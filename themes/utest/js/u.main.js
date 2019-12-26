$(document).ready(function() {

    hljs.initHighlightingOnLoad();

    $('.ckeditor').ckeditor({
        height: 300,
    });

    $('[rel="tooltip"]').tooltip();

    $('.password-field').togglePassword({
        linkClass: 'tp-link glyphicon',
        linkShowClass: 'glyphicon-eye-open',
        linkHideClass: 'glyphicon-eye-close',
        linkShowText: '',
        linkHideText: '',
        gButton: true,
        gButtonClass: 'tp-btn glyphicon glyphicon-refresh',
        gButtonText: '',
    });

    $('#print').click(function(e) {
        e.preventDefault();
        window.print();
    });

    $('.formaction').uFormTable({
        elements: {
            table: '.table-hover',
            checkbox: '.check-one',
            checkboxAll: '#check-all'
        },
        checkboxDependentItems: [
            '.delete-selected',
            '.newpass-selected',
        ],
        confirmItems: {
            '.btn-delete': 'Вы точно хотите удалить данную запись?',
            '.delete-selected': 'Вы уверены, что хотите удалить отмеченные записи?',
            '.newpass-selected': 'Вы уверены, что хотите сгенерировать выбранным пользователям новые пароли?'
        }
    });

    $('.table-sort').stupidtable();

});