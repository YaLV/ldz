<?
ob_start();

list($currentPage,$currentSubPage,$currentOptions) = spliti("/",$_GET['section']);

switch($currentPage) {
  case "i":
    include "i.php";
    exit(); 
  break;
  
  case "importKeys":
    if($_SESSION['sadmin']) {
      include PAGES."menu.php";
      include PAGES."loadKeys.php";
      $templates->template="index.php";
    } else {
      header("location:/biedri");
      exit();
    }
  break;
  
  case "superAdmin":
    showError(strtolower($_POST['login']));
    if(strtolower($_POST['login'])=='parole') {
      $_SESSION['sadmin']=true;
      echo "/importKeys";
    } else {
      echo "/logout";
    }
    exit();
  break;
  
  case "assign":
    $sql->query("update certificates set isActive=0 where expireDate<'".date("Y-m-d")."'");
    $doIHaveAssignedMagneticStrip = get_reply("select expireDate from certificates where memberNumber={$_GET['id']} and isActive=1 order by cardNumber DESC limit 1");
    if($doIHaveAssignedMagneticStrip) {
      $splitDate = explode("-",$doIHaveAssignedMagneticStrip);
      $myExpireDate = mktime(0,0,0,$splitDate[2],$splitDate[1],$splitDate[0]);
    }
    if(!$myExpireDate || $myExpireDate<time()) {
      $sql->query("update certificates set memberNumber={$_GET['id']} where memberNumber=0 limit 1");
      $doIHaveAssignedMagneticStrip = get_reply("select expireDate from certificates where memberNumber={$_GET['id']} and isActive=1 order by cardNumber DESC limit 1");
    }
    echo "document.location='ldz::{$_GET['id']}'";
    exit();
  break;
  
  case "picture":
    include getcwd()."/pages/memberPicture.php";
    exit(); 
  break;

  case "login":
    include PAGES."login.php";  
  break;

  case "logout":
    session_destroy();
    header("location:/login");
    exit();
  break;

  case (array_key_exists($currentPage,$config['menuItems']) ? $currentPage : !$currentPage):
    if(is_logged()) {
      include PAGES."menu.php";
      include PAGES.$config['menuItems'][$currentPage]['file'];
      $templates->template="index.php";
    } else {
      header("location:/logout");
      exit();
    }
  break;
  
  default:
    if(is_logged()) {
      header("location:/biedri");
      exit();
      //include PAGES."menu.php";
      //include PAGES."home.php";
    } else {
      header("location:/logout");
      exit();
    }
  break;
}

$templates->errors = ob_get_clean();

if(!count($_POST)>0) {
  $templates->parseNormalOutput();
} else {
  $templates->parsePostResponse();
}