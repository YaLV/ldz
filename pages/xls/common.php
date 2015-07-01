<?

class xls {
  
  public $fieldData = Array();
  public $row = 1;
  public $column = 0;
  public $columns = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
  public $comitees;
  public $workPlaces, $savedData = Array();
  public $cell;
  private $setWidth = Array('address','workPlace','comitee');
  
  public function getValue($fieldName,$fieldValue) {
    if($fieldName!='insurance') {
      return $this->savedData[$fieldName][$fieldValue];
    } else {
      $returnData = Array(); 
      $currentValues = explode(",",$fieldValue);
      foreach($this->savedData[$fieldName] as $field => $key) {
        $returnData[$field]=(in_array($field,$currentValues) ? "1" : "");
      }
      return $returnData;
    }
  }
  
  public function getAllFields() {
    global $sql;
    $returnData = Array();
    $sql->query("show full Columns from biedri where Comment like '%showInForm%'");
    while($sql->get_assoc()) {             
      $field = $sql->get_acol("Field");
      $this->fieldData[$field] = json_decode($sql->get_acol("Comment"),1);
      $returnData[] = $field; 
    }
    return $returnData;
  }
  
  public function parseShowFields($data) {
    global $sql;
    $fieldAviable = Array();
    $sql->query("show full Columns from biedri where Comment like '%showInForm%'");
    while($sql->get_assoc()) {
      $field = $sql->get_acol('Field');
      if(in_array($field,$data)) {
        $fieldAviable[] = $field;        
        $this->fieldData[$field] = json_decode($sql->get_acol("Comment"),1);
      }
    }
    return $fieldAviable;
  }
 
  private function getNames($field) {
    return get_reply("select `value` from languages where `key`='$field'"); 
  }
  
  public function writeHorizontalHeader($sheet,$column,$data) {
    $fieldData = $this->fieldData[$data];
    if($fieldData['type']!='checkbox') {
      $sheet->setCellValue($this->getCurrentColumn($column)."1",$data);
      $sheet->setCellValue($this->getCurrentColumn($column)."3",$this->getNames($data));
      $increase=0;  
      $cells = $this->getCurrentColumn($column);
    } else {
      $checkBoxCount = count($fieldData['value']); 
      $increase = --$checkBoxCount;
      $sheet->mergeCells($this->getCurrentColumn($column)."2:".$this->getCurrentColumn($column+$increase)."2");
      $sheet->mergeCells($this->getCurrentColumn($column)."1:".$this->getCurrentColumn($column+$increase)."1");
      $sheet->setCellValue($this->getCurrentColumn($column)."2",$this->getNames($data));
      $sheet->setCellValue($this->getCurrentColumn($column)."1",$data);
      $checkBoxIndex = 0;
      foreach($fieldData['value'] as $key => $value) {
        $curCell = $this->getCurrentColumn($column+$checkBoxIndex);
        $this->insurance[$key] = $curCell;
        $cells[] = $curCell;
        $sheet->setCellValue($this->getCurrentColumn($column+$checkBoxIndex)."3",$value);
        $checkBoxIndex++;
        $sheet->getColumnDimension($this->getCurrentColumn($column+$checkBoxIndex))->setWidth(17);
      }
      $cells = implode(",",$cells);
    }
    if(in_array($data,$this->setWidth)) {
      $sheet->getColumnDimension($this->getCurrentColumn($column))->setWidth(33);
    } else {
      $sheet->getColumnDimension($this->getCurrentColumn($column))->setwidth(23);
    }
    $this->cell[$data]=$cells;
    if(array_key_exists($data,$this->validation) || is_array($fieldData['value'])) {
      $this->validationColumn[$data]=$cells;
    }
    return $increase;
  }
  
  public function createCheckBoxValues($sheet) {
    $this->row = 1;
    $values = $this->fieldData['insurance']['value'];
    $columnID = $this->getCurrentColumn(0);
    $answers=Array();
    foreach($values as $insuranceKeys => $insuranceValues) {
      $this->savedData['insurance'][$insuranceKeys] = $insuranceValues; 
      $sheet->setCellValue($columnID.$this->row,$insuranceKeys);
      $answers[$columnID][]='IF({cell}="1",dataValidation!'.$columnID.$this->row.'&",","")';
      $this->row++;
    }
    $this->answers['insurance']=$answers;
    $this->column+=2;
  }
  
