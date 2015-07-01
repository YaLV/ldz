<?
$templates->values['headerItems']="";

function getPages($currentPage,$filters) {
  global $sql;
  $pageCount = ceil(get_reply("select count(*) from biedri $filters")/50);
  $curStartPage=$currentPage-5;
  $curStartPage = ($curStartPage>0 ? $curStartPage : 1);
  $curEndPage = $currentPage+5;
  $curEndPage = ($curEndPage<=$pageCount ? $curEndPage : $pageCount);
  for($curPage=$curStartPage;$curPage<=$curEndPage;$curPage++) {
    $current = ($curPage==$currentPage ? "active' disabled='disabled' onclick='return false;'" : "'");
    $pagination[]="<a href='/biedri?page=$curPage' class='btn btn-mini $current>$curPage</a>";
  }
  $pagination[]="&nbsp;&nbsp;<input type='text' style='width:35px;text-align:right;padding:2px;' value='$currentPage' name='page' />/$pageCount";
  return "<form method='get' action='/biedri'>".implode("",$pagination)."</form>";
}



function throwException($exception) {
  throw new exception($exception);
}
// change sort field and orientation
if(count($_POST)>0) {
  if(!$_POST['filter']) {
    preg_match("/^[a-zA-Z_]+$/",$_POST['sortField'],$matches);
    $fieldName = $matches[0];
    $fieldExists = get_reply("show columns from biedri like '$fieldName'");
    $_SESSION['sorting']['field']=($fieldExists==$fieldName ? $fieldName : $config['sorting']['defaultField']);
    if($_POST['sortOrder']=='ascending' || $_POST['sortOrder']=='descending') {
      $_SESSION['sorting']['order']=$_POST['sortOrder'];
    } else {
      $_SESSION['sorting']['order']=$config['sorting']['defaultOrder'];
    }
    exit();
  } elseif($_POST['filter']) {
    foreach($_POST as $k => $v) {
      try {
        if($k!='filter') {
          if($k!='children_count' && $k!='children_date') {
            get_reply("select $k from biedri where $k!='NULL' limit 1") or throwException(mysql_error());
          }
          if($v=='zzz' || (is_numeric($v) && $v==0)) {
            $_SESSION['filters'][$k]='';
          } else {
            $_SESSION['filters'][$k]=$v;
          }
        }
      } catch(exception $e) {
        showError($e);  
      }
    }
    $templates->values['returnTo']="/biedri";
  }
  $templates->values['action']="document.location='/biedri'";
} else {
  if(isset($_GET['reset'])) {
    showError("Reset");
    unset($_SESSION['filters']);
    $_SESSION['filter']=Array();
    header("location:/biedri");
    exit();
  }
  
  if(!$_SESSION['sorting']['field'] && !$_SESSION['sorting']['order']) {
    $_SESSION['sorting']['field']=$config['sorting']['defaultField'];
    $_SESSION['sorting']['order']=$config['sorting']['defaultOrder'];
  }
  
  $filters = Array();
  $sql1 = db_conn('1');
  $sql2 = db_conn('2');
  $sql1->query("select `key`,sortable from listValues where page='{$currentPage}' order by id ASC");
  while($sql1->get_row()) {
    $key = $sql1->get_col();
    $listField[]=$key;
    $value = get_reply("select `value` from languages where page='{$currentPage}' and `key`='$key'");
    $sortable = $sql1->get_col(); 
    if($sortable) {
      $sortedNow = ($_SESSION['sorting']['field']==$key ? ($_SESSION['sorting']['order']=='ascending' ? "descending active" : "active ascending"): "");
      $sortorder = ($_SESSION['sorting']['field']==$key ? ($_SESSION['sorting']['order']=='ascending' ? "descending" : "ascending") : "ascending");
      $templates->values['headerItems'].="<th class='sort' data-sortfield='$key' data-sortorder='$sortorder'><div><a>$value</a></div><div class='$sortedNow'><span></span></div></th>";
    } else {
      $templates->values['headerItems'].="<th><a>$value</a></th>";
    }
    $sql2->query("show full columns from biedri where Field='$key'");
    $sql2->get_assoc();
    $filters[$key]=Array("fieldInfo" => $sql2->get_acol('Comment'),"fieldName" => $value);
  }
  
  $sortOrder = ($_SESSION['sorting']['order']=='ascending' ? "ASC" : "DESC");
  $listFields = implode(",",$listField);
  $where = Array();
  foreach($_SESSION['filters'] as $k => $v) {
    if(strlen($v)>0) {
      if($k!='insurances') {
        if($k=='children_date') {
          $v=(strlen($v)==1 ? "0$v" : "$v");
          $where[]="children like '%.$v.%'";
        } elseif($k=='children_count') {
          $where[] = "(length(children)-length(replace(children,',',''))+1>=$v and length(children)>0)";
        } else {
          if($k=='standing' || $k=='accessionDate') {
            $where[]=$k.">=$v";
          } elseif(preg_match('/"sql"/',$filters[$k]['fieldInfo'])) {
            $where[]=$k."=$v";            
          } elseif(in_array($k,$dateFields)) {
            $vv=explode(".",$v);
            $v=$vv[2]."-".$vv[1]."-".$vv[0];
            $where[] = "$k='$v'";
          } else {
            $where[]=$k." like '%$v%'";
          }
        }
      } else {
        foreach(explode(",",$v) as $val) {
          $where[]=$k." like '%$val%'";
        }
      }
    }
  }
  
  if(count($where)>0) {
    $where = "where " . implode(" and ",$where);
  } else {
    unset($where);
  }
  
  $currentPages = ($_GET['page'] ? $_GET['page'] : 1);
  $pages = getPages($currentPages,"$where order by {$_SESSION['sorting']['field']} $sortOrder");
  $limit = $currentPages*50-50;
  $limit = "limit $limit,50";
  $sql1->query("select number as ID,$listFields from biedri $where order by {$_SESSION['sorting']['field']} $sortOrder $limit");
  while($sql1->get_assoc()) {
    $mnumber = $sql1->get_acol('ID');
    foreach($listField as $value) {
      $fieldData = $sql1->get_acol($value);  
      try {
        $currentField = $filters[$value];
        $obj = json_decode($currentField['fieldInfo'],true);
        if($obj['value']=='' || $obj['value']=='filter') {
          throw new Exception("standart");
        } 
        if($obj['value']=='sql') {
          $indexColumn = spliti(",",$obj['columns']);
          $lastIndex = count($indexColumn)-1; 
          throw new Exception(get_reply("select {$indexColumn[$lastIndex]} from {$obj['table']} where {$indexColumn[0]}=$fieldData"));
        }
        if($obj['type']=='custom') {
          $ccount = count(explode(",",$fieldData));
          throw new Exception("$fieldData");
        }
        if($obj['type']=='radio' || $obj['type']=='checkbox') {
          $objValues = explode(",",$fieldData);
          unset($returnInfo);
          foreach($objValues as $objValue) {
            $returnInfo[] = $obj['value'][$objValue]; 
          }
          throw new Exception(implode(",",$returnInfo));
        }
      } catch(Exception $e) {
        preg_match("/message '([^\']*)'/",$e,$msg);
        if($msg[1]!='standart') { $fieldData=$msg[1]; }
        if(preg_match('/^\d\d\d\d-\d\d-\d\d$/',$fieldData)) {
          $slices = explode("-",$fieldData);
          $fieldData=$slices[2].".".$slices[1].".".$slices[0];
          if($fieldData=="00.00.0000") {
            $fieldData="";
          }
        }
        $line.="<td data-id='$mnumber'><a alt='$fieldData' title='$fieldData'>$fieldData</a></td>";
      }
    }
    $row[]=$line;
    $line="";
  }
  
  $templates->values['listData']="<tr data-href='/jauns_biedrs?edit='>".implode("</tr>\n<tr data-href='/jauns_biedrs?edit='>",$row)."</tr>\n";
  $templates->values['pages']=$pages;
  $templates->template="biedri.php";
  $templates->parseNormalOutput();
  $templates->values['center']=$templates->output;
  
  $templates->template="filterField.php";
 
  $sql1->query("show full columns from biedri");
  while($sql1->get_assoc()) {
    $field = $sql1->get_acol('Field');
    $json = json_decode($sql1->get_acol('Comment'));
    if($json->showInForm) {
      if($field=='number' || $field=='personalCode') {
        $forced = "checked='checked' readonly='readonly'";
      } else {
        $forced='';
      }
      if($json->owner) {
        $ownclick="onclick='if($(this).is(\":checked\")) { $(\"#export_{$json->owner}\").prop(\"checked\",true);}'";
      } else {
        $ownclick="";
      }
      $templates->values['filterName']="<input type='checkbox' id='export_$field' class='exportable' value='$field' $forced $ownclick />";
      $templates->values['filterField']=get_reply("select `value` from languages where page='{$currentPage}' and `key`='$field'");
      $templates->parseNormalOutput();
      $exportFields[] = $templates->output; 
    }
  }
  $templates->values['filters'] = implode("\n\t",$filterFields);
  $templates->values['export'] = implode("\n\t",$exportFields);
  $templates->template = "filters.php";
  $templates->parseNormalOutput();
  $templates->values['center'].=$templates->output;
}
?>