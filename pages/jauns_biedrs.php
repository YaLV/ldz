<?

if(count($_FILES)>0) {
  $file = file_get_contents($_FILES['applications']['tmp_name']);
  $fileData = explode("base64,",$file);
  if($fileData[1]!='') {
    $file = base64_decode($fileData[1]);
  } 
  $file = addslashes($file);
  $sql->query("replace into memberPictures values({$_GET['edit']},".time().",'$file')");
  echo "Bilde Nomainīta";
  exit();
} elseif(!count($_POST)>0) {
  $sql1=db_conn('1');
  $sql2=db_conn('2');
  $templates->template='inputfields.php';
  $sql1->query("show full columns from biedri");
  while($sql1->get_assoc()) {
    if($_GET['edit']) {
      $currentVal = get_reply("select {$sql1->get_acol('Field')} from biedri where number={$_GET['edit']}");
    } else {
      $currentVal = "";      
    }
    if(preg_match('/^\d\d\d\d-\d\d-\d\d$/',$currentVal)) {
      $slices = explode("-",$currentVal);
      $currentVal=$slices[2].".".$slices[1].".".$slices[0];
      if($currentVal=="00.00.0000") {
        $currentVal="";
      }
    }
    $field=$sql1->get_acol('Comment');
    $templates->values['fieldName']=$sql1->get_acol('Field');
    $templates->values['field']=makeField($field,$templates->values['fieldName'],$currentVal);
    $templates->values['fieldName']=get_reply("select `value` from languages where `key`='{$templates->values['fieldName']}'");
    $templates->parseNormalOutput();
    $line[]=$templates->output;
  }
  
  if($_GET['edit']) {
  $templates->values['picture']="
<div class='filters picture'>
  <div class='filter_header picture_header'>
    <p>Bilde</p>
  </div>
  <div class='filter_selection'>
    <form method='post' enctype='multipart/form-data' action='/jauns_biedrs".($_GET['edit'] ? "?edit={$_GET['edit']}" : "")."'>
      <table class='filter_table'>
        <tr>
          <td colspan='2'>
            <img src='/picture?id={$_GET['edit']}' />
          </td>
        </tr>
        <tr>
          <td> Mainīt: </td>
          <td><input type='file' name='picture' data-msg='Lūdzu uzgaidiet, bilde tiek apstrādāta' /></td>
        </tr>
      </table>
    </form>
  </div>
</div>";
  } else {
    $templates->values['picture']="";
  }
  $templates->values['formAction']="/jauns_biedrs".($_GET['edit'] ? "?edit={$_GET['edit']}" : "");
  $templates->values['inputFields']=implode("\n",$line);
  $karteDrukat=($_GET['edit'] ? "&nbsp;&nbsp;&nbsp;<a href='/assign?id={$_GET['edit']}' class='btn btn-mini btn-warning pull-right assignM'>Drukāt karti</a>" : "");
  $templates->values['controlFields']="<span class='formControls'><button class='pull-left btn-success'>Saglabāt</button>$karteDrukat</span>";
  $templates->template="jauns_biedrs.php";
  $templates->parseNormalOutput();
  $templates->values['center']=$templates->output;
} else {
  foreach($_POST as $field => $value) {
    if(preg_match("/[a-zA-Z]/",$field)) {
      try {
        $fieldExists = @get_reply("select $field from biedri limit 1");
        $fieldName[] = $field;
        $fieldValue[] = $value;
      } catch(Exception $e) {
        $errors = 1;       
      }
    }
  }
  if(!$_GET['edit']) {
    $fieldNames = implode(",",$fieldName);
    $fieldValues = "'".implode("','",$fieldValue)."'";
    showError();
    $sql->query("insert into biedri ($fieldNames) values($fieldValues)");
    $templates->values['message']="5";
    $templates->values['returnTo']="/biedri";
  } else {
    foreach($fieldName as $key => $currentFieldName) {
      $fields[]="$currentFieldName='{$fieldValue[$key]}'";
      $fieldValues[$currentFieldName] = $fieldValue[$key]; 
    }
    $fieldVals = implode(",",$fields);
    writeChangeLog($fieldValues,$_GET['edit'],$_SESSION['uname']);
    showError("update biedri set $fieldVals where number='{$_GET['edit']}'");
    $sql->query("update biedri set $fieldVals where number='{$_GET['edit']}'");
    $templates->values['message']="5";
    $templates->values['returnTo']="/biedri";
  }
}
?>