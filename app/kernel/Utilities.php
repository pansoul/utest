<?php

namespace UTest\Kernel;

use UTest\Kernel\User\User;
use UTest\Kernel\Site;

class Utilities
{
    private function __construct()
    {
        //
    }

    public static function generateSalt()
    {
        $salt = '';
        $length = rand(5, 10); // длина соли (от 5 до 10 сомволов)
        for ($i = 0; $i < $length; $i++) {
            $salt .= chr(rand(33, 126)); // символ из ASCII-table
        }
        return $salt;
    }

    public static function getRandomString($chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $length = 8)
    {
        $count = mb_strlen($chars);

        for ($i = 0, $password = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $password .= mb_substr($chars, $index, 1);
        }

        return $password;
    }

    public static function pluralForm($n, $forms, $returnWithNumber = false)
    {
        $label = $n % 10 == 1 && $n % 100 != 11 ? $forms[0] : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? $forms[1] : $forms[2]);
        return $returnWithNumber ? ($n.' '.$label) : $label;
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

    public static function truncate($string, $limit = 150, $stripTags = true, $break = '.', $pad = '...')
    {
        if ($stripTags) {
            $string = strip_tags($string);
        }

        // return with no change if string is shorter than $limit
        if (strlen($string) <= $limit) {
            return $string;
        }

        // is $break present between $limit and the end of the string?
        if (false !== ($breakpoint = strpos($string, $break, $limit))) {
            if ($breakpoint < strlen($string) - 1) {
                $string = substr($string, 0, $breakpoint) . $pad;
            }
        }

        return $string;
    }

    public static function setUniqueAlias(&$alias, $table)
    {
        while (DB::table($table)->where('alias', '=', $alias)->exists()) {
            $alias .= '-1';
        }
    }

    public static function getUserUploadedDir($relative = false)
    {
        $dir = UPLOADS_PATH . '/' . Site::getGroup() . '-' . User::user()->getUID() . '/' . Site::getModName();
        if (!is_dir($dir)) {
            mkdir($dir, 0755);
        }
        return $relative ? substr($dir, strlen(ROOT)) : $dir;
    }
}