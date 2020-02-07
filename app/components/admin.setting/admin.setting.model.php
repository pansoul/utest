<?php

class AdminSettingModel extends UModel {
    
    const ID = 1;
    private $table = 'u_univer_data';    
    
    public function showAction()
    {        
        $data = R::load($this->table, self::ID);
        
        if ($this->request->_post['a']) { 
            $v = $this->request->_post;            
            $data->name = $v['univer_name'];
            $data->fullname = $v['univer_fullname'];
            $data->address = $v['address'];
            $data->phone = $v['phone'];  
            if (R::store($data)) {
                $_SESSION['update'] = 'Y';
                USite::redirect (USite::getModurl());
            }
        }
        return $this->returnResult(array(
            'univer_name' => $data->name,
            'univer_fullname' => $data->fullname,
            'address' => $data->address,
            'phone' => $data->phone,
        ));
    }    
}
