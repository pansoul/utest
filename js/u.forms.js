$(document).ready(function() {
    
    var numCheckBox = $('.check-one').length;
    
    $('.table-hover:not(.answer-table) .btn-delete').click(function() {
        if (confirm("Вы точно хотите удалить данную запись?")) {
            return true;
        }
        return false;
    });
    
    $('.delete-selected').click(function(){
        if (confirm("Вы уверены, что хотите удалить отмеченные записи?")) {
            return true;
        }
        return false;
    });
    
    $('.newpass-selected').click(function(){
        if (confirm("Вы уверены, что хотите сгенерировать выбранным пользователям новые пароли?")) {
            return true;
        }
        return false;
    });

    $('#check-all').change(function() {
        if ($(this).attr("checked")) {
            $('.check-one').attr('checked', true);
            $('.delete-selected, .newpass-selected').removeAttr('disabled');
        } else {
            $('.check-one').attr('checked', false);
            $('.delete-selected, .newpass-selected').attr('disabled', true);
        }
    });
    
    $('.check-one').change(function() {        
        var numCheckedBox = $('.check-one:checked').length;
        if ($(this).attr("checked")) {
            if (numCheckedBox == numCheckBox)
                $('#check-all').attr('checked', true);
            if ($('.delete-selected, .newpass-selected').attr('disabled'))
                $('.delete-selected, .newpass-selected').removeAttr('disabled');
        } else {            
            $('#check-all').attr('checked', false);
            if (numCheckedBox == 0)
                $('.delete-selected, .newpass-selected').attr('disabled', true);
        }
    });

});


