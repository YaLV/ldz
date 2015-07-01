<?
$templates->values['center']='aaa';

$XLSver="1";

include getcwd()."/ExcelRead/PHPExcel.php";

$sql1=db_conn('1');      

function createValidation($sheet,$range,$cell,$owner=false) {
  if(is_array($range)) {
    $range = "{$range['column']}{$range['start']}:{$range['column']}{$range['end']}";
  }
  $objValidation = $sheet->getCell($cell)->getDataValidation();
  $objValidation->setType( PHPExcel_Cell_DataValidation::TYPE_LIST );
  $objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
  $objValidation->setAllowBlank(true);
  $objValidation->setShowDropDown(true);
  if(!$owner) {
    $objValidation->setFormula1("dataValidation!$range");
  } else { 
    $objValidation->setFormula1("=INDIRECT($owner)");
  }
  return $objValidation;
}

if(!isset($_GET['create'])) {
  if(!isset($_GET['load'])) {
    if(!get_reply("select count(*) from biedri where personalCode=''")>0) {
      ob_start();
      print_r($_FILES);
      showError("UploadedFile: ".ob_get_clean());
      $file = file_get_contents($_FILES['applications']['tmp_name']);
      showError("Data Read: ".$file);
      $fileData = spliti("base64,",$file);
      if($fileData[1]!='') {
        $file = base64_decode($fileData[1]);
      } 
      $fh = fopen(getcwd()."/up.xlsx","w");
      fwrite($fh,$file);
      fclose($fh);
      $excel = PHPExcel_IOFactory::createReader('Excel2007');
      $excel->setLoadAllSheets();
      $uploaded = getcwd()."/up.xlsx";
      $excelFile = $excel->load($uploaded);
      $excelFile->setActiveSheetIndex(1);
      $validationSheet = $excelFile->getSheet(1);
      $XLSversion = $validationSheet->getCell("V100")->getValue(); 
      
      
      $orientation = $validationSheet->getCell("Z10")->getValue();
      
      $vertical = $orientation=="vertical" ? true : false;
      
      $currentRow=1;   
      $workPlaces = Array();                     
      while($cellValue = $validationSheet->getCell("A$currentRow")!='') {
        list($key,$value) = Array($validationSheet->getCell("A$currentRow")->getValue(),$validationSheet->getCell("B$currentRow")->getValue());
        $workPlaces[$key] = $value; 
        $currentRow++; 
      }
      
      $currentRow+=5;
      
      while($cellValue = $validationSheet->getCell("A$currentRow")->getValue()!='') {
        list($key,$value1,$value2) = Array($validationSheet->getCell("A$currentRow")->getValue(),$validationSheet->getCell("B$currentRow")->getValue(),$validationSheet->getCell("C$currentRow")->getValue());
        $irnav[$key] = $value1;
        $jane[$key] = $value2; 
        $currentRow++; 
      }
      
      if($XLSversion>='1') {
        $currentRow+=4;
        
        while($cellValue = $validationSheet->getCell("A$currentRow")->getValue()!='') {
          list($key,$value1) = Array($validationSheet->getCell("A$currentRow")->getValue(),$validationSheet->getCell("B$currentRow")->getValue());
          $activity[$key] = $value1;
          $currentRow++; 
        }
      }
      
      $currentRow+=4;
          
      while($cellValue = $validationSheet->getCell("A$currentRow")!='') {
        list($key,$value) = Array($validationSheet->getCell("A$currentRow")->getValue(),$validationSheet->getCell("B$currentRow")->getValue());
        $comitees[$key] = $value; 
        $currentRow++; 
      }
      
      $sheet = $excelFile->getSheet(0);
        
      $currentRow=4;
      
      if($vertical) {
        if($XLSVersion>='1') {
          $columns="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        } else {
          $columns="ABCDEFGHIJKLMNOPQRSTUVWXYZ";        
        }
        $currentColumn = 0;
        
        while($sheet->getCell("{$columns[$currentColumn]}1")->getValue()!='') {
          $value = $sheet->getCell("{$columns[$currentColumn]}1")->getValue();
          $DBkeys[$columns[$currentColumn]] = $value;
          $sql1->query("show full columns from biedri where Field='$value'");
          $sql1->get_assoc();
          $json = $sql1->get_acol('Comment'); 
          $dataType = json_decode($json,true);
          if($dataType['type']=='checkbox') {
            //echo count($dataType['value']);
            $currentColumn+=count($dataType['value'])-1;
          } else {
            $currentColumn++;
          } 
        }
        
        $currentRow = 4;
        
        //$cc = ($XLSVersion>='1' ? "B" : "A");
        $cc="B"; 
        $maxRows = $sheet->getHighestRow();
        while($maxRows>=$currentRow) {
          if($sheet->getCell("$cc".$currentRow)->getValue()=='') { break; }         
          $formData[$currentRow]=Array();
          foreach($DBkeys as $currentColumn => $fieldName) {
            $sql1->query("show full columns from biedri where Field='$fieldName'");
            $sql1->get_assoc();
            $dataType = json_decode($sql1->get_acol('Comment'),true);
            if($dataType['type']=='checkbox') {
              $answerCount = count($dataType['value']);
              $origColumn = $currentColumn;
              for($x=0;$x<$answerCount;$x++) {
                $y=$x+1;
                $haveIt = $sheet->getCell($currentColumn.$currentRow)->getValue();
                if($haveIt!='') {
                  $insurances[]= $sheet->getCell("dataValidation!I".$y)->getValue();
                }
                $currentColumn++; 
              }  
              $formData[$currentRow][$origColumn].=implode(",",$insurances);
              unset($insurances);
            } elseif($dataType['value']=='sql') {
              $currentValue = $sheet->getCell($currentColumn.$currentRow)->getValue();
              $keys = array_search($currentValue,${$dataType['table']},true);
              preg_match("/(\d+)/",$keys,$key);
              if(is_array($key)) { 
                $key = $key[1];
              } else {
                $key = $currentValue;
                $error[$currentRow][$currentColumn] = true;
              }
              $formData[$currentRow][$currentColumn]=$key;
            } elseif($dataType['type']=='radio') {
              $radioValue = $sheet->getCell($currentColumn.$currentRow)->getValue();
              if(in_array($radioValue,$irnav)) { $radioValue = array_search($radioValue,$irnav); }
              if(in_array($radioValue,$jane)) { $radioValue = array_search($radioValue,$jane); }
              if(in_array($radioValue,$activity)) { $radioValue = array_search($radioValue,$activity); }
              $formData[$currentRow][$currentColumn] = $radioValue;  
            } elseif($dataType['type']=='custom') {
              unset($kidz);
              $currentValue = $sheet->getCell($currentColumn.$currentRow)->getValue();
              if($currentValue!='') {
                if(preg_match_all("/(\d\d\.\d\d\.\d\d\d\d)/",$currentValue,$kidz)) {
                  $formData[$currentRow][$currentColumn] = implode(",",$kidz[1]);
                } else {
                  $formData[$currentRow][$currentColumn] = $currentValue;
                  $error[$currentRow][$currentColumn] = true;
                } 
              } else {
                $formData[$currentRow][$currentColumn] = "";
              } 
            } else {
              $currentValue = $sheet->getCell($currentColumn.$currentRow)->getValue();
              $isError = false;
              switch($fieldName) {
                case "personalCode":
                  if(!preg_match("/^\d\d\d\d\d\d-[12]\d\d\d\d$/",$currentValue)) {
                    //$currentValue = "error";
                    $isError = true;
                  }
                break;
              }
              if($isError) {          
                $error[$currentRow][$currentColumn] = true;
              }
              $formData[$currentRow][$currentColumn]= $currentValue;
            }
          }
          $currentRow++;      
        }
      }
      
      
    
      $keysInsert = implode(",",$DBkeys); 
      
      $row[] = "<td>".implode("</td><td>",$DBkeys)."</td>";  
      foreach($formData as $rinda => $value) {
        $bnum=$value[array_search('number',$DBkeys)];
        if($bnum!='') {
          $number = $bnum; 
        } else {
          $personalCode = $value[array_search('personalCode',$DBkeys)];
          $number = get_reply("select number from biedri where personalCode='$personalCode'");
        }
        if(!$number) {
          $values = "'".implode("','",$value)."'";
          $sql1->query("insert into biedri ($keysInsert) values($values)");
          $number = get_reply("select last_insert_id() from biedri");
        } else {
          foreach($DBkeys as $key => $keyz) {
            $keysUpdate[] = "$keyz='{$value[$key]}'";
            $fieldValues[$keyz] = $value[$key]; 
          }
          $sql1->query("update biedri set ".implode(",",$keysUpdate)." where number = $number");
          writeChangeLog($fieldValues,$number,$_SESSION['uname'].'-import');
        }
        foreach($value as $k => $v) {
          if($error[$rinda][$k]) {
            echo $DBkeys[$k].": Kļūda, lauka vērtība: $v lietotājam $number<br />";
            $sql1->query("insert into inputErrors values('','$number','{$DBkeys[$k]}','$v')");
            $sql1->query("update biedri set {$DBkeys[$k]}='' where number=$number");
          }
        }
        $row[] = "<td>".implode("</td><td>",$value)."</td>";
      }
    
      $rows = "<tr>".implode("</tr><tr>",$row)."</tr>";
    
      echo "<table>$rows</table>";
      //unlink(getcwd()."/up.xlsx");  
      exit();
    } else {
      echo "Lūdzu izlabojiet kļūdas personu sarakstā.";
      exit();
    }
  } else {
    $templates->values['requesturi']="/".$_GET['section'];
    $templates->template="formLoad.php";
    $templates->parseNormalOutput();
    $templates->values['center']=$templates->output;
  }  
} elseif(isset($_GET['create'])) {
  if(!$_GET['export']) {
    $displayFields="all";
  } else {
    $displayFields=$_GET['displayFields'];
  } 
  $vertical = $_GET['type']=='v' ? true : false;
  $excel = new PHPExcel();
  $excel->createSheet(1);
  $excel->setActiveSheetIndex(1);
  $sheet = $excel->getSheet(1);
  $sheet->setTitle("dataValidation");
  
  $sheet->setCellValue("V100",$XLSver);
  
  $currentRow=1;
  $workPlaces['start']=1;
  $orient = $vertical ? "vertical" : "horizontal";
  $sheet->setCellValue("Z10",$orient);
  $sql1->query("select * from workPlaces order by workPlaceName ASC"); 
  while($sql1->get_assoc()){                
    $sheet->setCellValue("A$currentRow","workPlace".$sql1->get_acol('workPlaceId'))->setCellValue("C$currentRow","workPlace".$sql1->get_acol('workPlaceId'));;
    $sheet->setCellValue("B$currentRow",$sql1->get_acol('workPlaceName'));
    $workPlaceIds[$sql1->get_acol('workPlaceId')]=$currentRow;
    $currentRow++;
  }
  $workPlaces['end']=$currentRow-1;
  $workPlaces['column']="B";
  $currentRow+=5;
  $rowYes['start'] = $currentRow;
  $sheet->setCellValue("A$currentRow","y");
  $sheet->setCellValue("B$currentRow","Ir");
  $sheet->setCellValue("C$currentRow","Jā");
  $currentRow++;
  $sheet->setCellValue("A$currentRow","n");
  $sheet->setCellValue("B$currentRow","Nav");
  $sheet->setCellValue("C$currentRow","Nē");
  $rowYes['end'] = $currentRow;
  
  $currentRow+=5;
  
  $activity['start'] = $currentRow;
  $sheet->setCellValue("A$currentRow","act");
  $sheet->setCellValue("B$currentRow","Aktīvs");
  $currentRow++;
  $sheet->setCellValue("A$currentRow","arh");
  $sheet->setCellValue("B$currentRow","Arhīvs");
  $currentRow++;
  $sheet->setCellValue("A$currentRow","ret");
  $sheet->setCellValue("B$currentRow","Pensionārs");
  $activity['end'] = $currentRow;
  
  
  $currentRow+=5;
  $comitees['start']=$currentRow;
  $sql1->query("select * from comitees order by workPlaceId,comiteeName ASC");
  while($sql1->get_row()) {
    $id = $sql1->get_col();
    $wid = $sql1->get_col();
    $name = $sql1->get_col();
    if(!$owid) { $owid=$wid; }
    $sheet->setCellValue("A$currentRow",$id);
    $sheet->setCellValue("B$currentRow",$name);
    if($wid!=$owid) {
      $name = "workPlace$owid";
      $currentPrevRow = $currentRow-1;
      $excel->addNamedRange(new PHPExcel_NamedRange($name,$sheet,"B{$comitees['start']}:B$currentPrevRow"));
      $owid = $wid;
      $comitees['start']=$currentRow;
    }
    $currentRow++;
  }
  
  if($comitees['start']!=$currentRow) {
    $name = "workPlace$owid";
    $currentPrevRow = $currentRow-1;
    $excel->addNamedRange(new PHPExcel_NamedRange($name,$sheet,"B{$comitees['start']}:B$currentPrevRow"));
  }
  
  foreach($workPlaceIds as $key => $x) {
    $name = "workPlace$key";
    if($excel->getNamedRange($name)==null) {
      $excel->addNamedRange(new PHPExcel_NamedRange($name,$sheet,"B$x"));
    }
  }
 
  $comitees['end']=$currentRow-1;
  $comitees['column']="B";

  $Columns="ABCDEFGHIJKLMNOPQRSTUVWXYZ";

  $excel->getSheetByName('dataValidation')->setSheetState(PHPExcel_Worksheet::SHEETSTATE_HIDDEN);
  $excel->setActiveSheetIndex(0);
  $sheet = $excel->getSheet(0);
  $sheet->setTitle("Anketa");
  $validSheet = $excel->getSheet(1);
  if(!$vertical) {
    $currentRow=1;
    $sheet->getColumnDimension('B')->setWidth(23);
    $sheet->getColumnDimension('C')->setWidth(35);
    $sheet->getColumnDimension('A')->setVisible(false);
    $sheet->getRowDimension('1')->setVisible(false);
  } else {
    $currentRow=0;
    $sheet->getColumnDimension('A')->setVisible(false);
    $sheet->getRowDimension('1')->setVisible(false);
  }
  $sql1->query("show full columns from biedri");
  while($sql1->get_assoc()) {
    $preset = false;
    $field = $sql1->get_acol('Field');
    $fieldName = get_reply("select `value` from languages where `key`='$field'");
    $options = json_decode($sql1->get_acol('Comment'));
    if($displayFields!='all' && strpos($displayFields,$field) === false) {
      // not displaying
    } else {
      if($options->showInForm=='y') {
        if(!$vertical) {
          $sheet->setCellValue("A$currentRow",$field)
                ->setCellValue("B$currentRow",$fieldName);
        } else {
          $fieldz[$Columns[$currentRow]]=$field;
          $opts[$Columns[$currentRow]]=$options;
          if($options->type=='checkbox') {
            //$cells = count($options->value);
            $cells = 3;
            $nextCurrentRow=$currentRow+$cells;
            $sheet->setCellValue("{$Columns[$currentRow]}1",$field);
            $sheet->setCellValue("{$Columns[$currentRow]}2",$fieldName);
            $sheet->mergeCells("{$Columns[$currentRow]}2:{$Columns[$nextCurrentRow]}2");
            $sheet->getStyle("{$Columns[$currentRow]}2")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
          } else {
            $sheet->setCellValue("{$Columns[$currentRow]}1",$field)
                  ->setCellValue("{$Columns[$currentRow]}3",$fieldName);          
          }
        }
        if($options->formComment && !$vertical) {
          $sheet->setCellValue("D$currentRow",$options->formComment);
        }
        if($options->type=='checkbox') {
          $presetFormData='';
          $currentRowCheck = $currentRow;
          $line=0;
          foreach($options->value as $key => $value) {
            $line++;
            $validSheet->setCellValue("I".$line,$key);
            if($vertical) {
              $sheet->setCellValue("{$Columns[$currentRow]}3",$value." $currentRow");
              $sheet->getColumnDimension($Columns[$currentRow])->setAutoSize(true);
              $currentRow++;            
            } else {
              $presetFormData.="$value: \r\n";
            }
          }
          if(!$vertical) {
            $sheet->setCellValue("C$currentRow",$presetFormData)->getStyle("C$currentRow")->getAlignment()->setWrapText(true);
          } else {
            $currentRow--;
          }
        }
        if($options->formHeight && !$vertical) {
          $sheet->getRowDimension("$currentRow")->setRowHeight($options->formHeight);
        }
        if($options->value) {
          if(is_object($options->value)) {
            if($options->value->y=='Ir') {
              $rowYes['column']="B";
              $cell = $vertical ? "{$Columns[$currentRow]}4" : "C$currentRow";
              $valid = createValidation($sheet,$rowYes,$cell);
              $validation[]="clone:$cell";
              $clone[$cell]= $rowYes;
            } elseif($options->value->y=='Jā') {
              $rowYes['column']="C";
              $cell = $vertical ? "{$Columns[$currentRow]}4" : "C$currentRow";
              createValidation($sheet,$rowYes,$cell);
              $validation[]="clone:$cell";
              $clone[$cell]= $rowYes;
            } elseif($options->value->act=='Aktīvs') {
              $activity['column']="B";
              $cell = $vertical ? "{$Columns[$currentRow]}4" : "C$currentRow";
              $valid = createValidation($sheet,$activity,$cell);
              $validation[]="clone:$cell";
              $clone[$cell] = $activity; 
            } 
          } elseif($options->value=='sql') {
              $x=4;
              $cell = $vertical ? "{$Columns[$currentRow]}$x" : "C$currentRow";
              $preset = true;
              if($options->owner) {
                $sheet->getColumnDimension($Columns[$currentRow])->setWidth(31);
                createValidation($sheet,${$options->table},$cell,${$options->owner});
                $validation[]="inc:$cell";
                $clone[$cell]= ${$options->owner."se"};
              } else {
                $sheet->getColumnDimension($Columns[$currentRow])->setWidth(33);
                createValidation($sheet,${$options->table},$cell);
                $s = ${$options->table}['start'];
                $e = ${$options->table}['end'];
                //${$field} = 'LOOKUP(,datavalidation!B'.$s.':B'.$e.',datavalidation!A'.$s.':A'.$e.')';
                ${$field} = 'VLOOKUP('.$cell.',dataValidation!B'.$s.':C'.$e.',2,0)';
                ${$field."se"} = "{$Columns[$currentRow]}:$$s:$e";
                $validation[]="clone:$cell";
                $clone[$cell]= ${$options->table};
            }
          }
        }
        if(!$preset) {
          $sheet->getColumnDimension($Columns[$currentRow])->setWidth(23);      
        }
        $currentRow++;            
      }
    }
    $value = '';
  }
  
  if($vertical && !$_GET['export']) {
    foreach($validation as $cell) {
      list($type,$oldCell) = explode(":",$cell); 
      $column = $oldCell[0];
      $row = $oldCell[1];
      for($x=1;$x<=100;$x++) {
        $row++;
        if($type == 'clone') {
          createValidation($sheet,$clone[$oldCell],$column.$row);
        } else {
          list($col,$s,$e) = explode(":",$clone[$oldCell]);
          createValidation($sheet,'',$column.$row,'VLOOKUP('.$col.$row.',dataValidation!B'.$s.':C'.$e.',2,0)');
          //createValidation($sheet,'',$column.$row,'LOOKUP('.$col.$row.',datavalidation!B'.$s.':B'.$e.',datavalidation!A'.$s.':A'.$e.')');
        }
      }    
    }
  }
  
  if($_GET['export']) {
    $thisRow = 4;
    $fieldList = implode(",",$fieldz);
    $sortOrder = ($_SESSION['sorting']['order']=='ascending' ? "ASC" : "DESC");
    foreach($_SESSION['filters'] as $k => $v) {
      if(strlen($v)>0) {
        if($k!='insurances') {
          $where[]=$k." like '%$v%'";
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

    $sql2=db_conn('2');
    $sql1->query("select $fieldList from biedri $where order by {$_SESSION['sorting']['field']} $sortOrder");
    while($sql1->get_assoc()) {
      foreach($fieldz as $k => $v) {
        $val = $sql1->get_acol($v);
        $curOpts = $opts[$k];
        if($curOpts->value=='sql') {
          $idd = explode(",",$curOpts->columns);
          $sql2->query("select {$curOpts->columns} from {$curOpts->table} where {$idd[0]}='$val'");
          $sql2->get_assoc();
          $val = $sql2->get_acol($idd[count($idd)-1]);
          $sheet->setCellValue($k.$thisRow,$val);
        } elseif($curOpts->type=='checkbox') {
          $kk=strpos($Columns,$k);
          foreach($curOpts->value as $key => $value) {
            if(strpos($val,$key)!==false) {
              $sheet->setCellValue($Columns[$kk].$thisRow,'+');
            }
            $kk++;            
          }
        } elseif($curOpts->type=='radio') {
          if($val!='') {
            $sheet->setCellValue($k.$thisRow,$curOpts->value->{$val});
          }
        } else {
          $sheet->setCellValue($k.$thisRow,$val);
        } 
        foreach($validation as $cell) {                                                       
         list($type,$oldCell) = explode(":",$cell); 
         $column = $oldCell[0];
         $row = $oldCell[1];
         if($k==$column) {
            if($type == 'clone') {
              createValidation($sheet,$clone[$oldCell],$k.$thisRow);
            } else {                                               
              list($col,$s,$e) = explode(":",$clone[$oldCell]);
              createValidation($sheet,'',$k.$thisRow,'VLOOKUP('.$col.$thisRow.',dataValidation!B'.$s.':C'.$e.',2,0)');
            }
          }
        }
      }
      $thisRow++;
    }  
  }  

  if(!$vertical) {
    $sheet->getStyle("B1:B100")->getFont()->setBold("true");
    $sheet->getStyle("B1:D100")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_TOP);
    $currentRow--;
    $styleArray = array(
         'borders' => array(
               'allborders' => array(
                      'style' => PHPExcel_Style_Border::BORDER_THIN,
               ),
         ),
    );
    $sheet->getStyle("B1:C$currentRow")->applyFromArray($styleArray);
  } else {
    $currentRow--;
    $sheet->getStyle("{$Columns[0]}2:{$Columns[$currentRow]}3")->getFont()->setBold("true");
    $sheet->getStyle("{$Columns[0]}2:{$Columns[$currentRow]}3")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $styleArray = array(
         'borders' => array(
               'allborders' => array(
                      'style' => PHPExcel_Style_Border::BORDER_THIN,
               ),
         ),
    );
    $sheet->getStyle("{$Columns[0]}2:{$Columns[$currentRow]}3")->applyFromArray($styleArray);
  }
  //$sheet->getProtection()->setSheet(true);
  //$sheet->protectCells("A1:B100", 'PHPExcel'); 
  header("Content-Type: application/vnd.ms-excel");
  $fn = $vertical ? "Saraksts" : "Anketa";
  header('Content-Disposition: attachment;filename="'.$fn.'.xlsx"');
  header('Cache-Control: max-age=0');
  $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
  $writer->setPreCalculateFormulas(FALSE);
  //$writer->save(getcwd()."/anketa.xlsx");
  $writer->save('php://output');
  exit();
}