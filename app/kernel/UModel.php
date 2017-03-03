<?php

class UModel {
    
    public $errors = array();  
    public $errorsCode;
    public $request;    
    public $debugInfo;
    public $vars = array();
    
    protected $watcherStatus = false;
    
    public function __construct()
    {
        $this->request = new UHttpRequest();
    }

    public function indexAction()
    {
        return $this->returnResult(func_get_args());
    }
    
    final public function doAction($action, $args = array(), $watcher = false)
    {
        $method = $action . 'Action';   
        $args = (array) $args;
        $this->setWatcherStatus($watcher);
     
        if (UBase::getConfig('debug > component_debug')) {
            ob_start();        
            echo "<pre>"; var_dump($args); echo "</pre>";
            $argsDebug = ob_get_clean();
            
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            $this->debugInfo['do_action']['value'][] = array(
                'method' => $method,
                'file' => $caller['file'],
                'line' => $caller['line'],
                'args' => $argsDebug
            );                        
        }        
        
        return is_callable(array($this, $method))
            ? call_user_func_array(array($this, $method), $args)
            : $this->returnResult(null);        
    }
    
    final public function returnResult($data = '')
    {
        $this->setWatcherStatus(0);
        return array(
            'errors' => $this->errors, 
            'errors_code' => $this->errorsCode,
            'data' => $data, 
            'request' => $this->request, 
            'vars' => $this->vars
        );
    }
    
    final public function setErrors($errorsMsg = array(), $errorCode = null)
    {
        $this->errors = $errorsMsg;
        $this->errorsCode = $errorCode;
    }
    
    final public function setWatcherStatus($flag)
    {
        $this->watcherStatus = (bool) $flag;
    }
}
