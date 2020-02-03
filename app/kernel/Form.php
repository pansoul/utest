<?php

namespace UTest\Kernel;

class Form
{
    /**
     * Собирает массив данных атрибутов
     *
     * @param array $attr
     * @param bool $glue
     *
     * @return array|string
     */
    public static function buildAttr($attr = [], $glue = false)
    {
        $_attr = [];
        foreach ($attr as $k => $v) {
            $_attr[] = HttpRequest::convert2safe($k) . "='" . HttpRequest::convert2safe($v) . "'";
        }
        return $glue ? join(' ', $_attr) : $_attr;
    }

    /**
     * Создаёт произвольное поле.
     *
     * @param string $type - тип текстового поля (кроме "checkbox" и "radio")
     * @param string $name - имя для идентификатора инпута
     * @param string|integer $value - значение инпута
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $id - атрибут id
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     *
     * @return string
     */
    public static function input($type = '', $name = '', $value = '', $class = '', $id = '', $attr = array())
    {
        $allAttr = [
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'class' => $class,
            'id' => $id
        ];
        $allAttr = array_merge($allAttr, (array) $attr);

        return "<input " . self::buildAttr($allAttr, true) . "/>";
    }

    /**
     * Создаёт выпадающий список.
     *
     * @param string $name - имя для идентификатора селекта
     * @param array $arOptions - массив опшионов. В переданном массиве ключи элементов будут являться значением, а значения - названиями для опшионов.
     * @param array|integer|string $arSelected - список активных опшионов
     * @param mix $firstOption - если значение передано, то оно будет первым в списке и иметь отсылаемое значение "0". Если был передан массив,
     * то 1-й элемент - это отсылаемое значение, а 2-й элемент - это название опшиона.
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $id - атрибут id
     * @param bool $isMultiple - является ли селект "мультивыборочным"
     * @param integer $size - указывает сколько опшионов будет раскрыто для показа, если было указано $isMultiple
     * @param array $attr - другие атрибуты. Передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     *
     * @return string
     */
    public static function select($name = '', $arOptions = [], $arSelected = [], $firstOption = false, $class = '', $id = '', $isMultiple = false, $size = false, $attr = array())
    {
        $html = [];

        $allAttr = [
            'name' => $name,
            'class' => $class,
            'id' => $id
        ];
        if ($isMultiple) {
            $allAttr['multiple'] = 'multiple';
        }
        if ($size > 1) {
            $allAttr['size'] = $size;
        }
        $allAttr = array_merge($allAttr, (array) $attr);

        $html[] = "<select " . self::buildAttr($allAttr, true) . ">";
        if ($firstOption) {
            $value = 0;
            $label = $firstOption;
            if (is_array($firstOption)) {
                $firstOption = array_values($firstOption);
                $value = $firstOption[0];
                $label = $firstOption[1];
            }
            $html[] = "<option value='{$value}'>{$label}</option>";
        }
        foreach ($arOptions as $k => $v) {
            $selected = in_array($k, (array) $arSelected) ? "selected='selected'" : '';
            $html[] = "<option {$selected} value='{$k}'>{$v}</option>";
        }
        $html[] = "</select>";

        return join('', $html);
    }

    /**
     * Создаёт флажок.
     *
     * @param string $name - имя для идентификатора флажка
     * @param string|integer|array $value - значение, что будет отсылаться на сервер.<br/>
     * Если флажок был не выбран, на сервер будет отсылаться значение 0.<br/>
     * Если же был передан массив, то 1-й элемент будет являться значением, остылаемым
     * при отмеченном флажке, а 2-й элемент - значением, отсылаемым при невыбранном флажке.
     * @param string $class - атрибут class
     * @param string $id - атрибут id
     * @param bool $isChecked - отмечен/не отмечен ли флажок (по умолчанию не отмечен)
     * @param bool $isDisabled - активный/неактивный ли флажок (по умолчанию активный)
     * @param array $attr - другие атрибуты. Передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     *
     * @return string
     */
    public static function checkbox($name = '', $value = 1, $class = '', $id = '', $isChecked = false, $isDisabled = false, $attr = array())
    {
        $html = [];
        $valReal = $value;
        $valDefault = 0;
        if (is_array($value)) {
            $value = array_values($value);
            $valReal = $value[0];
            $valDefault = $value[1];
        }

        if ($isChecked) {
            $attr['checked'] = 'checked';
        }
        if ($isDisabled) {
            $attr['disabled'] = 'disabled';
        }

        $html[] = self::input('hidden', $name, $valDefault);
        $html[] = self::input('checkbox', $name, $valReal, $class, $id, $attr);
        return join('', $html);
    }

