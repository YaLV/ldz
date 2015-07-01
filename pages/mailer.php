<?

if(count($_POST)>0) {

  $where = Array();
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
          } elseif(preg_match('/"sql"/',$filters[$k]['fieldInfo'])) {
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
  
  $emailTemplate = $_POST['emailTemplate'];
  include $_SERVER['DOCUMENT_ROOT']."/PHPmail/PHPMailerAutoload.php";  

  $listFields = "number,name,lastName,personalCode,email,phone,adress,workPlace,comitee,bench,cardCreateDate,issueDate,validTill";

  $sql1->query("select $listFields from biedri $where");
  
  while($sql1->get_assoc()) {
    $currentTemplate = $emailTemplate;
    foreach(explode(',',$listFields) as $fieldName) {
      $$fieldName = $sql1->get_acol($fieldName);
      str_replace("{".$fieldName."}",$$fieldName,$currentTemplate);
    }
    $emails = explode(",",$email);
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->SMTPDebug = 0;
    $mail->Debugoutput = 'html';
    
    $mail->Host = $config['mail']['host'];
    $mail->Port = $config['mail']['port'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['mail']['from'];
    $mail->Password = $config['mail']['password'];
    
    $mail->setFrom($config['mail']['from'], $config['mail']['fromName']);
    foreach($emails as $email) {
      $mail->addAddress(trim($email));
    } 

    $mail->Subject = $subjects[$language].$invoiceNumber;
    
    $HTML = nl2br($currentTemplate);
    
    $mail->msgHTML($HTML);

    if (!$mail->send()) {
        $mailError++;
    } else {
        $mailSuccess++;
    } 
    unset($mail);
  }
} else {
?>
<div class="filters export">
  <div class="filter_header export_header">
    <p>Eksports</p>
  </div>
  <div class="filter_selection">
    <form method="get" action="/xlsv?create&amp;type=v">
      <input type="hidden" name="export" value="1">
      <input type="hidden" name="create" value="1">
      <input type="hidden" name="type" value="v">
      <input type="hidden" name="displayFields" id="displayFields" value="" data-submit="gatherExportables">
      <table class="filter_table">
        <tbody><tr>
  <th><input type="checkbox" id="export_number" class="exportable" value="number" checked="checked" readonly="readonly"></th>
  <td>ID</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_name" class="exportable" value="name"></th>
  <td>Vārds</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_lastName" class="exportable" value="lastName"></th>
  <td>Uzvārds</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_personalCode" class="exportable" value="personalCode" checked="checked" readonly="readonly"></th>
  <td>Personas Kods</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_email" class="exportable" value="email"></th>
  <td>E-pasts</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_phone" class="exportable" value="phone"></th>
  <td>Telefons</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_adress" class="exportable" value="adress"></th>
  <td>Adrese</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_workPlace" class="exportable" value="workPlace"></th>
  <td>darbavieta</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_comitee" class="exportable" value="comitee" onclick="if($(this).is(&quot;:checked&quot;)) { $(&quot;#export_workPlace&quot;).prop(&quot;checked&quot;,true);}"></th>
  <td>komiteja</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_bench" class="exportable" value="bench"></th>
  <td>amats</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_comiteePresident" class="exportable" value="comiteePresident"></th>
  <td>prezis</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_standing" class="exportable" value="standing"></th>
  <td>kautkas</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_children" class="exportable" value="children"></th>
  <td>berni</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_accessionDate" class="exportable" value="accessionDate"></th>
  <td>datums1</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_car" class="exportable" value="car"></th>
  <td>auto</td>
</tr>

	<tr>
  <th><input type="checkbox" id="export_memberStatus" class="exportable" value="memberStatus"></th>
  <td>statuss</td>
</tr>

        <tr>
          <td colspan="2"><button class="pull-left">Eksportēt</button>&nbsp;<a class="btn btn-mini btn-warning pull-right" style="cursor:pointer;" onclick="$('.exportable').prop('checked',true);">Visi lauki</a></td>
        </tr>
      </tbody></table>
    </form>
  </div>
</div>
<?
}