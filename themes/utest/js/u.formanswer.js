$(document).ready(function() {
    
    $("#answer-type-select").on('change', function() {
        var type = $(this).val(),
            url = $('input[name="url"]').val(),
            $al = $('#answer-list');
            
        if (type == 0) {
            $al.html('Создание вариантов ответов будет доступно после выбора типа вопроса.');
            return false;
        }
            
        $.ajax({
            dataType: 'html',
            url: url + '/newtype/' + type,
            success: function(data)
            {
                $al.html(data);
                $('.answer-table tr:last-child .answer-text').focus();
            },
            error: function()
            {
                alert('Error loading answer type');
            }
        });
    });
    
    $(document).on('click', '.btn-add-answer', function(e){
        e.preventDefault();
        
        var $tbody = $('.answer-table tbody'),
            url = $('input[name="url"]').val(),            
            type = $('#answer-type-select').length 
                ? $('#answer-type-select option:selected').val() 
                : $('input[name="question[type]"]').val(),
            curCount = $tbody.find('tr').length;
        
        $.ajax({
            dataType: 'html',
            type: "GET",
            url: url + '/newtype/' + type,
            data: {
                new: 'Y',
                count: curCount
            },
            success: function(data)
            {
                $tbody.append(data);
                
                // только для типа вопроса "Порядок значимости"
                if (type === 'order') {                    
                    curCount++;
                    $('.answer-select-order').each(function(){
                        var $select = $(this),
                            count = $select.find('option').length;
                        if (count !== curCount) {
                            for (var i = ++count; i <= curCount; i++)
                            {
                                $select.append('<option value=' + i + '>' + i + '</option>');
                            }
                        }
                    });
                }
                // конец
                
                $('.answer-table tr:last-child .answer-text').focus();                
            },
            error: function()
            {
                alert('Error adding a new answer');                
            }
        });
    });
    
    $(document).on('click', '.answer-table .btn-delete', function(e){    
        e.preventDefault();
        
        if (!confirm("Вы точно хотите удалить данный вариант?\nВариант удалится безвозвратно.")) {
            return false;
        }
        
        var $this = $(this),
            url = $('input[name="url"]').val(), 
            type = $('#answer-type-select').length 
                ? $('#answer-type-select option:selected').val() 
                : $('input[name="question[type]"]').val(),
            params = $this.data('ids');
            
        // Удаление нового, ещё не сохранённого ответа
        if (!params) {
            _removeAnswerRow($this, type);
            return false;
        }

        $.ajax({
            dataType: 'json',
            type: "GET",
            url: url + '/delanswer/test-' + params,
            success: function(data)
            {
                if (data.status === 'OK') {
                    _removeAnswerRow($this, type);
                } else {
                    alert(data.status_message);
                }
            },
            error: function()
            {
                alert('Error deleting the answer');
            }
        });        
    });
    
    function _removeAnswerRow($o, type)
    {
        $o.closest("tr").fadeOut('normal', function() {

            // только для типа вопроса "Порядок значимости"
            if (type === 'order') {
                $('.answer-select-order').each(function() {
                    var $select = $(this),
                        $last = $select.find('option:last-child'),
                        selected = $select.find('option:selected');   
                    if (selected.val() === $last.val()) {                                        
                        var index = selected.index();
                        $select.find('option').eq(--index).attr('selected','selected');
                    }                                    
                    $last.remove();
                });
            }
            // конец

            $(this).remove();
        });
    }

});


