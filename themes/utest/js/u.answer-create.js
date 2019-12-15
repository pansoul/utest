$(document).ready(function() {

    var $answerTypeSelect = $('#answer-type-select'),
        $answerList = $('#answer-list'),
        $updateInfo = $('#update-info'),
        $unselectedType = $('#unselected-type'),
        url = $('input[name="url"]').val(),
        ajaxNewTypeUrl = url + '/ajax/newtype';

    $answerTypeSelect.on('change', function() {
        var type = $(this).val();

        if (!type) {
            $answerList.hide().html('');
            $unselectedType.show();
            return;
        }

        $.ajax({
            dataType: 'html',
            url: ajaxNewTypeUrl + '/' + type,
            success: function(data) {
                $unselectedType.hide();
                $answerList.html(data).show();
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

        if (!confirm('Вы точно хотите удалить данный вариант?')) {
            return false;
        }

        var $this = $(this),
            type = $answerTypeSelect.length
                ? $answerTypeSelect.find('option:selected').val()
                : $('input[name="question[type]"]').val();

        removeAnswerRow($this, type);

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

            if ($o.data('id')) {
                $updateInfo.show();
            }

            $(this).remove();
        });
    }

    function focusInLastAnswer() {
        $('.answer-table tr:last-child .answer-text').focus();
    }

});


