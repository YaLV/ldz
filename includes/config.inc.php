<?
DEFINE("DOCPATH", getcwd()."/");
DEFINE("INCPATH", getcwd()."/includes/");
DEFINE("TEMPLATE", getcwd()."/templates/");
DEFINE("PAGES", getcwd()."/pages/");

$config['sql']['host'] = "localhost";
$config['sql']['user'] = "adienerg_adi";
$config['sql']['pass'] = "parole";
$config['sql']['db']   = "adienerg_adi";

$config['language'] = Array();

$dateFields = array("cardCreateDate",'validTill','issueDate');

$config['menuItems']=Array(
  "biedri" => Array(
    'link' => "biedri",
    'name' => "Biedru saraksts",
    'file' => "biedri.php"
  ),
  "jauns_biedrs" => Array(
    'link' => "jauns_biedrs",
    'name' => "Pievienot Jaunu biedru",
    'file' => "jauns_biedrs.php"
  ),
  "fieldNames" => Array(
    'link' => "fieldNames",
    'name' => "Tabulu Konfigurācija",
    'file' => "tableConfig.php"
  ),
  "fieldOrder" => Array(
    'link' => "fieldOrder?table=biedri",
    'name' => "Tabulu atlases konfigurācija",
    'file' => "listOrder.php"
  ),
  "selectFields" => Array(
    'link' => 'selectFields',
    'name' => 'Izvēlnes lauku vērtības',
    'file' => 'addItems.php'
  ),

  "xlsv" => Array(
    'link' => "xlsv?create&type=v",
    'name' => "Izveidot Saraksta Anketu",
    'file' => "xls/xls.php",
    'spec' => "onclick=\"$(this).attr('href',$(this).attr('href')+'&lines='+prompt('Cik rindiņas ģenerēt?'));\""
  ),  
  "xlsinput" => Array(
    'link' => "xlsv?load",
    'name' => "Ielādēt Anketu",
    'file' => "xls.php"
  ),

  "logout" => Array(
    'link' => "logout",
    'name' => "Iziet no sistēmas",
    'spec' => "id='logout'"
  )
);

$config['listTables'] = Array(
  "comitees" => "Arodkomitejas",
  "workPlaces" => "Darba vietas"
);

$config['listItems'] = Array(
  'number' => 'Biedra Nr.',
  'name' => 'Vārds',
  'lastName' => 'Uzvārds',
  'personalCode' => 'Personas Kods',
  'email' => 'E-pasts',
  'phone' => 'Telefons',
  'adress' => 'Adrese',
  'workPlace' => 'Darba Vieta'
);

$config['sortables'] = Array('number','name','lastName');
$config['sorting']['defaultField']='number';
$config['sorting']['defaultOrder']='ASC';
$_SESSION['filter']=Array();
include INCPATH."/sql.inc.php";
include INCPATH."/check.inc.php";
include INCPATH."/templates.inc.php";
include INCPATH."/functions.inc.php";

getPageTexts();
include INCPATH."/parser.inc.php";