    /**
     * Создаёт переключатель.
     *
     * @param string $name - имя для идентификатора переключателя
     * @param string|integer $value - значение переключателя
     * @param string $class - атрибут class
     * @param string $id - атрибут id
     * @param bool $isChecked - отмечен/не отмечен ли переключатель (по умолчанию не отмечен)
     * @param bool $isDisabled - активный/неактивный ли переключатель (по умолчанию активный)
     * @param array $attr - другие атрибуты. Передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     *
     * @return string
     */
    public static function radio($name = '', $value = '', $class = '', $id = '', $isChecked = false, $isDisabled = false, $attr = array())
    {
        if ($isChecked) {
            $attr['checked'] = 'checked';
        }
        if ($isDisabled) {
            $attr['disabled'] = 'disabled';
        }

        return self::input('radio', $name, $value, $class, $id, $attr);
    }

    /**
     * Создаёт кнопку.
     *
     * @param string $text - надпись кнопки
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $name - имя для идентификатора кнопки
     * @param string|integer $value - значение кнопки. По умолчанию "submit"
     * @param string $type - тип кнопки
     * @param bool $isDisabled - активна/неактива ли кнопка. По умолчанию true
     * @param array $attr - другие атрибуты. Передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     *
     * @return string
     */
    public static function button($text = '', $class = '', $name = '', $value = '', $type = 'submit', $isDisabled = false, $attr = array())
    {
        $allAttr = [
            'type' => $type,
            'name' => $name,
            'value' => $value,
            'class' => $class,
        ];
        if ($isDisabled) {
            $allAttr['disabled'] = 'disabled';
        }
        $allAttr = array_merge($allAttr, (array) $attr);

        return "<button " . self::buildAttr($allAttr, true) . ">{$text}</button>";
    }

    /**
     * Создаёт многострочную текстовую область
     *
     * @param string $name - имя для идентификатора инпута
     * @param string|integer $text - значение поля
     * @param string $class - атрибут class (можно назначать несколько классов через пробел)
     * @param string $id - атрибут id
     * @param integer $rows - высота поля в строках
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     *
     * @return string
     */
    public static function textarea($name = '', $text = '', $class = '', $id = '', $rows = 5, $attr = array())
    {
        $allAttr = [
            'name' => $name,
            'class' => $class,
            'id' => $id,
            'rows' => (int) $rows,
        ];
        $allAttr = array_merge($allAttr, (array) $attr);

        return "<textarea " . self::buildAttr($allAttr, true) . ">{$text}</textarea>";
    }

    /**
     * Создаёт служебную кнопку "Новая запись"
     *
     * @param string $text - название ссылки
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     *
     * @return string
     */
    public static function btnNew($text = '', $url = '#', $params = '', $attr = array())
    {
        $fullUrl = $url . '/' . $params;
        $fullUrl = str_replace('//', '/', $fullUrl);

        return "<a class='btn btn-add btn-lg' href='{$fullUrl}' " . self::buildAttr($attr, true) . "><span class='glyphicon glyphicon-plus'></span>{$text}</a>";
    }

    /**
     * Создаёт служебную кнопку "Редактировать"
     *
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     *
     * @return string
     */
    public static function btnEdit($url = '#', $params = '', $attr = array())
    {
        $fullUrl = $url . '/' . $params;
        $fullUrl = str_replace('//', '/', $fullUrl);

        return "<a title='Изменить' class='btn-mini btn-edit' href='{$fullUrl}' " . self::buildAttr($attr, true) . "></a>";
    }

