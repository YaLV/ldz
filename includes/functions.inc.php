<?
function validateInput($value,$fieldName) {
  global $ErrorFields;
  switch($fieldName) {
    case "insurance":
      if(strlen($value)>0) {
        return substr($value, 0, strlen($value)-1);
      } else {
        return $value;
      }
    break;        

    case "workPlace":
      preg_match("/([\d]+)$/",$value,$found);
      return $found[1];
    break;
    
    case "personalCode":
      if(preg_match("/^\d\d\d\d\d\d-[12]\d\d\d\d$/",$value)) {
        return $value;
      } else {
        $ErrorFields++;
        return "";
      }
    break;
    
    default:
      return $value;
    break;
  }
}

function showError($errors=false) {
  if(!$errors) {
    $error = error_get_last();
  } else {
    $error['message']=$errors;
  }
  $file = fopen(getcwd()."/logfile","a+");
  fwrite($file,$error['message']."\n");
  fclose($file);
}

function getPageTexts() {
  global $config,$sql;
  $sql->query("select `key`,`value` from languages where page like '%common%' or page like '%login%'");
  while($sql->get_row()) {
    $config['language'][$sql->get_col()]=$sql->get_col();
  }  
}

function languageCallback($matches) {
  global $config,$templates;
  $match = substr($matches[1],1,-1);
  if(array_key_exists($match,$config['language'])) {
    return $config['language'][$match];
  } elseif(array_key_exists($match,$templates->values)) {
    return $templates->values[$match];
  } else {
    return "Element Not Found: $match";
  }
}

