<?php

defined('ERROR_404') or define('ERROR_404','404');
defined('THEMES_PATH') or define('THEMES_PATH', ROOT . '/themes');

class UAppBuilder {
    
    private static $title;
    private static $h;
    private static $content;    
    private static $arBreadcrumb = array();
    
    private $html;    
    private $arSysTplVars = array(
        'content',
        'menu',
        'title',
        'h'
    );

    public function build($module, $layout)
    {
        if ($module == ERROR_404) {
            USite::setHeader404();
        } elseif ($module === null) {
            self::$content = '';
        } else {
            self::$content = $module;                
        }
        
        $tplVars = array();
        foreach ($this->arSysTplVars as $v)
        {
            $func = 'get' . ucfirst($v);
            $tplVars[$v] = self::$func();
        }        
        
        $this->loadIncVars($tplVars);         
        $this->html = $this->loadLayout(UBase::getConfig('theme'), $layout, $tplVars);
    }
    
    private function loadLayout($theme, $layout, $tplVars)
    {   
        $_arFind = $_arReplace = array();
        
        $themePath = THEMES_PATH . '/' . $theme;
        if (!is_dir($themePath)) {
            throw new UAppException(sprintf("Тема '%s' не найдена", $theme));
        }                
        
        $includedLayout = pathinfo($layout, PATHINFO_EXTENSION) ? true : false;        
        $layoutPath = $themePath . '/' . $layout . ($includedLayout ? '' : '.html');        
        if (!file_exists($layoutPath)) {
            if ($includedLayout) {
                return UForm::notice("Подключаемый шаблон '{$layout}' не найден");
            } else {
                throw new UAppException(sprintf("Основной шаблон '%s' не найден", $layout));
            }
        }
        
        $template = file_get_contents($layoutPath);
        preg_match_all("/\{!([.-_a-z0-9\/]+)\}/i", $template, $arInc, PREG_SET_ORDER);        
        preg_match_all("/\[!([-_a-z]+)\]/i", $template, $arVars, PREG_SET_ORDER);        
        
        foreach ($arInc as $item)
        {
            $_arFind[] = $item[0];            
            $_arReplace[] = $this->loadLayout($theme, $item[1], $tplVars);            
        }
        
        foreach ($arVars as $item)
        {
            $_arFind[] = $item[0];            
            if (array_key_exists($item[1], $tplVars)) {
                $_arReplace[] = $tplVars[$item[1]];
            } else {                                
                $_arReplace[] = UForm::notice("переменная '{$item[1]}' не объявлена");
            }
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
                $tplVars[$k] = $v;             
            }
        }
    }
    
    public function show()
    {        
        if (!$this->html) {
            throw new UAppException('Шаблон не построен!');
        }
        print $this->html;
    }
    
    public static function getTitle()
    {
        return self::$title;
    }
    
    public static function setTitle($value)
    {
        self::$title = (string)$value;
    }
    
    public static function getH()
    {
        return self::$h;
    }
    
    public static function setH($value)
    {
        self::$h = (string)$value;
    }
    
    public static function getContent()
    {        
        return self::$content;
    }
    
    public static function getMenu()
    {
        return USiteController::loadComponent('utility', 'menu');
    }
    
    public static function addBreadcrumb($name, $url)
    {
        if (!($name && $url)) {
            return false;
        }
        
        self::$arBreadcrumb[] = array(
            'name' => (string)$name,
            'url' => (string)$url
        );
        return true;
    }
    
    public static function editBreadcrumpItem(array $arr = array(), $index = 1)
    {
        if (empty($arr) || !isset(self::$arBreadcrumb[$index])) {
            return false;
        }
        
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
        $str = mb_strtolower($str, 'utf-8'); 
        $tr = array(            
            "а" => "a", "б" => "b",
            "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ё" => 'e', "ж" => "j",
            "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l",
            "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
            "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h",
            "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "y",
            "ы" => "yi", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya",           
        );
        $str = strtr($str, $tr);                
        $str = preg_replace('/[^-a-z0-9_]/', '-', $str);        
        return $str;
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