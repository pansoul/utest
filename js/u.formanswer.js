$(document).ready(function() {
    
    $("#answer-type-select").change(function() {
        var type = $(this).val(),
            url = $('input[name="url"]').val(),
            al = $('#answer-list');
            
        if (type === '0') {
            al.html('Создание вариантов ответов будет доступно после выбора типа вопроса.');
            return false;
        }
            
        $.ajax({
            dataType: 'html',
            url: url + '/newtype/' + type,
            success: function(data)
            {
                al.html(data);
                $('.answer-table tr:last-child .answer-text').focus();
            },
            error: function()
            {
                alert('Error loading answer type');
            }
        });
    });
    
    $('.btn-add-answer').live('click', function(){
        var tbody = $('.answer-table tbody'),
            url = $('input[name="url"]').val(),            
            type = $('#answer-type-select').length  ? $('#answer-type-select option:selected').val() : $('input[name="question[type]"]').val(),
            curCount = $('.answer-table tbody tr').length;
        
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
                tbody.append(data);
                
                // only for order-type answer
                if (type === 'order') {
                    var newCount = ++curCount;
                    $('.answer-select-order').each(function(){
                        var count = $(this).find('option').length;
                        if (count !== newCount) {
                            for (var i = ++count; i <= newCount; i++)
                            {
                                $(this).append('<option value=' + i + '>' + i + '</option>');
                            }
                        }
                    });
                }
                //end
                
                $('.answer-table tr:last-child .answer-text').focus();                
            },
            error: function()
            {
                alert('Error adding a new answer');                
            }
        });
        
        return false;
    });
    
    $('.answer-table .btn-delete').live('click', function() {
        if (confirm("Вы точно хотите удалить данный вариант?\nВариант удалится безвозвратно.")) {
            var url = $('input[name="url"]').val(), 
                type = $('#answer-type-select').length  ? $('#answer-type-select option:selected').val() : $('input[name="question[type]"]').val(),
                params = $(this).data('ids'),
                that = $(this);
                
            $.ajax({
                dataType: 'json',
                type: "GET",
                url: url + '/delanswer/test-' + params,
                success: function(data)
                {
                    if (data.status === 'OK') {
                        that.closest("tr").fadeOut('normal', function() {
                            
                            // only for order-type answer
                            if (type === 'order') {
                                $('.answer-select-order').each(function() {
                                    var last = $(this).find('option:last-child');
                                    var selected = $(this).find('option:selected');   
                                    if (selected.val() === last.val()) {                                        
                                        var index = selected.index();
                                        $(this).find('option').eq(--index).attr('selected','selected');
                                    }                                    
                                    last.remove();
                                });
                            }
                            //end
                            
                            $(this).remove();
                        });
                    } else {
                        alert(data.status_message);
                    }
                },
                error: function()
                {
                    alert('Error deleting the answer');
                }
            });
        }
        return false;
    });

});


