<?
class sql_dummy{
 var $handle;			// PHP's sql-drivers handle
 var $cursor;			// Cursor
 var $supp;			// Supported features
 var $name,$vers;		// Driver name and version
 var $row_count;		// Number of selected rows/columns
 var $cos_count;		// Number of selected columnts
 var $cur_row;			// Current row
 var $cur_col;			// Current col
 function sql_dummy(){$name='Dummy';$vers='0.0';}
 function dummy($func){warning("$func not supported by $name/$vers)");}
 function connect($db,$host="localhost",$user="",$pass=""){dummy('connect');}
 function disconnect(){$this->dummy('disconnect');}
 function query($query){$this->dummy('query');}
 function get_row($index=-1){$this->dummy('get_row');}
 function get_col($index=-1){$this->dummy('get_col');}
 function errno(){$this->dummy('errno');}
 function errstr(){$this->dummy('errstr');}
 function perror($string){/*error("SQL error: ".$string);*/return 0;}
 function show_databases($where=false){$this->dummy('show_databases');}
 function show_tables($where=false){$this->dummy('show_tables');}
 function rollback(){$this->dummy('rollback');}
 function commit(){$this->dummy('commit');}
}

function load_driver($drv){
# Extend sql functionality by loading module needed for sql_$drv class
# if(strtok($drv,'./'))return false;  ... Do we need security?
# include 'sql-c/'.$drv.'.php';
}

class sql_mysql extends sql_dummy {
 var $row,$cur_row,$cur_col;			// Current col

 function sql_mysql(){
  $this->handle=0;
  $this->row=0;
  $this->name='MySql';
  $this->vers='0.0';
 }

 function connect($db,$host="localhost",$user="",$pass=""){
  if(!($this->handle=mysql_connect($host,$user,$pass)))return perror('connect');
  mysql_select_db($db,$this->handle);
  return true;
 }

 function disconnect(){
  return true;
 }

 function query($query){
  if(!$this->cursor=mysql_query($query,$this->handle))return $this->perror('query('.$this->errstr().'): '.$query);
  return true;
 }

 function query_rez($query) {
  if(!$this->cursor=mysql_query($query,$this->handle)) return $this->perror('query('.$this->errstr().'): '.$query);
  return mysql_num_rows($this->cursor);
}

 function get_row($index=-1){
  $this->cur_col=0;
  if($index>-1)mysql_data_seek($this->cursor,$index);
  if($this->row=mysql_fetch_row($this->cursor))return true;
  return false;
 }

 function get_assoc($index=-1){
  $this->cur_col=0;
  if($index>-1)mysql_data_seek($this->cursor,$index);
  if($this->row=mysql_fetch_assoc($this->cursor))return true;
  return false;
 }

 function get_col($index=-1){
  if($index>-1){
   $this->cur_col=$index;
  }
  $out=htmlspecialchars_decode($this->row[$this->cur_col]);
  $this->cur_col++;
  return $out;
 }

 function get_colu($index=-1){
  if($index>-1){
   $this->cur_col=$index;
  }
  $out=$this->row[$this->cur_col];
  $this->cur_col++;
  return $out;
 }

 function get_acol($index){
  if($index>-1){
   $this->cur_col=$index;
  }
  $out=$this->row[$this->cur_col];
  return $out;
 }

 function errno(){
  return mysql_errno();
 }

 function errstr(){
  return mysql_error();
 }
}

function db_conn($t=''){
 global ${'sql'.$t},$config;
 if(${'sql'.$t})return ${'sql'.$t};
 load_driver('mysql');
 ${'sql'.$t}=new sql_mysql();
 ${'sql'.$t}->connect($config['sql']['db'],$config['sql']['host'],$config['sql']['user'],$config['sql']['pass']);
 ${'sql'.$t}->query("set names utf8"); 
 ${'sql'.$t}->query("set character set utf8"); 
 return ${'sql'.$t};
}
$sql=db_conn();

function error($what)
{
 echo "<font color=red>$what</font>";
}

function get_reply($query)
{
	global $sql;
	$connection=$sql;
	$connection->query($query);
	$connection->get_row();
	Return $connection->get_col();
}
function get_replyz($query)
{
	global $sql;
	$connection=$sql;
	$connection->query($query);
	$connection->get_row();
	Return $connection->get_colu();
}

?>
