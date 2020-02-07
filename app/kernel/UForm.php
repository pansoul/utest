<?php

class UForm {

    /**
     * Создаёт текстовое поле.
     * 
     * @param string $type - тип текстового поля (кроме "checkbox" и "radio")
     * @param string $name - имя для идентификатора инпута
     * @param string|integer $value - значение инпута
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $id - атрибут id 
     * @param array $attr - другие атрибуты у поля, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function input($type, $name, $value, $class, $id, array $attr = array())
    {
        $_attr = '';
        if (!empty($attr))
            foreach ($attr as $k => $v)
            {
                $_attr .= "$k='$v' ";
            }
        return "<input type='$type' name='$name' value='$value' class='$class' id='$id' $_attr/>";
    }

    /**
     * Создаёт выпадающий список.
     * 
     * @param string $name - имя для идентификатора селекта
     * @param array $arOptions - массив опшионов. В переданном массиве ключи элементов будут являться значением, а значения - названиями для опшионов.
     * @param array|integer $arSelected - список активных опшионов
     * @param string $first_option_val - если значение отлично от null, то данный опшион будет первый в списке и иметь отсылаемое значение "0"
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $id - атрибут id 
     * @param bool $isMultiple - является ли селект "мультивыборочным"
     * @param integer $size - указывает сколько опшионов будет раскрыто для показа, если было указано $isMultiple
     * @return string
     */
    public static function select($name, array $arOptions, $arSelected = null, $first_option_val = null, $class, $id, $isMultiple = false, $size = 1)
    {
        $html = '';        
        $multiple = $isMultiple ? 'multiple' : '';
        $size = $isMultiple ? "size='$size'" : '';
        $arSelected = (array) $arSelected;

        $html .= "<select name='$name' class='$class' id='$id' $size $multiple>";
        
        if ($first_option_val) 
            $html .= "<option value='0'>-$first_option_val-</option>";
        
        foreach ($arOptions as $k => $v)
        {
            $selected = in_array($k, $arSelected) ? "selected='selected'" : '';
            $html .= "<option $selected value='$k'>$v</option>";
        }
        $html .= "</select>";

        return $html;
    }
    
    /**
     * Создаёт флажок.
     * 
     * @param string $name - имя для идентификатора флажка
     * @param string|integer $value - значение, что будет отсылаться на сервер, если флажок был отмечен, если флажое был не отмечен - отсылается "0"
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $id - атрибут id 
     * @param bool $isChecked - отмечен/не отмечен ли флажок (по умолчанию не отмечен)
     * @param bool $isDisbled - активный/неактивный ли флажок (по умолчанию активный)
     * @return string
     */
    public static function checkbox($name, $value = 1, $class, $id, $isChecked = false, $isDisbled = false)
    {
        $html = '';
        $checked = $isChecked ? 'checked' : '';
        $disabled = $isDisbled ? 'disabled' : '';
        
        $html .= "<input type='hidden' name='$name' value='0' />";
        $html .= "<input type='checkbox' name='$name' class='$class' id='$id' value='$value' $checked $disabled />";
        
        return $html;
    }
    
    /**
     * Создаёт переключатель.
     * 
     * @param string $name - имя для идентификатора переключателя
     * @param string|integer $value - значение переключателя
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $id - атрибут id
     * @param bool $isChecked - отмечен/не отмечен ли переключатель (по умолчанию не отмечен)
     * @param bool $isDisbled - активный/неактивный ли переключатель (по умолчанию активный)
     * @return string
     */
    public static function radio($name, $value, $class, $id, $isChecked = false, $isDisbled = false)
    {        
        $checked = $isChecked ? 'checked' : '';
        $disabled = $isDisbled ? 'disabled' : '';        
        
        return "<input type='radio' name='$name' class='$class' id='$id' value='$value' $checked $disabled />";
    }
    
    /**
     * Создаёт кнопку.
     * 
     * @param string $text - надпись кнопки
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $name - имя для идентификатора кнопки
     * @param string|integer $value - значение кнопки
     * @param string $type - тип кнопки
     * @param bool $isDisabled - активна/неактива ли кнопка (по умолчанию активна)
     * @return striing
     */
    public static function button($text, $class, $name, $value, $type = 'submit', $isDisabled = false)
    {
        $disabled = $isDisabled ? 'disabled' : '';
        return "<button type='$type' class='$class' name='$name' value='$value' $disabled>$text</button>";
    }
    
    /**
     * Создаёт многострочную текстовую область
     * 
     * @param string $name - имя для идентификатора инпута
     * @param string|integer $text - значение поля
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $id - атрибут id      
     * @param array $attr - другие атрибуты у поля, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @param integer $rows - высота поля в строках
     * @return string
     */
    public static function textarea($name, $text, $class, $id, array $attr = array(), $rows = 5)
    {        
        $_attr = '';
        $size = (int)$size;
        if (!empty($attr))
            foreach ($attr as $k => $v)
            {
                $_attr .= "$k='$v' ";
            }
        return "<textarea name='$name' class='$class' id='$id' rows='$rows' $_attr>$text</textarea>";
    }
    
    
    public static function btnNew($text, $url = '#', $params)
    {
        $fullURL = $url . $params;
        return "<a class='btn btn-add btn-lg' href='$fullURL'><span class='glyphicon glyphicon-plus'></span>$text</a>";
    }

    /**
     * Создаёт служебную кнопку "Редактировать"
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @return string
     */
    public static function btnEdit($url = '#', $params, array $attr = array())
    {
        $fullURL = $url . $params;
         if (!empty($attr))
            foreach ($attr as $k => $v)
            {
                $_attr .= "$k='$v' ";
            }
        return "<a title='Изменить' class='btn-mini btn-edit' href='$fullURL' $_attr></a>";
    }
    
    /**
     * Создаёт служебную кнопку "Удалить"
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @return string
     */
    public static function btnDelete($url = '#', $params, array $attr = array())
    {
        $fullURL = $url . $params;
         if (!empty($attr))
            foreach ($attr as $k => $v)
            {
                $_attr .= "$k='$v' ";
            }
        return "<a title='Удалить' class='btn-mini btn-delete' href='$fullURL' $_attr></a>";
    }
    
    /**
     * Создаёт служебную кнопку "Начать/продолжить тестирование"
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @return string
     */
    public static function btnTest($url = '#', $params, array $attr = array())
    {
        $fullURL = $url . $params;
         if (!empty($attr))
            foreach ($attr as $k => $v)
            {
                $_attr .= "$k='$v' ";
            }
        return "<a class='btn-mini btn-test' href='$fullURL' $_attr></a>";
    }
    
    /**
     * Создаёт служебную кнопку "Просмотреть результаты тестирования"
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @return string
     */
    public static function btnResult($url = '#', $params, array $attr = array())
    {
        $fullURL = $url . $params;
         if (!empty($attr))
            foreach ($attr as $k => $v)
            {
                $_attr .= "$k='$v' ";
            }
        return "<a title='Просмотреть результаты' class='btn-mini btn-result' href='$fullURL' $_attr></a>";
    }
    
    /**
     * Создаёт служебную кнопку "Назначить пересдачу"
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @return string
     */
    public static function btnRetake($url = '#', $params, array $attr = array())
    {
        $fullURL = $url . $params;
         if (!empty($attr))
            foreach ($attr as $k => $v)
            {
                $_attr .= "$k='$v' ";
            }
        return "<a class='btn-mini btn-retake' href='$fullURL' $_attr></a>";
    }
    
    public static function isRequired()
    {
        return "<sup class='form-control-required'>*</sup>";
    }
    
}