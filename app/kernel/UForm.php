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
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function input($type, $name, $value, $class, $id, $attr = array())
    {
        $_attr = '';
        $type = !preg_match("/(checkbox|radio)/i", $type) ? $type : 'text';
        
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }
        
        return "<input type='{$type}' name='{$name}' value='{$value}' class='{$class}' id='{$id}' {$_attr}/>";
    }

    /**
     * Создаёт выпадающий список.
     * 
     * @param string $name - имя для идентификатора селекта
     * @param array $arOptions - массив опшионов. В переданном массиве ключи элементов будут являться значением, а значения - названиями для опшионов.
     * @param array|integer|string $arSelected - список активных опшионов
     * @param string $first_option_val - если значение отлично от null, то данный опшион будет первый в списке и иметь отсылаемое значение "0"
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $id - атрибут id 
     * @param bool $isMultiple - является ли селект "мультивыборочным"
     * @param integer $size - указывает сколько опшионов будет раскрыто для показа, если было указано $isMultiple
     * @param array $attr - другие атрибуты. Передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function select($name, array $arOptions, $arSelected = null, $first_option_val = null, $class, $id, $isMultiple = false, $size = 1, array $attr = array())
    {
        $html = '';    
        $_attr = '';
        $multiple = $isMultiple ? 'multiple' : '';
        $size = $isMultiple ? "size='$size'" : '';
        $arSelected = (array) $arSelected;
        
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }

        $html .= "<select name='{$name}' class='{$class}' id='{$id}' {$size} {$multiple} {$_attr}>";        
        if (!is_null($first_option_val)) {
            $html .= "<option value='0'>{$first_option_val}</option>";
        }        
        foreach ($arOptions as $k => $v)
        {
            $selected = in_array($k, $arSelected) ? "selected='selected'" : '';
            $html .= "<option {$selected} value='{$k}'>{$v}</option>";
        }
        $html .= "</select>";

        return $html;
    }
    
    /**
     * Создаёт флажок.
     * 
     * @param string $name - имя для идентификатора флажка
     * @param string|integer|array $value - значение, что будет отсылаться на сервер.<br/> 
     * Если флажок был не выбран, на сервер будет отсылаться значение 0.<br/>
     * Если же был передан массив, то первый элемент будет являться значением, остылаемым
     * при отмеченном флажке, а второй элемент - значением, отсылаемым при невыбранном флажке.
     * @param string $class - атрибут class
     * @param string $id - атрибут id 
     * @param bool $isChecked - отмечен/не отмечен ли флажок (по умолчанию не отмечен)
     * @param bool $isDisbled - активный/неактивный ли флажок (по умолчанию активный)
     * @param array $attr - другие атрибуты. Передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function checkbox($name, $value = 1, $class, $id, $isChecked = false, $isDisbled = false, $attr = array())
    {
        $html = '';
        $_attr = '';
        $checked = $isChecked ? 'checked' : '';
        $disabled = $isDisbled ? 'disabled' : '';
        
        $val = is_array($value) ? $value[0] : $value;
        $valDefault = is_array($value) ? $value[1] : 0;        
        
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }
        
        $html .= "<input type='hidden' name='{$name}' value='{$valDefault}' />";
        $html .= "<input type='checkbox' name='{$name}' value='{$val}' class='{$class}' id='{$id}' {$checked} {$disabled} {$_attr} />";
        
        return $html;
    }
    
    /**
     * Создаёт переключатель.
     * 
     * @param string $name - имя для идентификатора переключателя
     * @param string|integer $value - значение переключателя
     * @param string $class - атрибут class
     * @param string $id - атрибут id
     * @param bool $isChecked - отмечен/не отмечен ли переключатель (по умолчанию не отмечен)
     * @param bool $isDisbled - активный/неактивный ли переключатель (по умолчанию активный)
     * @param array $attr - другие атрибуты. Передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function radio($name, $value, $class, $id, $isChecked = false, $isDisbled = false, $attr = array())
    {        
        $_attr = '';
        $checked = $isChecked ? 'checked' : '';
        $disabled = $isDisbled ? 'disabled' : ''; 
        
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }
        
        return "<input type='radio' name='{$name}' value='{$value}' class='{$class}' id='{$id}' {$checked} {$disabled} {$_attr} />";
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
     * @param array $attr - другие атрибуты. Передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return striing
     */
    public static function button($text, $class, $name, $value, $type = 'submit', $isDisabled = false, $attr = array())
    {
        $disabled = $isDisabled ? 'disabled' : '';
        
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }
        
        return "<button type='{$type}' class='{$class}' name='{$name}' value='{$value}' {$disabled} {$_attr}>{$text}</button>";
    }
    
    /**
     * Создаёт многострочную текстовую область
     * 
     * @param string $name - имя для идентификатора инпута
     * @param string|integer $text - значение поля
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $id - атрибут id      
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @param integer $rows - высота поля в строках
     * @return string
     */
    public static function textarea($name, $text, $class, $id, $attr = array(), $rows = 5)
    {        
        $_attr = '';
        $size = (int)$size;
        
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }

        return "<textarea name='{$name}' class='{$class}' id='{$id}' rows='{$rows}' {$_attr}>{$text}</textarea>";
    }
    
    /**
     * Создаёт служебную кнопку "Новая запись"
     * 
     * @param string $text - название ссылки
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function btnNew($text, $url = '#', $params, $attr = array())
    {
        $_attr = '';
        $fullURL = $url . '/' . $params;
        $fullURL = str_replace('//', '/', $fullURL);
        
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }
        
        return "<a class='btn btn-add btn-lg' href='{$fullURL}' {$_attr}><span class='glyphicon glyphicon-plus'></span>$text</a>";
    }

    /**
     * Создаёт служебную кнопку "Редактировать"
     * 
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function btnEdit($url = '#', $params, $attr = array())
    {
        $_attr = '';
        $fullURL = $url . '/' . $params;
        $fullURL = str_replace('//', '/', $fullURL);
        
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }
        
        return "<a title='Изменить' class='btn-mini btn-edit' href='{$fullURL}' {$_attr}></a>";
    }
    
    /**
     * Создаёт служебную кнопку "Удалить"
     * 
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function btnDelete($url = '#', $params, $attr = array())
    {
        $_attr = '';
        $fullURL = $url . '/' . $params;
        $fullURL = str_replace('//', '/', $fullURL);
        
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }
        
        return "<a title='Удалить' class='btn-mini btn-delete' href='{$fullURL}' {$_attr}></a>";
    }
    
    /**
     * Создаёт служебную кнопку "Начать/продолжить тестирование"
     * 
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function btnTest($url = '#', $params, $attr = array())
    {
        $_attr = '';
        $fullURL = $url . '/' . $params;
        $fullURL = str_replace('//', '/', $fullURL);
         
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }
        
        return "<a class='btn-mini btn-test' href='{$fullURL}' {$_attr}></a>";
    }
    
    /**
     * Создаёт служебную кнопку "Просмотреть результаты тестирования"
     * 
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function btnResult($url = '#', $params, $attr = array())
    {
        $_attr = '';
        $fullURL = $url . '/' . $params;
        $fullURL = str_replace('//', '/', $fullURL);
       
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }
        
        return "<a title='Просмотреть результаты' class='btn-mini btn-result' href='{$fullURL}' {$_attr}></a>";
    }
    
    /**
     * Создаёт служебную кнопку "Назначить пересдачу"
     * 
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function btnRetake($url = '#', $params, $attr = array())
    {
        $_attr = '';
        $fullURL = $url . '/' . $params;
        $fullURL = str_replace('//', '/', $fullURL);
        
        if (!empty($attr)) {
            foreach ($attr as $k => $v)
            {
                $_attr .= "{$k}='{$v}' ";
            }
        }
        
        return "<a class='btn-mini btn-retake' href='{$fullURL}' {$_attr}></a>";
    }
    
    public static function isRequired()
    {
        return "<sup class='form-control-required'>*</sup>";
    }    
    
    /**
     * Выводит информацию в виде блока с произольным набором классов
     * @param array|string $msg
     * @param string $classes
     * @param bool $block
     * @param string $listType
     * @return string
     */
    public static function message($msg, $classes, $block = true, $listType = 'ul')
    {
        $html = '';
        
        if (empty($msg)) {
            return $html;
        }
        
        $el = is_array($msg) 
            ? $listType 
            : ($block ? 'div' : 'span');        
        
        $html .= "<{$el} class='{$classes}'>";
        if (is_array($msg)) {
            foreach ($msg as $v)            
            {
                $html .= "<li>{$v}</li>";
            }
        } else {
            $html .= (string) $msg;
        }
        $html .= "</{$el}>";
        
        return $html;
    }
    
    public static function success($msg, $block = true, $listType = 'ul')
    {
        return self::message($msg, 'alert alert-success', $block, $listType);        
    }
    
    public static function info($msg, $block = true, $listType = 'ul')
    {
        return self::message($msg, 'alert alert-info', $block, $listType);        
    }
    
    public static function notice($msg, $block = true, $listType = 'ul')
    {
        return self::message($msg, 'alert alert-warning alert-mini', $block, $listType);        
    }
    
    public static function warning($msg, $block = true, $listType = 'ul')
    {
        return self::message($msg, 'alert alert-warning', $block, $listType);        
    }
    
    public static function error($msg, $closeControl = false, $block = true, $listType = 'ul')
    {
        if ($closeControl) {
            $errors = self::message($msg, 'noliststyle', $block, $listType);
            return <<<EOF
                <div class="alert alert-danger">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    {$errors}
                </div>
EOF;
        } else {
            return self::message($msg, 'alert alert-danger', $block, $listType);        
        }
    }
}