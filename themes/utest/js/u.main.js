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

});