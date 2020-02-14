<?php

namespace UTest\Kernel;

class HttpRequest
{
    const GET = '_GET';
    const POST = '_POST';
    const REQUEST = '_REQUEST';

    /**
     * $_GET массив
     * @var array
     */
    private $_GET;

    /**
     * $_POST массив
     * @var array
     */
    private $_POST;

    /**
     * $_REQUEST массив
     * @var array
     */
    private $_REQUEST;

    public function __construct()
    {
        $this->_GET = $this->get();
        $this->_POST = $this->post();
        $this->_REQUEST = $this->request();
    }

    /**
     * Возвратит весь массив $_GET с преобразованными элементами в безопасном виде
     * @return array
     */
    private function get()
    {
        $safe = $this->convert2safe($_GET);
        $original = array();
        foreach ($_GET as $key => $value) {
            $value = get_magic_quotes_gpc() ? stripslashes($value) : $value;
            $original['~' . $key] = $value;
        }
        return array_merge($safe, $original);
    }

    /**
     * Возвратит весь массив $_POST с преобразованными элементами в безопасном виде
     * @return array
     */
    private function post()
    {
        $safe = $this->convert2safe($_POST);
        $original = array();
        foreach ($_POST as $key => $value) {
            $value = get_magic_quotes_gpc() ? stripslashes($value) : $value;
            $original['~' . $key] = $value;
        }
        return array_merge($safe, $original);
    }

    /**
     * Возвратит весь массив $_REQUEST с преобразованными элементами в безопасном виде
     * @return array
     */
    private function request()
    {
        $safe = $this->convert2safe($_REQUEST);
        $original = array();
        foreach ($_REQUEST as $key => $value) {
            $value = get_magic_quotes_gpc() ? stripslashes($value) : $value;
            $original['~' . $key] = $value;
        }
        return array_merge($safe, $original);
    }

    /**
     * Возвращает элемент из суперглобального массива $_SERVER
     * Если параметр $key опущен, то вернётся весь массив $_SERVER
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getServer($key = null, $default = null)
    {
        if (null === $key) {
            return $_SERVER;
        }
        return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
    }

    /**
     * Возвращает значение запроса или массив целевого запроса
     *
     * @param null $key
     * @param null $default
     * @param string $source
     *
     * @return mixed
     */
    public function getValue($key = null, $default = null, $source = self::REQUEST)
    {
        if (null === $key) {
            return $this->{$source};
        }
        return isset($this->{$source}[$key]) ? $this->{$source}[$key] : $default;
    }

    /**
     * Проверяет, является ли данный запрос ajax-запросом
     * @return bool
     */
    public function isAjaxRequest()
    {
        return ($this->getServer('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest');
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
     * @param bool $doubleEncode
     * @return mixed
     */
    public static function convert2safe($str = null, $doubleEncode = false)
    {
        switch (gettype($str)) {
            case 'array':
                $arClean = array();
                foreach ($str as $k => $v) {
                    $arClean[$k] = self::convert2safe($v);
                }
                return $arClean;
                break;

            case 'string':
                return get_magic_quotes_gpc()
                    ? htmlspecialchars(stripslashes(trim($str)), ENT_QUOTES | ENT_HTML401, 'UTF-8', boolval($doubleEncode))
                    : htmlspecialchars(trim($str), ENT_QUOTES | ENT_HTML401, 'UTF-8', boolval($doubleEncode));
                break;

            default:
                return $str;
                break;
        }
    }
}