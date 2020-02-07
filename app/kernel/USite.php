<?php

class USite {

    /**
     * Текущий корректный url сайта
     * @var string 
     */
    private static $url;
    
    /**
     * Имя группы, относительно текущего url
     * @var string
     */
    private static $group;
    
    /**
     * Название текущего модуля.
     * Здесь храниться именно имя того модуля, который был вызван непосредственно
     * через адресную строку, а не через ajax
     * @var string
     */
    private static $modname;
    
    /**
     * Url текущего модуля.
     * Url именно того модуля, что был вызван через адресную строку, а не через ajax
     * @var string
     */
    private static $modurl;
    
    private function __construct()
    {
        //
    }

    /**
     * Устанавливает заголовок в статус 404
     */
    public static function setHeader404()
    {
        header("HTTP/1.0 404 Not Found");
        header('Status: 404 Not Found');         
    }
    
    /**
     * Редирект пользователя
     * @param string $url - на какую страницу сделать редирект
     * @param bool $isPermanent - будет ли редирект моментальным, или же с каким-то сопровадительным сообщением (по умолчанию моментальный)
     * @param string $text - текст сопроводительного сообщения
     * @param integer $delay - время в секундах, после которого пользователя перекинет
     */
    public static function redirect($url, $isPermanent = true, $text = 'Перенаправление, ожидайте..', $delay = 2)
    {
        if ($isPermanent) {
            header('Location: ' . $url);
            exit;
        } else {
			$seconds = $delay * 1000;            
            echo <<<EOF
                <!DOCTYPE html>
                <html>                
                    <head>                    
                        <meta charset="utf-8" />                    
                        <title>Перенаправление...</title>
                        <style>
                            body, html{
                                margin: 0;
                                padding: 0;
                                height: 100%;
                                weight: 100%;
                            }

                            body{
                                font: 16px/1.5em Verdana;
                            }                        
                        </style>
						<script>
							window.setTimeout(function() {
								window.location.href = '{$url}';
							}, {$seconds});
						</script>
                    </head>
                <body>
                    <table border="0" width="100%" height="100%">
                        <tr>
                            <td align="center">
                                {$text}
                            </td>
                        </tr>
                    </table>
                </body>
                </html>
EOF;
            exit;
        }
    }   

    public static function setUrl($url)
    {
        self::$url = (string)$url;
    }

    public static function setGroup($group)
    {
        self::$group = (string)$group;
    }
    
    public static function setModname($name)
    {
        if (!self::$modname)
            self::$modname = (string)$name;        
    }
    
    public static function setModurl($url)
    {
        if (!self::$modurl)
            self::$modurl = (string)$url;
    }

    public static function getUrl()
    {
        return self::$url;
    }

    public static function getGroup()
    {
        return self::$group;
    }
    
    public static function getModname()
    {
        return self::$modname;
    }
    
    public static function getModurl()
    {
        return self::$modurl;
    }
}