<?php

class AdminSettingModel extends UModel {
    
    const ID = 1;
    
    public function showAction()
    {        
        $data = R::load(TABLE_UNIVER_DATA, self::ID);
        
        if ($this->request->_POST['a']) {             
            $v = $this->request->_POST;                 
            $data->name = $v['univer_name'];
            $data->fullname = $v['univer_fullname'];
            $data->address = $v['address'];
            $data->phone = $v['~phone'];              
            $data->contacts = $v['contacts'];
            $data->info = $v['~info'];              
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
