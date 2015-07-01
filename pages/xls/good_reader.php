<?

ini_set("max_execution_time",0);

include getcwd()."/ExcelRead/PHPExcel.php";

$usersUpdated = 0;
$usersAdded   = 0;
$ErrorFields  = 0;
$usersTotal   = 0;

function saveData($usersData) {
  global $sql,$usersUpdated,$usersAdded,$usersTotal;
  if($usersData['personalCode']) {
    if($usersData['number']) {
      $number = $usersData['number']; 
    } else {
      $number = get_reply("select number from biedri where personalCode = '{$usersData['personalCode']}'");
    }
    
    if($number) {
	unset($usersData['number']);
      foreach($usersData as $fieldName => $fieldValue) {
        $dataSave[] = "$fieldName='$fieldValue'";
      }
      $query="update biedri set ".implode(",",$dataSave)." where number='$number'";
      $usersUpdated++;  
    } else {
      $fieldNames = implode("`,`",array_keys($usersData));
      $fieldValues = implode("','",$usersData);
      $query="insert into biedri (`$fieldNames`) values('$fieldValues')";
      $usersAdded++;  
    }
    
    $usersTotal++;
	showError($query);
    $sql->query("$query");
  }
}

// upload file
$file = file_get_contents($_FILES['applications']['tmp_name']);
$fileData = explode("base64,",$file);
if($fileData[1]!='') {
  $file = base64_decode($fileData[1]);
} 
$fh = fopen(getcwd()."/up.xlsx","w");
fwrite($fh,$file);
fclose($fh);

// Reader
$excel = PHPExcel_IOFactory::createReader('Excel2007');
$excel->setLoadAllSheets();
$xlsFile = getcwd()."/up.xlsx";
$excelFile = $excel->load($xlsFile);

// sheets
$dataSheet = $excelFile->getSheet(0);
$extraDataSheet = $excelFile->getSheet(2); 

if($dataSheet && $extraDataSheet) {
  // get columns for fields
  $columns="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
  $mainColumnsByNames=Array();
  $columnCount = strpos($columns,$dataSheet->getHighestColumn());
  foreach(array('dataSheet','extraDataSheet') as $sheet) {          
    $currentColumn = 0;
    while($columnCount>=$currentColumn) {
      $cell = $columns[$currentColumn];
      $cellValue = $$sheet->getCell($cell."1")->getValue();
      if($cellValue) {
        if(!$columnsByNames[$sheet][$cellValue]) {
          $columnsByNames[$sheet][$cellValue]=$cell;      
        }
      }
      $currentColumn++;
    }
  }
  
 
  $currentRow = 4;
  // User Values
  $rowCount = $dataSheet->getHighestRow();
  while($rowCount>=$currentRow) {
    foreach($columnsByNames['dataSheet'] as $fieldName => $columnName) {
      if($columnsByNames['extraDataSheet'][$fieldName]) {
        $userList[$fieldName]=validateInput($extraDataSheet->getCell($columnsByNames['extraDataSheet'][$fieldName].$currentRow)->getCalculatedValue(),$fieldName);
      } else {
        $userList[$fieldName]=validateInput($dataSheet->getCell($columnName.$currentRow)->getValue(),$fieldName);
      }
    }
    
    saveData($userList);
    unset($userList);
    $currentRow++;
  }
  
  $eflds = ($ErorFields>0 ? "<tr>
        <td>
          Biedri ar k??dainu Personas Kodu: 
        </td>
        <td>
          $ErrorFields
        </td>
      </tr>" : "");
  
  echo "
    <table>
      <tr>
        <td>
          Jauni biedri: 
        </td>
        <td>
          $usersAdded
        </td>
      </tr>

      <tr>
        <td>
          Eso�ie biedri: 
        </td>
        <td>
          $usersUpdated
        </td>
      </tr>

      $eflds

      <tr>
        <td>
          Kop?� ielas?ti biedri: 
        </td>
        <td>
          $usersTotal
        </td>
      </tr>
    </table>
  ";
   exit();
     
} else {
  echo "Ielādējamais fails nav pareizā formātā.";
  exit();
}