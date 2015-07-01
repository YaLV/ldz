<?
class templates {
  
  public $page,$output,$template,$errors;
  public $values = Array();
  private $templateData;
  
  public function parseNormalOutput() {
    global $sql;
    $templateResult = $this->readTemplate();
    if($templateResult) {    
      $this->fillTemplate();
    } else {
      
    }
    $this->output = $this->templateData;
  }
  
  public function readTemplate() {
    if(!file_exists(TEMPLATE.$this->template)) return false;
    $file_length = filesize(TEMPLATE.$this->template) or showError(); 
    $file_handler = fopen(TEMPLATE.$this->template,'r') or showError();
    $this->templateData=fread($file_handler,$file_length);
    fclose($file_handler);
    return true;
  }
  
  private function fillTemplate() {
    global $config;
    $this->templateData = preg_replace_callback("/(\{[^}\s]+\})/","languageCallback",$this->templateData);
  }
  
  public function parsePostResponse() {
    global $sql;
    $this->output=Array();
    if($this->errors=='') {
      $this->output['messageType']=get_reply("select messageType from messages where messageID='{$this->values['message']}'");
      $this->output['message'] = get_reply("select message from messages where messageID='{$this->values['message']}'");
      if($this->output['messageType']!='error') {
        $_SESSION['info'] = $this->output;
        $this->output['action']="document.location='{$this->values['returnTo']}';";
      }
    } else {
      $this->output['messageType']='error';
      $this->output['message'] = $this->errors;
    }
    echo json_encode($this->output);
    exit();
  } 
}
$templates = new templates;
?>