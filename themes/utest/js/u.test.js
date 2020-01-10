function TestPassage(testId, modUrl) {
    // Элементы
    this.$errContainer = $('#test-errors');
    this.$startBtn = $('#test-run');
    this.$nextBtn = $('#test-next');
    this.$finishBtn = $('#test-end');
    this.$testForm = $('#test-form');
    this.$pagination = $('#test-pagination');
    this.$testWrapper = $('.test-wrapper');
    this.$testStarter = $('#test-starter');
    this.$number = $('#q-cur-count');
    this.$text = $('#test-q-text');
    this.$variants = $('#test-form-variants');

    // Переменные
    this.testId = testId;
    this.modUrl = modUrl;
    this.url = modUrl + '/ajax/test-' + testId;
    this.ajaxStartUrl = this.url + '/start';
    this.ajaxGoToUrl = this.url + '/goto';
    this.ajaxFinishUrl = this.url + '/finish';

    // Обработчики
    this.handlerStart();
    this.handlerNext();
    this.handlerFinish();
    this.handlerPagination();
}

TestPassage.prototype.handlerStart = function() {
    var self = this;
    this.$startBtn.on('click', function(e) {
        e.preventDefault();
        self.start();
    });
};

TestPassage.prototype.handlerNext = function() {
    var self = this;
    this.$nextBtn.on('click', function(e) {
        e.preventDefault();
        self.goto('next');
    });
};

TestPassage.prototype.handlerFinish = function() {
    var self = this;
    this.$finishBtn.on('click', function(e) {
        e.preventDefault();
        self.finish();
    });
};

TestPassage.prototype.handlerPagination = function() {
    var self = this;
    this.$pagination.find('a').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);

        if ($this.parent().hasClass('active')) {
            return;
        }

        self.goto($this.data('num'));
    });
};

TestPassage.prototype.start = function() {
    var self = this;
    this.clearErrors();

    $.ajax({
        dataType: 'json',
        url: this.ajaxStartUrl,
        success: function(data) {
            if (data.status === 'OK') {
                var q = data.question;
                self.$number.text(q.cur_num);
                self.$text.html(q.text);
                self.$variants.html(q.variants);
                self.$testStarter.remove();
                self.$testWrapper.fadeIn(250);
                hljs.initHighlightingOnLoad();
            }
            else {
                self.showErrors(data.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            self.showErrors('Ошибка при запуске теста.', textStatus + ' | ' + errorThrown);
        },
    });
};

TestPassage.prototype.goto = function(number) {
    var self = this;
    this.clearErrors();

    $.ajax({
        dataType: 'json',
        url: this.ajaxGoToUrl + '/' + number,
        success: function(data) {
            if (data.status === 'OK') {
                var q = data.question;
                self.$number.text(q.cur_num);
                self.$text.html(q.text);
                self.$variants.html(q.variants);
                self.$pagination.find('.active').removeClass('active');
                self.$pagination.find('a[data-num="' + q.cur_num + '"]').parent().addClass('active');
                hljs.initHighlightingOnLoad();

                /*$('#test-q-text').html(data.text);
                $('#test-variants').html(data.answer);
                $('.test-wrapper').fadeIn(200);
                $('.test-q-paginator .active').removeClass('active');
                $('.test-q-paginator a.q-' + data.cur_num).parent().addClass('active');
                $('#q-cur-count').text(data.cur_num);
                tNext.attr('data-num', data.cur_num + 1);
                if (data.is_last) {
                    tNext.fadeOut(150).addClass('hidden-next');
                }
                else if (tNext.hasClass('hidden-next')) {
                    tNext.fadeIn(150).removeClass('hidden-next');
                }*/
            }
            else {
                self.showErrors(data.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            self.showErrors('Ошибка при загрузке вопроса.', textStatus + ' | ' + errorThrown);
        },
    });
};

TestPassage.prototype.finish = function() {
    var self = this;
    this.clearErrors();

    // https://stackoverflow.com/questions/23287067/converting-serialized-forms-data-to-json-object

    $.ajax({
        dataType: 'json',
        url: this.ajaxFinishUrl,
        success: function(data) {
            if (data.status === 'OK') {
                $('.test-wrapper').fadeOut(150, function() {
                    $(this).html(data.result).fadeIn(250);
                });
            }
            else {
                self.showErrors(data.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            self.showErrors('Ошибка при завершении теста.', textStatus + ' | ' + errorThrown);
        },
    });
};

TestPassage.prototype.showErrors = function(msg, techInfo, inAlert) {
    if (inAlert) {
        prompt(msg, techInfo);
    }
    else {
        this.$errContainer.html(msg + '<br/><b>' + techInfo + '</b>').fadeIn(250);
    }
};

TestPassage.prototype.clearErrors = function() {
    this.$errContainer.hide().html('');
};

/*$(document).ready(function() {

    var modUrl = $('input[name="url"]').val();

    var $errContainer = $('#test-errors'),
        url = $('input[name="url"]').val(),
        ajaxStartUrl = url + '/ajax/start',
        ajaxGoToUrl = url + '/ajax/goto',
        ajaxFinishUrl = url + '/ajax/finish';

    $('#test-run').on('click', function(e) {
        e.preventDefault();

        var tid = $(this).data('tid');

        $.ajax({
            dataType: 'json',
            url: modUrl + '/run/' + tid,
            success: function(data) {
                if (data.status === 'OK') {
                    $('#test-starter').fadeOut(150, function() {
                        $('#test-q-text').html(data.text);
                        $('#test-variants').html(data.answer);
                        $('.test-wrapper').fadeIn(200);
                    });
                }
                else {
                    alert(data.status_message);
                }
            },
            error: function() {
                alert('Error running test');
            },
        });
    });

    $('#test-next, .test-q-paginator a').click(function(e) {
        e.preventDefault();

        var tNext = $('#test-next'),
            num = $(this).attr('data-num'),
            str = $('#test-variants').serialize();

        $.ajax({
            dataType: 'json',
            url: modUrl + '/q/' + num + '?' + str,
            success: function(data) {
                if (data.status === 'OK') {
                    $('#test-q-text').html(data.text);
                    $('#test-variants').html(data.answer);
                    $('.test-wrapper').fadeIn(200);
                    $('.test-q-paginator .active').removeClass('active');
                    $('.test-q-paginator a.q-' + data.cur_num).parent().addClass('active');
                    $('#q-cur-count').text(data.cur_num);
                    tNext.attr('data-num', data.cur_num + 1);
                    if (data.is_last) {
                        tNext.fadeOut(150).addClass('hidden-next');
                    }
                    else if (tNext.hasClass('hidden-next')) {
                        tNext.fadeIn(150).removeClass('hidden-next');
                    }
                }
                else {
                    alert(data.status_message);
                }
            },
            error: function() {
                alert('Error loading question');
            },
        });
    });

    $('#test-end').click(function(e) {
        e.preventDefault();

        var str = $('#test-variants').serialize();

        //$('#app-header').html(str);return false;

        $.ajax({
            dataType: 'json',
            url: modUrl + '/end/' + '?' + str,
            success: function(data) {
                if (data.status === 'OK') {
                    $('.test-wrapper').fadeOut(150, function() {
                        $(this).html(data.result).fadeIn(200);
                    });
                }
                else {
                    alert(data.status_message);
                }
            },
            error: function() {
                alert('Error finish test');
            }
        });
    });

});*/