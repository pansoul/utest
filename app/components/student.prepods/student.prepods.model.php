<?php

class StudentPrepodsModel extends UModel {
    
    private $table_user = 'u_user';
    
    private $arPost = array(
        'old_prepod'    => 'старший преподаватель',
        'docent'        => 'доцент',
        'prof'          => 'профессор',
        'prepod'        => 'преподаватель',
    );

    public function indexAction()
    { 
        $res = R::find($this->table_user, 'group_id IS NULL AND role != "admin" ORDER BY last_name');        
        
        return $this->returnResult(array(
            'form' => $res,
            'users' => $users
        ));
    }
    
    public function getArPost()
    {
        return $this->arPost;
    }

}