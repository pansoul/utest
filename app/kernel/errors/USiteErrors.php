<?php

class USiteErrors {
    
    public static function exception($msg, $file, $line, $trace)
    {
        //var_dump($trace);die;
        $traceline = "%s(<b>%s</b>): %s -> %s(%s)";
        foreach ($trace as $key => $stackPoint)
        {
            $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
        }

        $trace = array_reverse($trace);
        
        $result = array();
        $result[] = '{main}'; 
        foreach ($trace as $key => $stackPoint)
        {
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
    
    public static function notice($msg)
    {
        return "<span class='alert alert-warning alert-mini'>$msg</span>";
    }
    
    public static function warning($msg)
    {
        return "<div class='alert alert-danger'>$msg</div>";
    }
    
}