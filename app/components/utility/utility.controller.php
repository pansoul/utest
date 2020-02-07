<?php

class UtilityController extends USiteController {    

    public function run()
    {   
        switch ($this->action) {
            case 'menu':
                $result = $this->model->doAction($this->action, array(USite::getGroup()));                
                $html = $this->loadView('menu', $result);
                break;
            
            case 'panel':
                $result = $this->model->doAction($this->action);                
                $html = $this->loadView('panel', $result);
                break;
            
            case 'univer':
                $result = $this->model->doAction($this->action, $this->actionArgs);                
                $html = $this->loadView($this->actionArgs[0], $result);
                break;
            
            case 'breadcrumb':
                $result = $this->model->doAction($this->action, $this->actionArgs);     
                $html = $this->loadView('breadcrumb', $result);
                break;
            
            case 'pastable':
                $result = $this->model->doAction($this->action, $this->actionArgs);     
                $html = $this->loadView('pastable', $result);
                break;
            
            case 'tabs':
                $result = $this->model->doAction($this->action, $this->actionArgs);     
                $html = $this->loadView('tabs', $result);
                break;
            
            case 'answerdisplay':
                $result = $this->model->doAction($this->action, $this->actionArgs);     
                $html = $this->loadView('answer_'.$this->actionArgs[0], $result);
                break;
            
            case 'testanswer':
                $result = $this->model->doAction($this->action, $this->actionArgs);     
                $html = $this->loadView('test_answer_'.$this->actionArgs[0], $result);                
                break;
            
            case 'testresult':
                $result = $this->model->doAction($this->action, $this->actionArgs);     
                $html = $this->loadView('test_result', $result);                
                break;
        }
        return $html;
    }
}
