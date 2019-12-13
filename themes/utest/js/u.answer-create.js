$(document).ready(function() {

    var $answerTypeSelect = $('#answer-type-select'),
        $answerList = $('#answer-list'),
        url = $('input[name="url"]').val(),
        ajaxNewTypeUrl = url + '/ajax/newtype',
        ajaxDelAnswerUrl = url + '/ajax/delanswer';

    $answerTypeSelect.on('change', function() {
        var type = $(this).val();

        if (type == 0) {
            $al.html(
                'Создание вариантов ответов будет доступно после выбора типа вопроса.');
            return;
        }

        $.ajax({
            dataType: 'html',
            url: ajaxNewTypeUrl + '/' + type,
            success: function(data) {
                $answerList.html(data);
                focusInLastAnswer();
            },
            error: function() {
                alert('Error loading the answer type ' + type);
            },
        });
    });

    $(document).on('click', '.btn-add-answer', function(e) {
        e.preventDefault();

        var $tbody = $('.answer-table tbody'),
            type = $answerTypeSelect.length
                ? $answerTypeSelect.find('option:selected').val()
                : $('input[name="question[type]"]').val(),
            answerCount = $tbody.find('tr').length;

        $.ajax({
            dataType: 'html',
            type: 'GET',
            url: ajaxNewTypeUrl + '/' + type,
            data: {
                new: 'Y',
                count: answerCount,
            },
            success: function(data) {
                $tbody.append(data);

                // только для типа вопроса "Порядок значимости"
                if (type === 'order') {
                    answerCount++;
                    $('.answer-select-order').each(function() {
                        var $select = $(this),
                            count = $select.find('option').length;
                        if (count !== answerCount) {
                            for (var i = ++count; i <= answerCount; i++) {
                                $select.append('<option value=' + i + '>' + i +
                                    '</option>');
                            }
                        }
                    });
                }

                focusInLastAnswer();
            },
            error: function() {
                alert('Error adding the new answer');
            },
        });
    });

    $(document).on('click', '.answer-table .btn-delete', function(e) {
        e.preventDefault();

        if (!confirm(
            'Вы точно хотите удалить данный вариант?\nВариант удалится безвозвратно.')) {
            return false;
        }

        var $this = $(this),
            type = $answerTypeSelect.length
                ? $answerTypeSelect.find('option:selected').val()
                : $('input[name="question[type]"]').val(),
            params = $this.data('params');

        // Удаление нового, ещё не сохранённого ответа
        if (!params) {
            removeAnswerRow($this, type);
            return;
        }

        $.ajax({
            dataType: 'json',
            type: 'GET',
            url: ajaxDelAnswerUrl + '/' + params,
            success: function(data) {
                if (data.status === 'OK') {
                    removeAnswerRow($this, type);
                }
                else {
                    alert(data.errors);
                }
            },
            error: function() {
                alert('Error deleting the answer');
            },
        });
    });

    function removeAnswerRow($o, type) {
        $o.closest('tr').fadeOut('normal', function() {

            // только для типа вопроса "Порядок значимости"
            if (type === 'order') {
                $('.answer-select-order').each(function() {
                    var $select = $(this),
                        $last = $select.find('option:last-child'),
                        $selected = $select.find('option:selected');

                    if ($selected.val() === $last.val()) {
                        var index = $selected.index();
                        $select.find('option').
                            eq(--index).
                            attr('selected', 'selected');
                    }

                    $last.remove();
                });
            }

            $(this).remove();
        });
    }

    function focusInLastAnswer() {
        $('.answer-table tr:last-child .answer-text').focus();
    }

});