  public function createWorkPlaceList($sheet) {
    global $sql;
    $this->row = 1;
    $columnName = $this->getCurrentColumn(0);
    $columnRange = $this->getCurrentColumn(1);
    $sheet->setCellValue($columnName.$this->row,"workPlaceName");
    $sheet->setCellValue($columnRange.$this->row,"workPlaceRange");
    $this->row++;
    $range = $columnName.$this->row; 
    $sql->query("select * from workPlaces order by workPlaceName ASC");
    while($sql->get_assoc()) {
      $id=$sql->get_acol('workPlaceId');
      $name=$sql->get_acol('workPlaceName');
      $this->savedData['workPlace'][$id] = $name;
      $sheet->setCellValue($columnRange.$this->row,"workPlace$id");
      $sheet->setCellValue($columnName.$this->row,"$name");
      $this->workPlaces[$id] = $columnName.$this->row;
      $this->row++;
    }
    $this->row--;
    $range2 = $range.":".$columnRange.$this->row;
    $range.= ":".$columnName.$this->row; 
    $this->validation['workPlace']="dataValidation!$range";
    $this->validation['workPlaceSearch']="dataValidation!$range2";
    $this->answers['workPlace'] = "VLOOKUP({cell},dataValidation!$range2,2,0)";
    $this->column+=3;   
  }
  
  public function createComiteeList($sheet,$excel) {
    global $sql,$excel;
    $this->row = 1;
    $columnName = $this->getCurrentColumn(0);
    $columnRange = $this->getCurrentColumn(1);
    $sheet->setCellValue($columnName.$this->row,"comiteeName");
    $sheet->setCellValue($columnRange.$this->row,"comiteeID");
    $this->comitees = "$columnName,$columnRange";
    $this->row++;
    $comitees = Array();
    $sql->query("select * from comitees order by workPlaceId,comiteeName ASC");
    while($sql->get_assoc()) {
      $comitees[$sql->get_acol('workPlaceId')][]=$this->row;
      $this->savedData['comitee'][$sql->get_acol('comiteeId')] = $sql->get_acol('comiteeName');
      $sheet->setCellValue($columnName.$this->row,$sql->get_acol('comiteeName'));
      $sheet->setCellValue($columnRange.$this->row,$sql->get_acol('comiteeId'));
      $this->row++;
    }
    foreach($this->workPlaces as $workPlaceID => $workPlaceRow) {
      showError("array_key_exists($workPlaceID,$comitees) = ".array_key_exists($workPlaceID,$comitees));
      if(array_key_exists($workPlaceID,$comitees)) {
        $min = $comitees[$workPlaceID][0];
        $max = $comitees[$workPlaceID][count($comitees[$workPlaceID])-1]; 
        showError("$columnName$min - $columnName$max");
        $excel->addNamedRange(new PHPExcel_NamedRange("workPlace$workPlaceID",$sheet,"$columnName$min:$columnName$max"));
      } else {
        $excel->addNamedRange(new PHPExcel_NamedRange("workPlace$workPlaceID",$sheet,"$workPlaceRow"));
      }
    }
    $this->validation['comitee'] = "INDIRECT(VLOOKUP({cell},{$this->validation['workPlaceSearch']},2,0))";
    $this->answers['comitee']="VLOOKUP({cell},dataValidation!$columnName"."1:$columnRange"."{$this->row},2,0)";
    $this->column+=3;
  }
  
  public function createHorizontalValidation($sheet,$dataSheet,$rows=100,$headers=false) {
    $maxRows = $rows+$this->row;
    while($this->row<$maxRows) {
      $answerColumnID=0;
      foreach($this->answers as $validationID => $validationFormula) {
        $this->answerColumn = $this->columns[$answerColumnID]; 
        if(array_key_exists($validationID,$this->validation)) {
          if($validationID=='comitee') {
            $rcell=$this->validationColumn['workPlace'].$this->row;
          } else {
            $rcell=false;
          }
          $this->createValidation($sheet,$this->validationColumn[$validationID].$this->row,$this->validation[$validationID],$rcell);
        }
        $cell=$this->validationColumn[$validationID].$this->row;
        $headers = ($headers ? $validationID : false);
        $this->createAnswer($dataSheet,$cell,$validationFormula,$sheet,$headers);
        $answerColumnID++;
      }
      $this->row++;
      $headers = false;
    }  
  }
  
  private function createAnswer($sheet,$cell,$validation,$mainSheet,$headers) {
    if($headers) {
      $column = $cell[0];
      $sheet->setCellValue($this->answerColumn."1",$headers);    
    }
    if($cell) {
      if(!is_array($validation)) {
        $validation=preg_replace("/\{cell\}/","Anketa!$cell",$validation);
        $sheet->setCellValue($this->answerColumn.$this->row,"=IF(ISNA(".$validation.'),"",'.$validation.")");
      } else {
        $cells = spliti(",",substr($cell,0,strlen($cell)-1));
        foreach($validation as $column => $validation) {
          foreach($validation as $row => $validation) {
            $cell=array_shift($cells);
            $answers[] = preg_replace("/\{cell\}/","Anketa!".$cell.$this->row,$validation);
          }          
        }
        $sheet->setCellValue($this->answerColumn.$this->row,"=".implode("&",$answers));
      }
    }     
  }
  
