<?
if(!$_FILES && !isset($_GET['load'])) {
  include getcwd()."/pages/xls/writer.php";
} else {
  if(count($_FILES)>0) {
    include getcwd()."/pages/xls/reader.php";
  } else {
    $templates->values['formHeader']="Izvēlieties Anketu ko ielādēt.";
    $templates->values['msg']="Lūdzu uzgaidiet, Anketa tiek apstrādāta";
    $templates->values['requesturi']="/".$_GET['section'];
    $templates->template="formLoad.php";
    $templates->parseNormalOutput();
    $templates->values['center']=$templates->output;
  }
}

?>