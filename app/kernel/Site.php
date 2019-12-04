<?php

namespace UTest\Kernel;

class Site
{
    /**
     * Текущий url
     * @var string
     */
    private static $url = '';

    /**
     * Имя группы, относительно текущего url
     * @var string
     */
    private static $group = '';

    /**
     * Название текущего модуля (компонента).
     * Здесь храниться именно имя того модуля, который был вызван непосредственно через адресную строку.
     * Представляем из себя запись вида "group.modName"
     * @var string
     */
    private static $modName = '';

    /**
     * Url текущего модуля (компонента).
     * Url именно того модуля, что был вызван через адресную строку.
     * Представляем из себя запись вида "/group/modName"
     * @var string
     */
    private static $modUrl = '';

    /**
     * Часть url, представляющая из себя строку параметров модуля (компонента).
     * Представляем из себя запись вида "{/group/modName}/action/..."
     * @var string
     */
    private static $modParamsRow = '';

    private function __construct()
    {
        //
    }

    public static function setHeader404()
    {
        header("HTTP/1.0 404 Not Found");
        header('Status: 404 Not Found');
    }

    public static function setUrl($url = '')
    {
        if (!self::$url) {
            self::$url = (string) $url;
        }
    }

    public static function setGroup($group = '')
    {
        if (!self::$group) {
            self::$group = (string) $group;
        }
    }

    public static function setModName($name = '')
    {
        self::$modName = (string) $name;
    }

    public static function setModUrl($url = '')
    {
        self::$modUrl = (string) $url;
    }

    public static function setModParamsRow($args = '')
    {
        self::$modParamsRow = (string) $args;
    }

    public static function getUrl()
    {
        return self::$url;
    }

    public static function getGroup()
    {
        return self::$group;
    }

    public static function getModName()
    {
        return self::$modName;
    }

    public static function getModUrl()
    {
        return self::$modUrl;
    }

    public static function getModParamsRow()
    {
        return self::$modParamsRow;
    }

    /**
     * Редирект пользователя
     *
     * @param string $url - на какую страницу сделать редирект
     * @param bool $isPermanent - будет ли редирект моментальным, или же с каким-то сопровадительным сообщением (по умолчанию моментальный)
     * @param string $text - текст сопроводительного сообщения
     * @param integer $delay - время в секундах, после которого пользователя перекинет
     */
    public static function redirect($url = '', $isPermanent = true, $text = 'Перенаправление, ожидайте...', $delay = 2)
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
}
