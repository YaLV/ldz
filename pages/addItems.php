<?

if(count($_POST)>0) {
  if($_POST['edit']) {
    $text = get_reply("select {$_POST['field']} from {$_POST['table']} where {$_POST['idField']}={$_POST['edit']}");
    $templates->values['fieldName'] = $_POST['field'];
    $templates->values['idFieldName'] = $_POST['idField'];
    $templates->values['fieldValue'] = $text;
    $templates->values['action'] = $_SERVER['REQUEST_URI']."&edit={$_POST['edit']}";
    $templates->template="editField.php";
    $templates->parseNormalOutput();
    echo $templates->output;
    exit();  
  } else {
    $workPlace = ($_GET['table']=='comitees' ? "&workPlace={$_GET['workPlace']}" : "");
    if($_GET['edit'] && !empty($_GET['edit'])) {
      $sql->query("update {$_GET['table']} set {$_POST['field']}='{$_POST[$_POST['field']]}' where {$_POST['idField']}={$_GET['edit']}");
    } elseif(!empty($_POST['value'])) {
      $add = ($_GET['workPlace'] ? "'{$_GET['workPlace']}'," : "");
      $sql->query("insert into {$_GET['table']} values('',$add'{$_POST['value']}')");
    }
    $templates->values['returnTo']="/selectFields?table={$_GET['table']}$workPlace";
  }    
} elseif(($_GET['table'] && $_GET['workPlace']) || $_GET['table']=='workPlaces') {
  if($_GET['remove']) {
    $firstField = get_reply("show columns from {$_GET['table']}");
    $sql->query("delete from {$_GET['table']} where $firstField={$_GET['remove']}");
  }                                                              
  if(get_reply("show tables like '%{$_GET['table']}%'")) {
    $templates->values['table']=$_GET['table'];
    $where = $_GET['workPlace'] ? "where workPlaceId='{$_GET['workPlace']}'" : "";
    $orderby = ($_GET['workPlace'] ? "order by comiteeName ASC" : "order by workPlaceName ASC");
    $sql->query("select * from {$_GET['table']} $where $orderby");
    while($sql->get_row()) {
      $id= $sql->get_col();
      if($_GET['workPlace']) {
        $sql->get_col();
        $idField="comiteeId";
        $fieldName='comiteeName';
      } else {
        $idField="workPlaceId";
        $fieldName='workPlaceName';
      }
      $name = $sql->get_col();
      $vertiba[]="<li data-idField='$idField' data-field='$fieldName' id='$id' data-table='{$_GET['table']}'>$name <a href='/{$_GET['section']}?table={$_GET['table']}&workPlace={$_GET['workPlace']}&remove=$id' style='float:right;' class='btn btn-mini btn-danger' data-ask='Vai tiešām dzēst šo ierakstu?'>Dzēst</a></li>";          
    }
    $templates->values['formAction']="/".$_GET['section']."?table={$_GET['table']}&workPlace={$_GET['workPlace']}";
    $templates->values['currentValues']=implode("",$vertiba);
    $templates->template='addItems.php';
    $templates->parseNormalOutput();
    $templates->values['center']=$templates->output;
  } else {
    header("location:/");
    exit();
  }
} elseif(!$_GET['table'] && !$_GET['workPlace']) {
  foreach($config['listTables'] as $table => $name) {
    $templates->values['tables'] .= "<li><a href='{$_GET['section']}?table=$table'>$name</a></li>"; 
  }
  $templates->template='translate_select_tables.php';
  $templates->parseNormalOutput();
  $templates->values['center']=$templates->output;
} else {
  $sql->query("select * from workPlaces order by workPlaceName ASC");
  while($sql->get_row()) {
    $id = $sql->get_col();
    $name = $sql->get_col();
    $values[] = "<li><a href='/{$_GET['section']}?table={$_GET['table']}&workPlace={$id}'>$name</a></li>"; 
  }
  $templates->values['tables']=implode("",$values);
  $templates->template='translate_select_tables.php';
  $templates->parseNormalOutput();
  $templates->values['center']=$templates->output;
} 