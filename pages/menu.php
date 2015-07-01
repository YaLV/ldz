<?
$sql1=db_conn('1');
$sql2=db_conn('2');
$sql1->query("select `key`,sortable from listValues where page='biedri' order by id ASC");
while($sql1->get_row()) {
  $key = $sql1->get_col();
  $value = get_reply("select `value` from languages where page='biedri' and `key`='$key'");
  $sortable = $sql1->get_col(); 
  $sql2->query("show full columns from biedri where Field='$key'");
  $sql2->get_assoc();
  $filters[$key]=Array("fieldInfo" => $sql2->get_acol('Comment'),"fieldName" => $value);
}

$templates->template="filterField.php";
foreach($filters as $k => $v) {
  if(strpos($v['fieldInfo'],"radio")>0) {
    $v['fieldInfo'] = preg_replace('/value":{/','value":{"zzz":"Nelietot",',$v['fieldInfo']);
  }
  if(strpos($v['fieldInfo'],"custom")===false) {
    $v['fieldInfo'] = json_decode($v['fieldInfo'],true);
    $v['fieldInfo']['whereis']="search";
    $v['fieldInfo'] = json_encode($v['fieldInfo']);
    $templates->values['filterField']=preg_replace("/id=/","data-id=",preg_replace("/data-submit/",'data-nosubmit',preg_replace("/disabled='disabled'/","",makeField($v['fieldInfo'],$k,$_SESSION['filters'][$k]))));
  } else {
    $count = "<input type='text' name='children_count' placeHolder='Skaits' value='{$_SESSION['filters']['children_count']}' /><br />";
    $date = "<input type='text' name='children_date' class='filter_date' placeholder='Mēnesis' value='{$_SESSION['filters']['children_date']}' />"; 
    $templates->values['filterField']="$count$date";
  }
  $templates->values['filterName']=$v['fieldName'];
  $templates->parseNormalOutput();
  $filterFields[] = $templates->output;    
}
$templates->values['filters'] = implode("\n\t",$filterFields);
$menuItems="";
foreach($config['menuItems'] as $menuItem) {
  $menuItems.="<li><a href='/{$menuItem['link']}' {$menuItem['spec']}>{$menuItem['name']}</a></li>\n";
}                                                           
$templates->values['menuItems']=$menuItems;
$templates->template="menu.php";
$templates->parseNormalOutput();
$templates->values['menu'] = $templates->output;