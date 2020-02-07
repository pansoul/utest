<?php

class UModel {
    
    protected $errors = array(); 
    
    protected $request;

    public $vars = array();
    
    public function __construct()
    {
        $this->request = new UHttpRequest();
    }

    public function indexAction()
    {
        return $this->returnResult('Default Method');
    }
    
    final public function doAction($action, array $args = array())
    {
        $method = $action . 'Action';                
        if (!is_callable(array($this, $method)))            
            return $this->returnResult(null);
        else
            return call_user_func_array(array($this, $method), $args);
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
