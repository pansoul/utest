;(function($){

    var defaults = {
        // Селекторы элементов
        elements: {
            table: null,
            checkbox: null,
            checkboxAll: null
        },
        // Селекторы элементов, активность которых зависит от элементов checkbox
        checkboxDependentItems: [],
        // Селекторы элементов управления с текстом конфирма
        confirmItems: {}
    };

    $.fn.uFormTable = function(params){
        var options = $.extend(true, {}, defaults, params),
            elements = {},
            $element = null,
            $table = null,
            $checkbox = null,
            $checkboxAll = null,
            $checkboxDependentItems = $(),
            numCheckBox = 0;

        return this.each(function(el){

            $element = $(this);

            // Избавимся от повторный инициализации
            if ($element.data('uft')) {
                return;
            }

            // Сохраним переданные элементы
            for (var key in options.elements) {
                if (!options.elements.hasOwnProperty(key)) {
                    continue;
                }
                elements[key] = $element.find(options.elements[key]);
            }

            // Алиасы
            $table = elements.table;
            $checkbox = elements.checkbox;
            $checkboxAll = elements.checkboxAll;

            numCheckBox = $checkbox.length;

            // Соберём единый объект с зависимыми
            options.checkboxDependentItems.forEach(function(element){
                $checkboxDependentItems = $checkboxDependentItems.add($element.find(element));
            });

            // Вывод конфирмов
            for (var key in options.confirmItems) {
                if (!options.confirmItems.hasOwnProperty(key)) {
                    continue;
                }
                $element.find(key).data('key', key).on('click', function(){
                    if (!confirm(options.confirmItems[$(this).data('key')])) {
                        return false;
                    }
                    return true;
                });
            }

            $checkboxAll.on('change', function() {
                if ($(this).is(':checked')) {
                    $checkbox.prop('checked', true);
                    checkboxDependentItemsActivity(true);
                }
                else {
                    $checkbox.prop('checked', false);
                    checkboxDependentItemsActivity(false);
                }
            });

            $checkbox.on('change', function() {
                var numCheckedBox = $checkbox.filter(':checked').length;
                if ($(this).is(':checked')) {
                    if (numCheckedBox == numCheckBox) {
                        $checkboxAll.prop('checked', true);
                    }
                    checkboxDependentItemsActivity(true);
                }
                else {
                    $checkboxAll.prop('checked', false);
                    if (numCheckedBox == 0) {
                        checkboxDependentItemsActivity(false);
                    }
                }
            });

            $element.data('uft', {
                target: $element,
                options: options,
                elements: elements
            });

            function checkboxDependentItemsActivity(status)
            {
                $checkboxDependentItems.prop('disabled', !status);
            }

        });

    };

})(jQuery);