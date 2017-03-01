<?php

class UTStudent {
    
    /**
     * Массив доступных для создания вариантов типов вопросов.
     * Ключами являются название классов, которые реализуют обработку выбранного
     * типа вопроса.
     * @var array 
     */
    private static $arTypeQuestion = array(
        'one' => array(
            'name' => 'единственный ответ',
            'desc' => 'Из числа возможных вариантов верным является только один'
        ),
        'multiple' => array(
            'name' => 'несколько ответов',
            'desc' => 'Из числа возможных вариантов верными могут быть несколько'
        ),
        'order' => array(
            'name' => 'порядок значимости',
            'desc' => 'Выставление вариантов ответов в верной последовательности'
        ),
        'match' => array(
            'name' => 'точность написания',
            'desc' => 'Необходимо будет написать в текстовом виде верный ответ'
        )
    );
    
    private static $arRequiredText = array(
        'title' => 'Укажите название теста',
        'subject_id' => 'Укажите предмет, по которому будет создаваться тест'
    );
    
    private static $arRequiredQuestion = array(
        'text' => 'Заполните текст вопроса',
        'type' => 'Укажите тип вопроса'
    );
    
    /**
     * Список возможных статусов тестов
     * @var array
     */
    private static $arStatusTest = array(
        0 => 'прохождение не начиналось',
        1 => 'в процессе прохождения',
        2 => 'пройден'
    );
    
    function __construct()
    {
        die('st');
    }
    
    /**
     * Проверяет имеется ли запись о прохождении теста пользователем
     * @param integer $stid
     * @param integer $uid
     * @return bool
     */
    public static function checkRunningTest($stid, $uid)
    {
        $sql = "
            SELECT t.*
            FROM " . self::$table_st_passage . " AS t
            LEFT JOIN " . self::$table_st_time . " AS i 
                ON (i.test_id = t.test_id AND i.user_id = t.user_id)
            WHERE
                t.test_id = {$stid}
                AND t.user_id = {$uid}
                AND i.retake_value = t.retake
        ";
        $res = R::getRow($sql);

        return (bool) count($res);
    }
    
    /**
     * Возвращает все доступные статусы тестов
     * @return array
     */
    public static function getStatusTest()
    {
        return self::$arStatusTest;
    }
    
}