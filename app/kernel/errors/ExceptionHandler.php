<?php

namespace UTest\Kernel\Errors;

class ExceptionHandler
{
    /**
     * Функция по вылавливанию исключений
     * @param object $exception
     * @return void
     */
    public static function exceptionHandler($exception)
    {
        if (!error_reporting()) {
            return;
        }

        if (\UTest\Kernel\Base::getConfig('debug > display_errors')) {
            echo self::exception($exception->getMessage(), $exception->getFile(), $exception->getLine(),
                $exception->getTrace());
            return;
        }

        // these are our templates
        $traceline = "#%s %s(%s): %s(%s)";
        $msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

        // alter your trace as you please, here
        $trace = $exception->getTrace();

        foreach ($trace as $key => $stackPoint) {
            // I'm converting arguments to their type
            // (prevents passwords from ever getting logged as anything other than 'string')
            $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
        }

        // build your tracelines
        $result = array();
        foreach ($trace as $key => $stackPoint) {
            $result[] = sprintf(
                $traceline,
                $key,
                $stackPoint['file'],
                $stackPoint['line'],
                $stackPoint['function'],
                implode(', ', $stackPoint['args'])
            );
        }
        // trace always ends with {main}
        $result[] = '#' . ++$key . ' {main}';

        // write tracelines into main template
        $msg = sprintf(
            $msg,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode("\n", $result),
            $exception->getFile(),
            $exception->getLine()
        );

        // log or echo as you please
        error_log($msg);
    }

    private static function exception($msg, $file, $line, $trace)
    {
        $traceline = "%s(<b>%s</b>): %s -> %s(%s)";
        foreach ($trace as $key => $stackPoint) {
            $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
        }

        $trace = array_reverse($trace);

        $result = array();
        $result[] = '{main}';
        foreach ($trace as $key => $stackPoint) {
            $result[] = sprintf(
                $traceline,
                $stackPoint['file'],
                $stackPoint['line'],
                $stackPoint['class'],
                $stackPoint['function'],
                implode(', ', $stackPoint['args'])
            );
        }
        $resultToStr = implode("</li><li>", $result);

        while (ob_get_level()) {
            ob_end_clean();
        }

        return <<<EOF
            <!DOCTYPE html>
            <html>                
                <head>
                    <style>
                        body, html{
                            margin: 0;
                            padding: 0;
                            height: 100%;
                            weight: 100%;
                        }
        
                        body{
                            font: 16px/1.8em Verdana;
                        }
                        
                        .exception{
                            list-style: none;
                            margin: 0;
                            padding: 0;
                            width: 700px;
                            color: red;                            
                        }
        
                        .exception > li{
                            border: 2px solid #C63C27;
                            padding: 10px;
                            border-radius: 5px;
                        }
        
                        .exception-info{
                            margin-top: 10px;
                            font-size: 12px;
                            line-height: 1.2;
                            background: #FAEAE0;
                            text-align: left;     
                            color: #58343C;
                        }
        
                        #trace{
                            list-style: decimal outside none;
                            margin: 20px 0 20px 30px;
                            padding: 0;
                            margin-top: 10px;
                            background: #FFF8D2;
                            color: #000;
                        }
        
                        #trace li{
                            border: none;
                            padding: 10px;
                            border-top: 1px solid #EA9B20;
                        }
                    </style>
                </head>
            <body>
                <table border="0" width="100%" height="100%">
                    <tr>
                        <td align="center">
                            <ul class='exception'>
                                <li>{$msg}</li>
                                <li class="exception-info">
                                    File: "{$file}" <br/>
                                    Line: {$line} <br/><br/>
                                    Array trace: <br/>
                                    <ol id="trace">
                                        <li>$resultToStr</li>
                                    </ol>
                                </li>
                            </ul>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
EOF;
    }
}