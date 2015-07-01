<?
ini_set("max_execution_time","0");
if(count($_FILES)>0) {
  $file = file_get_contents($_FILES['applications']['tmp_name']);
  $fileData = explode("base64,",$file);
  if($fileData[1]!='') {
    $file = base64_decode($fileData[1]);
  } 
  	
//memberNumber
//expireDate
//cardNumber
//stripStr
//isActive    yy/mm
  $keyz = explode("\n",$file);
  foreach($keyz as $line) {
    $exdate = explode("=",$line);
    $exDate = date("Y-m-d",mktime(0,0,0,substr($exdate[1], 2, 2), 1, substr($exdate[1], 0, 2)));
    $sql->query("insert into certificates (expireDate,stripStr,isActive) values('$exDate','$line',1)");
  }
  $count = count($keyz); 
  echo "$count Keys Loaded";
  exit();  
} else {
  $templates->values['formHeader']="Select Text file to load";
  $templates->values['msg']="Please wait, loading keys";
  $templates->values['requesturi']="/".$_GET['section'];
  $templates->template="formLoad.php";
  $templates->parseNormalOutput();
  $templates->values['center']=$templates->output;
}
?>