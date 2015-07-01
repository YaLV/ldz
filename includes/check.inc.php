<?

function is_logged() {
  global $sql;
  $sql->query("select id,username,password from users where id='{$_SESSION['uid']}'");
  if($sql->get_row()) {
    $id = $sql->get_col();
    $uname = $sql->get_col();
    $pwd = $sql->get_col();
    if(crypt("$id-$uname-$pwd",$_SESSION['string'])==$_SESSION['string']) {
      if($_SESSION['lastAction']>time()-6000) {
        $_SESSION['lastAction']=time()+3600;
        return true;
      }      
    }
  }
  return false;
}



?>