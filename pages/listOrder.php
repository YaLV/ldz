<?

if(!count($_POST)>0) {
  if(!$_GET['table']) {
    $sql->query("show tables");
    while($sql->get_row()) {
      $thisTable = $sql->get_col(); 
      $line[]="<li><a href='/fieldOrder?table=$thisTable'>$thisTable</a></li>";
    }
    $templates->values['tables']=implode("\n",$line);
    $templates->template="translate_select_tables.php";
    $templates->parseNormalOutput();
    $templates->values['center']=$templates->output;
  } else {
    $columns[]="<tr><td>Lauka nosaukums</td><td>Rādīt sarakstā</td><td>Iespēja kārtot pēc šī lauka</td></tr>";    
    $sql1=db_conn('1');
    $sql1->query("show full columns from {$_GET['table']}");
    while($sql1->get_assoc()) {
      $fieldNameOrig = $sql1->get_acol('Field');
      $fieldType = $sql1->get_acol('Type');
      $fieldName = get_reply("select `value` from languages where `key`='$fieldNameOrig' and page='{$_GET['table']}'");
      $sortable = get_reply("select sortable from listValues where page='{$_GET['table']}' and `key`='$fieldNameOrig'");
      if($sortable!='') {
        $listCheck="checked='checked'";
        if($sortable) {
          $sortCheck="checked='checked'";
        } else {
          $sortCheck='';
        }
      } else {
        $listCheck='';
        $sortCheck='';
      }
      $columns[]="<tr><td>$fieldName:</td><td><input type='checkbox' value='1' name='list_$fieldNameOrig' $listCheck /></td><td><input type='checkbox' value='1' name='sort_$fieldNameOrig' $sortCheck /></td></tr>";
    }
    $columns[]="<tr><td colspan='3'><button>Saglabāt</button></td></tr>";
    $templates->values['center']="<form method='post' action='/fieldOrder?table={$_GET['table']}'><table class='orderTable'>".implode("\n",$columns)."</table></form>";
  }
} else {
  $sql1=db_conn('1');
  $sql1->query("delete from listValues where page='{$_GET['table']}'");
  $sql1->query("show full columns from {$_GET['table']}");
  while($sql1->get_assoc()) {
    $fieldNameOrig = $sql1->get_acol('Field');
    if($_POST['list_'.$fieldNameOrig]) {
      $sortable = ($_POST['sort_'.$fieldNameOrig] ? 1 : 0);
      $sql->query("insert into listValues values('','$fieldNameOrig','$sortable','{$_GET['table']}')");
    }
  }
  $templates->values['message']=5;
  $templates->values['returnTo']="/biedri";
}