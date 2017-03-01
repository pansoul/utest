<?php

class UModel {
    
    public $errors = array();     
    public $request;    
    public $debugInfo;
    public $vars = array();
    
    public function __construct()
    {
        $this->request = new UHttpRequest();
    }

    public function indexAction()
    {
        return $this->returnResult(func_get_args());
    }
    
    final public function doAction($action, $args = array())
    {
        $method = $action . 'Action';        
     
        if (UBase::getConfig('debug > component_debug')) {
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            $this->debugInfo['do_action']['value'][] = array(
                'method' => $method,
                'file' => $caller['file'],
                'line' => $caller['line']
            );
            ob_start();        
            var_dump($args);  
            $this->debugInfo['do_action_args']['value'] = ob_get_clean();            
        }        
        
        return is_callable(array($this, $method))
            ? call_user_func_array(array($this, $method), (array)$args)
            : $this->returnResult(null);        
    }
    
    final public function returnResult($data = '')
    {
        return array(
            'errors' => $this->errors, 
            'data' => $data, 
            'request' => $this->request, 
            'vars' => $this->vars
        );
    }
}