function makeField($data,$fieldName,$value=false) {
  $first=true;
  $sql2 = db_conn("2");
  $inputs=Array('text','password');
  $selects=Array("radio","checkbox");
  $fieldData = json_decode($data,true);
  if(in_array($fieldData['type'],$inputs)) {
    $field="<input type='{$fieldData['type']}' name='$fieldName' value='$value' {$fieldData['spec']} />";
  } elseif($fieldData['type']=='textarea') {
    $field="<textarea name='$fieldName' style='height:140px;width:400px;'>$value</textarea>";
  } elseif(in_array($fieldData['type'],$selects)) {
    if($fieldData['type']=='checkbox') {
      $field = "<input type='hidden' id='field_$fieldName' name='$fieldName' data-submit='grab$fieldName' />
      <script>
        function grab$fieldName() {
          container = $('#field_$fieldName');
          items = new Array();
          $('[id^=$fieldName]').each(function(){
            if($(this).is(':checked')) {
              items.push($(this).val());
            }
          });
          container.val(items.join(','));
        }
      </script>
      ";
    }
    foreach($fieldData['value'] as $val => $name) {
      if(!$value && $first && $fieldData['type']=='radio') {
        $checked="checked='checked'";
        $first=false;
      } else {
        $valuecheck = explode(",",$value);
        if(is_array($valuecheck)) {
          $checked=in_array($val,$valuecheck) ? "checked='checked'" : ""; 
        } else {
          $checked=$value==$val ? "checked='checked'" : "";
        }
      }
      if($fieldData['type']=='checkbox') {
        $field.="<input style='float:left;' type='{$fieldData['type']}' value='$val' $checked id='$fieldName"."_$val'/><label for='$fieldName"."_$val' style='float:left;'>&nbsp; $name</label><div class='clear'></div>";
      } else {
        $field.="<input style='float:left;' type='{$fieldData['type']}' name='$fieldName' value='$val' $checked id='$fieldName"."_$val'/><label for='$fieldName"."_$val' style='float:left;'>&nbsp; $name</label><div class='clear'></div>";
      }
    }
  } elseif($fieldData['type']=='select') {
    $field="<option></option>";
    $orderFields = explode(",",$fieldData['columns']);
    if(count($orderFields)>2) {
      $orderby = $orderFields[1].",".$orderFields[2];
    } else {
      $orderby = $orderFields[1];
    }
    $sql2->query("select {$fieldData['columns']} from {$fieldData['table']} order by $orderby");
    while($sql2->get_row()) {
      if($fieldData['place']=='javascript') {
        $f = ($fieldData['whereis']=='search' ? "s" : "");      
        $val = $sql2->get_col(); 
        $psid=$sql2->get_col();                                
        if(!$made[$psid]) {
          $jss.="$fieldName$f"."[$psid]=[];\n";
          $made[$psid]=true;
        }
        $jss.="$fieldName$f"."[$psid].push({id: {$val}, value : '{$sql2->get_col()}'});\n";
      } else {
        $val = $sql2->get_col();
        $nm=$sql2->get_col();
        $checked=$value==$val ? "selected='selected'" : "";
        if(is_numeric($nm)) {
          $nm=$sql2->get_col();
          $checked=$value==$val ? "selected='selected'" : "";
        }
        $field.="<option value='$val' $checked>$nm</option>\n";
      }
    }
    if($fieldData['place']=='javascript') {
      $c = ($fieldData['whereis']=='search' ? "." : "#");
      $f = ($fieldData['whereis']=='search' ? "s" : "");      
      $edit = ($value ? "$('".$c."{$fieldName} option[value={$value}]').prop('selected','selected');" : "");
      $jsz = ($fieldData['whereis']=='search' ? "
        if(typeof elem!='undefined') {
          if(typeof $fieldName$f"."[elem.val()]!='undefined') {
            $.each($fieldName$f"."[elem.val()], function(id,el) {
              $('$c$fieldName').append('<option value=\"'+el.id+'\">'+el.value+'</option>');
            });
          } else {
            for( key in $fieldName$f) {
              $.each($fieldName$f"."[key], function(id,el) {
                $('$c$fieldName').append('<option value=\"'+el.id+'\">'+el.value+'</option>');
              });
            }
          }          
        }
      " : "
        if(typeof $fieldName$f"."[elem.val()]!='undefined') {
          $.each($fieldName$f"."[elem.val()], function(id,el) {
            $('$c$fieldName').append('<option value=\"'+el.id+'\">'+el.value+'</option>');
          });
        }
      "); 
      $js = "
      <script>
      var $fieldName$f = [];
      $jss
      $('$c{$fieldData['owner']}').change(function(){ change$fieldName$f($(this));});    
      function change$fieldName$f(elem) {
        $('$c$fieldName').children('option').each(function(){ $(this).remove();});
        $('$c$fieldName').append('<option></option>');
        $jsz;
      }
      $(document).ready(function(){ change$fieldName$f($('$c{$fieldData['owner']}')); $edit});
      </script>";
    }
    $cls = ($fieldData['whereis']=='search' ? "class" : "id");
    $field = "$js<select $cls='$fieldName' name='$fieldName' {$fieldData['action']}>$field</select>";
  } elseif($fieldData['type']=='custom') {
    try {
      
      $childs = explode(",",$value);
      if(!is_array($childs)) {
        throw new exception("navMulti");
      }
      /*
      $form = get_reply("select `value` from settings where `key`='{$data['formKey']}'");
      $replaces = explode(",",$data['fields']);
      $sql->query("select {$data['fields']} from {$data['table']} {$data['where']}");
      while($sql->get_row()) {
        foreach($replaces as $k => $v) {
          $reps[] = $sql->get_acol($v);
          $pats[] = "{".$v."}"; 
        }
        $data = preg_replace($pats,$reps,$form);
      }
      */
      foreach($childs as $child) {
        if(!empty($child)) {
          $fdata .= "<option>$child</option>";
        }
      }
    } catch(exception $e) {
      if(!empty($value)) {
        $fdata = "<option>$value</option>";
      } else {
        $fdata = "";
      }
    }
    //$field = get_reply("select `value` from settings where `key`='$fieldName'");
    //$patz=Array("/\{fieldName\}/","/\{data\}/");
    //$repz=Array($fieldName,$data);
//    $field=preg_replace($patz,$repz,$field);    
    $field = "<input type='hidden' id='$fieldName' name='$fieldName' data-submit='grabChildren'/>";
    $field.= "<select id='childrenList' multiple>$fdata</select><a href='javascript:removeChildren()' class='formBtn'>Izdzēst</a><br /><br />";
    $field.= "<input type='text' id='childrenBD' class='pickDate' placeHolder='Dzimšanas datums' style='width:180px;'/> <a href='javascript:addChildren()' class='formBtn'>Pievienot</a>";
    $field.= "<script>
    function addChildren() {
      content=$('#childrenBD').val();
      $('#childrenList').append('<option>'+content+'</option>');
      $('#childrenBD').val('');
    }
    function grabChildren() {
      container = $('#children');
      itemList = new Array();
      $('#childrenList option').each(function() {
        itemList.push($(this).val());
      });
      container.val(itemList.join(','));
      console.log(container.val());
    }
    function removeChildren() {
      $('#childrenList option:selected').remove();
    }
    </script>";
  } 
  unset($sql2);
  return ($field ? $field : $fieldData['type']);
}

function writeChangeLog($values,$number,$changesMadeBy=false) {
  global $sql;
  foreach($values as $fieldName => $fieldValue) {
    $oldFieldValue = get_reply("select $fieldName from biedri where number=$number");
    showError("$fieldValue!=$oldFieldValue");
    if($fieldValue!=$oldFieldValue) {
      $changes = json_encode(Array("new" => $fieldValue, "old" => $oldFieldValue));
      $sql->query("insert into changeLog values('','$number','$fieldName','$changes','$changesMadeBy')");
      showError("insert into changeLog values('','$number','$fieldName','$changes','$changesMadeBy')");
    }
  }
}

?>