<?
$templates->template = 'loginForm.php';
$templates->values['returnTo']='/';

if(isset($_POST['login']) && isset($_POST['password'])) {
  $login = preg_match("/^[a-zA-Z0-9]+$/",$_POST['login']) ? $_POST['login'] : "";
  $password = $_POST['password'];
  $sql->query("select id,username,password from users where username='$login'");
  if($sql->get_row()) {
    $id = $sql->get_col();
    $uname = $sql->get_col();
    $pwd = $sql->get_col();
    if(crypt($password,$pwd)==$pwd) {
      $_SESSION['string'] = crypt("$id-$uname-$pwd");
      $_SESSION['entered'] = true;
      $_SESSION['lastAction'] = time();
      $_SESSION['uid']=$id;
      $_SESSION['uname'] = $uname;
      $templates->values['message'] = 3;
    } else {
      $templates->values['message'] = 1;
    }
  } else {
    $templates->values['message'] = 2;
  } 
} else {
  $templates->values['message'] = 4;
}
?>