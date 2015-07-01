<?php
ini_set("memory_limit","256M");


session_start();
include getcwd()."/includes/config.inc.php";
showError(mktime(0,0,0,6,12,2038));
?>
<!DOCTYPE html>
<html lang="en">
  <head>
  	<meta charset="utf-8">
  	<title>LDz</title>
  	<meta name="keywords" content="" />
  	<meta name="description" content="" />
  	<meta name="author" content="Vitalijs Harlamovs" >
  	<meta name="dcterms.rightsHolder" content="&copy; 2012 ENERGY.LV LTD.">
  	<meta name="dcterms.dateCopyrighted" content="2012">
   	<meta name="revisit-after" content="1 days">
   	<meta name="robots" content="noindex, nofollow">
   	<link rel="stylesheet" href="/css/bootstrap.css" type="text/css">
   	<link rel="stylesheet" href="/css/datepicker.css" type="text/css">
   	<link rel="stylesheet" href="/css/main_devel.css" type="text/css">
   	<script type="text/javascript" src="/js/jquery-1.8.3.min.js"></script>
  	<script type="text/javascript" src="/js/bootstrap.min.js"></script>
  	<script type="text/javascript" src="/js/jquery.form.js"></script>
  	<script type="text/javascript" src="/js/bootstrap-datepicker.js"></script>
  	<script type="text/javascript" src="/js/func.js"></script>
  	<script type="text/javascript" src="/js/main.js"></script>
  </head>
  <body>
    <div id="body-content">
      <?php 
      echo $templates->output;
      ?>
    </div>
    <div id='curtains'>
    </div>
    <div id='curtainsContainer' class='black-box success'>
      <div class='clear'></div>
    </div>
  </body>
</html>
