<?php

defined('ERROR_404') or define('ERROR_404','404');
defined('THEMES_PATH') or define('THEMES_PATH', ROOT . '/themes');

class UAppBuilder {
    
    private static $title;
    private static $h;
    private static $content;    
    
    private $html;
    
    private $arSysTplVars = array(
        'content',
        'menu',
        'title',
        'h'
    );
    
    private static $arBreadcrumb = array();

    public function build($module, $layout)
    {
        $tplVars = array();        
        
        if ($module == ERROR_404)
            USite::setHeader404();
        elseif ($module === null)
            self::$content = '';
        else 
            self::$content = $module;                
        
        foreach ($this->arSysTplVars as $v)
        {
            $func = 'get' . ucfirst($v);
            $tplVars[$v] = self::$func();
        }        
        $this->loadIncVars($tplVars);        
        
        $this->html = $this->loadTheme(UBase::getConfig('theme'), $layout, $tplVars);
    }
    
    private function loadTheme($theme, $layout, $tplVars)
    {   
        $_arFind = $_arReplace = array();
        
        $themePath = THEMES_PATH . '/' . $theme . '/';
        $layoutPath = $themePath . $layout . '.html';
        
        if (!is_dir($themePath))
            throw new UAppException(sprintf("Темы '%s' не найдено", $theme));
        
        if (!file_exists($layoutPath))
            throw new UAppException(sprintf("Шаблон '%s' не найден", $layout));
        
        $template = file_get_contents($layoutPath);
        preg_match_all("/\[!([-_a-zA-Z]+)\]/", $template, $arVars, PREG_SET_ORDER);        
        
        foreach ($arVars as $item)
        {
            $_arFind[] = $item[0];            
            if (isset($tplVars[$item[1]]))
                $_arReplace[] = $tplVars[$item[1]];
            else
                $_arReplace[] = USiteErrors::notice("переменная '{$item[1]}' не объявлена");
        }
        
        return str_replace($_arFind, $_arReplace, $template);
    }
    
    private function loadIncVars(&$tplVars)
    {
        $incFile = THEMES_PATH . '/' . UBase::getConfig('theme') . '/' . '_inc.php';        
        if (file_exists($incFile)) {
            $_incTplVars = include $incFile;            
            foreach ($_incTplVars as $k => $v)
            {
                if (preg_match("/^<\?(?:php|)(.+?)\?>$/iu", $v, $f))
                    $tplVars[$k] = eval($f[1]);
                else
                    $tplVars[$k] = htmlspecialchars((string)$v);
            }
        }
    }
    
    public function show()
    {        
        if (!$this->html)
            throw new UAppException('Шаблон не построен!');
        
        print $this->html;
    }
    
    public static function getTitle()
    {
        return self::$title;
    }
    
    public static function getH()
    {
        return self::$h;
    }
    
    public static function getContent()
    {        
        return self::$content;
    }
    
    public static function getMenu()
    {
        return USiteController::loadComponent('utility', 'menu');
    }
    
    public static function setTitle($value)
    {
        self::$title = (string)$value;
    }
    
    public static function setH($value)
    {
        self::$h = (string)$value;
    }
    
    public static function addBreadcrumb($name, $url)
    {
        if (!($name && $url))
            return false;
        
        self::$arBreadcrumb[] = array(
            'name' => (string)$name,
            'url' => (string)$url
        );
        return true;
    }
    
    public static function editBreadcrumpItem(array $arr = array(), $index = 1)
    {
        if (empty($arr) || !isset(self::$arBreadcrumb[$index]))
            return false;
        
        self::$arBreadcrumb[$index] = array(
            'name' => $arr['name'],
            'url' => $arr['url']
        );
        return true;
    }
    
    public static function getBreadcrumb()
    {        
        return self::$arBreadcrumb;
    }
    
    public static function clearBreadcrumb()
    {
        self::$arBreadcrumb = array();
    }
    
    public static function translit($str)
    {
        $tr = array(
            "А" => "a", "Б" => "b", "В" => "v", "Г" => "g", "Д" => "d", 
            "Е" => "e", "Ё" => "e", "Ж" => "j", "З" => "z", "И" => "i",
            "Й" => "y", "К" => "k", "Л" => "l", "М" => "m", "Н" => "n",
            "О" => "o", "П" => "p", "Р" => "r", "С" => "s", "Т" => "t",
            "У" => "u", "Ф" => "f", "Х" => "h", "Ц" => "ts", "Ч" => "ch",
            "Ш" => "sh", "Щ" => "sch", "Ъ" => "", "Ы" => "yi", "Ь" => "",
            "Э" => "e", "Ю" => "yu", "Я" => "ya", 
            
            "а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d", 
            "е" => "e", "ё" => "e", "ж" => "j", "з" => "z", "и" => "i", 
            "й" => "y", "к" => "k", "л" => "l", "м" => "m", "н" => "n", 
            "о" => "o", "п" => "p", "р" => "r", "с" => "s", "т" => "t", 
            "у" => "u", "ф" => "f", "х" => "h", "ц" => "ts", "ч" => "ch", 
            "ш" => "sh", "щ" => "sch", "ъ" => "y", "ы" => "yi", "ь" => "", 
            "э" => "e", "ю" => "yu", "я" => "ya",
            
            " " => "_", "." => "", "/" => "_", "<" => '', ">" => '',
            "&" => '', "^" => '', "?" => '', "'" => '', '"' => '',
            '\\' => '', ":" => '', ";" => '', "#" => '', "@" => '',
            "№" => 'N', "%" => '', "*" => '', "!" => ''
        );
        return strtr($str, $tr);
    }
    
    public static function getDateTime()
    {
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Конвертирует байты в человечески понятный формат
     * @param integer - количество байт, которые нужно сконвертировать
     * @return string
     */
    public static function bytesToSize($bytes, $precision = 2)
    {
        $kilobyte = 1024;
        $megabyte = $kilobyte * 1024;
        $gigabyte = $megabyte * 1024;
        $terabyte = $gigabyte * 1024;

        if (($bytes >= 0) && ($bytes < $kilobyte)) {
            return $bytes . ' B';
        } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
            return round($bytes / $kilobyte, $precision) . ' KB';
        } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
            return round($bytes / $megabyte, $precision) . ' MB';
        } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
            return round($bytes / $gigabyte, $precision) . ' GB';
        } elseif ($bytes >= $terabyte) {
            return round($bytes / $terabyte, $precision) . ' TB';
        } else {
            return $bytes . ' B';
        }
    }
}