<?
if(!$_GET['table']) {
  $sql->query("show tables");
  while($sql->get_row()) {
    $thisTable = $sql->get_col(); 
    $line[]="<li><a href='/fieldNames?table=$thisTable'>$thisTable</a></li>";
  }
  $templates->values['tables']=implode("\n",$line);
  $templates->template="translate_select_tables.php";
  $templates->parseNormalOutput();
  $templates->values['center']=$templates->output;
} else {
  if(count($_POST)>0) {
    $sql->query("delete from lanbguages where page='{$_GET['table']}'");
    $sql->query("show full columns from {$_GET['table']}");
    while($sql->get_assoc()) {
      $fieldName = $sql->get_acol('Field');
      $fieldType = $sql->get_acol('Type');
      $value = addslashes($_POST['config_'.$fieldName]);
      if($fieldName=='number') { $AI = 'AUTO_INCREMENT';} else { $AI='';}
      mysql_query("alter table `{$_GET['table']}` change `$fieldName` `$fieldName` $fieldType $AI comment '$value'") or mysql_error();
      mysql_query("replace into languages values('$fieldName','{$_POST['name_'.$fieldName]}','{$_GET['table']}')");
      //echo $_POST[$fieldName]."<br />";
      
    }
    $templates->values['message']=5;
    $templates->values['returnTo']="/$currentPage";
    //print_r($templates->values);
     //ALTER TABLE `user` CHANGE `id` `id` INT( 11 ) COMMENT 'id of user'
  } else {
    $sql1 = db_conn('1');
    $templates->template="translate_enter_names.php";
    $sql1->query("show full columns from {$_GET['table']}");
    while($sql1->get_assoc()) {
      $fieldName = $sql1->get_acol('Field');
      $fieldComment = htmlspecialchars($sql1->get_acol('Comment'),ENT_QUOTES);
      $name = get_reply("select `value` from languages where `key`='$fieldName' and page='{$_GET['table']}'");
      $line[]="<tr><td>$fieldName</td><td><input type='text' name='name_$fieldName' value='$name' /></td><td><input type='text' name='config_$fieldName' value='$fieldComment' /></td></tr>";
    }
    $templates->values['fieldNames']=implode("\n",$line);
    $templates->values['table']=$_GET['table'];
    $templates->parseNormalOutput();
    $templates->values['center']=$templates->output;
  }
}



?>