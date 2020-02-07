<?php

class UHttpRequest {
    
    /**
     * $_GET массив
     * @var array
     */
    public $_get;
    
    /**
     * $_POST массив
     * @var array
     */
    public $_post;
    
    /**
     * $_REQUEST массив
     * @var array
     */
    public $_requset;
    
    public function __construct()
    {
        $this->_get = $this->get();
        $this->_post = $this->post();
        $this->_requset = $this->request();
    }
    
    /**
     * Возвратит весь массив $_GET с преобразованными элементами в безопасном виде
     * @return array
     */
    private function get()
    {
        return $this->convert2safe($_GET);
    }

    /**
     * Возвратит весь массив $_POST с преобразованными элементами в безопасном виде
     * @return array
     */
    private function post()
    {
        return $this->convert2safe($_POST);
    }

    /**
     * Возвратит весь массив $_REQUEST с преобразованными элементами в безопасном виде
     * @return array
     */
    private function request()
    {
        return $this->convert2safe($_REQUEST);
    }
    
    /**
     * Возвращает элемент из суперглобального массива $_SERVER
     * 
     * Если параметр $key опущен, то вернётся весь массив $_SERVER
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getServer($key = null, $default = null)
    {
        if (null === $key) {
            return $_SERVER;
        }
        return (isset($_SERVER[$key])) ? $_SERVER[$key] : $default;
    }

    /**
     * Проверяет, является ли данный запрос ajax-запросом
     * @return bool
     */
    public function isAjaxRequest()
    {
        return( $this->getServer('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest' );
    }

    /**
     * Получает IP адрес пользователя
     * @param boolean $checkProxy
     * @return string
     */
    public function getClientIp($checkProxy = true)
    {
        if ($checkProxy && $this->getServer('HTTP_CLIENT_IP') != null) {
            $ip = $this->getServer('HTTP_CLIENT_IP');
        } elseif ($checkProxy && $this->getServer('HTTP_X_FORWARDED_FOR') != null) {
            $ip = $this->getServer('HTTP_X_FORWARDED_FOR');
        } else {
            $ip = $this->getServer('REMOTE_ADDR');
        }
        return $ip;
    }
    
    /**
     * Возвращает переданную переменную в безопасном виде
     * @param mixed $str
     * @return mixed
     */
    public static function convert2safe($str)
    {        
        switch (gettype($str)) {
            case 'array':
                $arClean = array();
                foreach ($str as $k => $v)
                {
                    $arClean[$k] = self::convert2safe($v);
                }
                return $arClean;
                break;
                
            case 'string':                                
                return get_magic_quotes_gpc() 
                    ? htmlspecialchars(stripslashes(trim($str)))
                    : htmlspecialchars(trim($str));
                break;
            
            default:
                return $str;
        }
    }
}