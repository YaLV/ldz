<?

include getcwd()."/ExcelRead/PHPExcel.php";
include getcwd()."/pages/xls/common.php";


if(!$_GET['export']) {
  $fieldsToShow = $xls->getAllFields();
} else {
  $fieldsToShow = $xls->parseShowFields(explode(",",$_GET['displayFields']));
}

$dataValidation=array();
$fieldsValidate = Array(
  "workPlace" => "createWorkPlaceList",
  "comitee" => "createComiteeList",
  "memberStatus" => "createState",
  "car" => "createYesNo",
  "comiteePresident" => "createYesNo",
  "insurance" => "createCheckBoxValues"
);

function getDataValidationFields($key,$value) {
  global $fieldsValidate,$dataValidation;
  if(array_key_exists($key,$fieldsValidate)) {
    $xls->addValidation[$key] = true;
    showError($key." = ".$xls->addValidation[$key]);
    $dataValidation[$fieldsValidate[$key]]=true;
  }
}

$xls->type = $_GET['type'];

$excel = new PHPExcel();

$sheet = $excel->getSheet(0);
$sheet->setTitle("Anketa");

$excel->createSheet(1);
$validationSheet = $excel->getSheet(1);
$validationSheet->setTitle("dataValidation");

$excel->createSheet(2);
$dataSheet = $excel->getSheet(2);
$dataSheet->setTitle("saveData");

array_walk($fieldsToShow, "getDataValidationFields");
foreach($dataValidation as $function => $unused) {
  if($unused==true) {
    $xls->$function($validationSheet);
  }
}

$xls->column=0;
$increase = 0;
foreach($fieldsToShow as $field => $name) {
  $columnID = $field+$increase;
  $increase+= $xls->writeHorizontalHeader($sheet,$columnID,$name);
}

$sql2 = db_conn('2');

function isExactValue($key) {
  global $sql2;
  $sql2->query("show full columns from biedri where Field='$key'");
  $sql2->get_assoc();
  return (preg_match('/"sql"/',$sql2->get_acol('Comment')));
}

if($_GET['export']) {
  $sql1=db_conn("1");

  $sortOrder = ($_SESSION['sorting']['order']=='ascending' ? "ASC" : "DESC");
  foreach($_SESSION['filters'] as $k => $v) {
    if(strlen($v)>0) {
      if($k!='insurances') {
        if($k=='children_date') {
          $v=(strlen($v)==1 ? "0$v" : "$v");
          $where[]="children like '%.$v.%'";
        } elseif($k=='children_count') {
          $where[] = "(length(children)-length(replace(children,',',''))+1>=$v and length(children)>0)";
        } else {
          if($k=='standing' || $k=='accessionDate') {
            $where[]=$k.">=$v";
          } elseif(isExactValue($k)) {
            $where[]=$k."=$v";            
          } elseif(in_array($k,$dateFields)) {
            $vv=explode(".",$v);
            $v=$vv[2]."-".$vv[1]."-".$vv[0];
            $where[] = "$k='$v'";
          } else {
            $where[]=$k." like '%$v%'";
          }
        }
      } else {
        foreach(explode(",",$v) as $val) {
          $where[]=$k." like '%$val%'";
        }
      }
    }
  }
  
  if(count($where)>0) {
    $where = "where " . implode(" and ",$where);
  } else {
    unset($where);
  }
  
  $xls->row=4;
  $sql1->query("select ".implode(',',$fieldsToShow)." from biedri $where order by {$_SESSION['sorting']['field']} $sortOrder");
  while($sql1->get_assoc()) {
    $xls->createHorizontalValidation($sheet,$dataSheet,'1');
    $xls->row--;
    foreach($fieldsToShow as $field) {
      $fieldValue = $sql1->get_acol($field);
      if($field!='insurance') {
        if($xls->answers[$field]) {
          $fieldValue = $xls->getValue($field,$fieldValue);
        } 
        $sheet->setCellValue($xls->cell[$field].$xls->row,$fieldValue);
      } else { // checkbox
        $values = $xls->getValue($field,$fieldValue);
        foreach($xls->insurance as $fieldName => $Column) {
          $sheet->setCellValue($Column.$xls->row,$values[$fieldName]);
        }
      }
    }
  $xls->row++;
  }
}

$headers=true;
$xls->createHorizontalValidation($sheet,$dataSheet,($_GET['lines'] ? $_GET['lines'] : 100),$headers);


$sheet->freezePane("A4");
$sheet->getStyle("A2:Z3")->getFont()->setBold("true");
$sheet->getStyle("A2:Z3")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$styleArray = array(
     'borders' => array(
           'allborders' => array(
                  'style' => PHPExcel_Style_Border::BORDER_THIN,
           ),
     ),
);
$maxCol = $sheet->getHighestColumn();
$sheet->getStyle("A2:".$maxCol."3")->applyFromArray($styleArray);
$validationSheet->setSheetState(PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
$dataSheet->setSheetState(PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
$sheet->getColumnDimension('A')->setVisible(false);
$sheet->getRowDimension('1')->setVisible(false);



header("Content-Type: application/vnd.ms-excel");
$fn = 'Anketa';
header('Content-Disposition: attachment;filename="'.$fn.'.xlsx"');
header('Cache-Control: max-age=0');
$writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
$writer->setPreCalculateFormulas(FALSE);
$writer->save('php://output');
exit();
?>