    /**
     * Создаёт служебную кнопку "Удалить"
     *
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     *
     * @return string
     */
    public static function btnDelete($url = '#', $params = '', $attr = array())
    {
        $fullUrl = $url . '/' . $params;
        $fullUrl = str_replace('//', '/', $fullUrl);

        return "<a title='Удалить' class='btn-mini btn-delete' href='{$fullUrl}' " . self::buildAttr($attr, true) . "></a>";
    }

    /**
     * Создаёт служебную кнопку "Начать/продолжить тестирование"
     *
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     * @return string
     */
    public static function btnTest($url = '#', $params = '', $attr = array())
    {
        $fullUrl = $url . '/' . $params;
        $fullUrl = str_replace('//', '/', $fullUrl);

        return "<a class='btn-mini btn-test' href='{$fullUrl}' " . self::buildAttr($attr, true) . "></a>";
    }

    /**
     * Создаёт служебную кнопку "Просмотреть результаты тестирования"
     *
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     *
     * @return string
     */
    // @todo Убрать безобразие $params и во всех подобных функциях
    public static function btnResult($url = '#', $params = '', $attr = array())
    {
        $fullUrl = $url . '/' . $params;
        $fullUrl = str_replace('//', '/', $fullUrl);

        return "<a title='Просмотреть результаты' class='btn-mini btn-result' href='{$fullUrl}' " . self::buildAttr($attr, true) . "></a>";
    }

    /**
     * Создаёт служебную кнопку "Назначить пересдачу"
     *
     * @param string $url - часть url, включающая название контроллера
     * @param string $params - параметры, передаваемые контроллеру
     * @param array $attr - другие атрибуты у элемента, передаются через массив в виде пар "<имя_атрибута>"=>"<значение_атрибута>"
     *
     * @return string
     */
    public static function btnRetake($url = '#', $params = '', $attr = array())
    {
        $fullUrl = $url . '/' . $params;
        $fullUrl = str_replace('//', '/', $fullUrl);

        return "<a class='btn-mini btn-retake' href='{$fullUrl}' " . self::buildAttr($attr, true) . "></a>";
    }

    /**
     * Отобразит звёздочку
     * @return string
     */
    public static function asterisk()
    {
        return "<sup class='form-control-required'>*</sup>";
    }

    /**
     * Выводит информацию в виде блока с произольным набором классов
     *
     * @param array|string $msg
     * @param string $classes
     * @param bool $block
     * @param string $listType
     *
     * @return string
     */
    public static function message($msg = '', $classes = '', $block = true, $listType = 'ul')
    {
        if (empty($msg)) {
            return '';
        }

        if (is_array($msg) && count($msg) == 1) {
            $msg = array_shift($msg);
        }

        $html = [];
        $el = is_array($msg) ? $listType : ($block ? 'div' : 'span');

        $html[] = "<{$el} class='{$classes}'>";
        if (is_array($msg)) {
            foreach ($msg as $v) {
                $html[] = "<li>{$v}</li>";
            }
        } else {
            $html[] = (string)$msg;
        }
        $html[] = "</{$el}>";

        return join('', $html);
    }

    public static function success($msg = '', $block = true, $listType = 'ul')
    {
        return self::message($msg, 'alert alert-success', $block, $listType);
    }

    public static function info($msg = '', $block = true, $listType = 'ul')
    {
        return self::message($msg, 'alert alert-info', $block, $listType);
    }

    public static function notice($msg = '', $block = true, $listType = 'ul')
    {
        return self::message($msg, 'alert alert-warning alert-mini', $block, $listType);
    }

    public static function warning($msg = '', $block = true, $listType = 'ul')
    {
        return self::message($msg, 'alert alert-warning', $block, $listType);
    }

    public static function error($msg = '', $closeControl = false, $block = true, $listType = 'ul')
    {
        if ($closeControl) {
            $errors = self::message($msg, '', $block, $listType);
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