  private function createValidation($sheet,$cell,$formula,$rcell) {
    if($rcell) {
      $formula = preg_replace("/\{cell\}/",$rcell,$formula);
    }
    $objValidation = $sheet->getCell($cell)->getDataValidation();
    $objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
    $objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
    $objValidation->setAllowBlank(true);
    $objValidation->setShowDropDown(true);
    $objValidation->setFormula1("=$formula");
  }
  
  public function createYesNo($sheet) {
    $this->row = 1;
    $columnYes = $this->getCurrentColumn(0);
    $columnYesID = $this->getCurrentColumn(1);
    $columnHave = $this->getCurrentColumn(2);
    $columnHaveID = $this->getCurrentColumn(3);
    $sheet->setCellValue($columnYesID.$this->row,"yesNoID");
    $sheet->setCellValue($columnYes.$this->row,"yesNo");
    $sheet->setCellValue($columnHaveID.$this->row,"haveDontID");
    $sheet->setCellValue($columnHave.$this->row,"haveDont");

    $this->row++;
    $sheet->setCellValue($columnYesID.$this->row,"n");
    $sheet->setCellValue($columnYes.$this->row,"Nē");
    $this->savedData['comiteePresident']['n'] = "Nē";
    $sheet->setCellValue($columnHaveID.$this->row,"n");
    $sheet->setCellValue($columnHave.$this->row,"Nav");
    $this->savedData['car']['n'] = "Nav";

    $this->row++;
    $sheet->setCellValue($columnYesID.$this->row,"y");
    $sheet->setCellValue($columnYes.$this->row,"Jā");
    $this->savedData['comiteePresident']['y'] = "Jā";
    $sheet->setCellValue($columnHaveID.$this->row,"y");
    $sheet->setCellValue($columnHave.$this->row,"Ir");
    $this->savedData['car']['y'] = "Ir";

    $this->validation['comiteePresident'] = "dataValidation!$columnYes"."2:$columnYes"."3";
    $this->validation['car'] = "dataValidation!$columnHave"."2:$columnHave"."3";
    $this->answers['comiteePresident']="VLOOKUP({cell},dataValidation!$columnYes"."2:$columnYesID"."3,2,0)";
    $this->answers['car']="VLOOKUP({cell},dataValidation!$columnHave"."2:$columnHaveID"."3,2,0)";
    $this->column+=5;
  }
  
  public function createState($sheet) {
    $this->row = 1;
    $columnID = $this->getCurrentColumn(1);
    $columnName = $this->getCurrentColumn(0);
    $sheet->setCellValue($columnID.$this->row,"stateID");
    $sheet->setCellValue($columnName.$this->row,"stateName");
    
    $this->row++;
    $sheet->setCellValue($columnID.$this->row,"act");
    $sheet->setCellValue($columnName.$this->row,"Aktīvs");
    $this->savedData['memberStatus']['act'] = "Aktīvs";

    $this->row++;
    $sheet->setCellValue($columnID.$this->row,"arh");
    $sheet->setCellValue($columnName.$this->row,"Arhīvs");
    $this->savedData['memberStatus']['arh'] = "Arhīvs";

    $this->row++;
    $sheet->setCellValue($columnID.$this->row,"ret");
    $sheet->setCellValue($columnName.$this->row,"Pensionārs");
    $this->savedData['memberStatus']['ret'] = "Pensionārs";
      
    $this->validation['memberStatus'] = "dataValidation!$columnName"."2:$columnName"."4";
    $this->answers['memberStatus']="VLOOKUP({cell},dataValidation!$columnName"."2:$columnID"."4,2,0)";
    $this->column+=3;
  }
  
  public function createAnswerHeader($sheet) {
    $this->row = 1;
    $columnInUse = 0;
    foreach($this->answers as $answerKey => $answerTitle) {
      ${"column$answerKey"} = $this->getCurrentColumn($columnInUse);
      $columnInUse++; 
      $sheet->setCellValue(${"column$answerKey"}.$this->row,$answerTitle);
      $this->columnsInUse[$answerKey]=${"column$answerKey"}; 
    }    
  }
  
  

  public function getCurrentColumn($index) {
    $newIndex = $this->column+$index;
    return $this->columns[$newIndex];
  }
}

$xls = new xls;

?>