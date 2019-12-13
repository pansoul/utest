$(document).ready(function() {

    var numCheckBox = $('.check-one').length;

    $('.table-hover:not(.answer-table) .btn-delete').click(function() {
        if (!confirm('Вы точно хотите удалить данную запись?')) {
            return false;
        }
        return true;
    });

    $('.delete-selected').on('click', function() {
        if (!confirm('Вы уверены, что хотите удалить отмеченные записи?')) {
            return false;
        }
        return true;
    });

    $('.newpass-selected').on('click', function() {
        if (!confirm('Вы уверены, что хотите сгенерировать выбранным пользователям новые пароли?')) {
            return false;
        }
        return true;
    });

    $('#check-all').on('change', function() {
        if ($(this).is(':checked')) {
            $('.check-one').attr('checked', true);
            $('.delete-selected, .newpass-selected').removeAttr('disabled');
        }
        else {
            $('.check-one').attr('checked', false);
            $('.delete-selected, .newpass-selected').attr('disabled', true);
        }
    });

    $('.check-one').on('change', function() {
        var numCheckedBox = $('.check-one:checked').length;
        if ($(this).is(':checked')) {
            if (numCheckedBox == numCheckBox) {
                $('#check-all').attr('checked', true);
            }
            if ($('.delete-selected, .newpass-selected').attr('disabled')) {
                $('.delete-selected, .newpass-selected').removeAttr('disabled');
            }
        }
        else {
            $('#check-all').attr('checked', false);
            if (numCheckedBox == 0) {
                $('.delete-selected, .newpass-selected').attr('disabled', true);
            }
        }
    });

});


