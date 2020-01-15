function TestPassage(testId, modUrl, options) {
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
    this.options = options;
    this.url = modUrl + '/ajax/test-' + testId;
    this.ajaxStartUrl = this.url + '/start';
    this.ajaxGoToUrl = this.url + '/goto';
    this.ajaxFinishUrl = this.url + '/finish';

    // Обработчики
    this.handlerStart();
    this.handlerNext();
    this.handlerFinish();
    this.handlerPagination();
    this.handlerForm();
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
        if (confirm('Завершить тестирование?')) {
            self.finish();
        }
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
                self._showQuestion(data.question);
                self.$testStarter.remove();
                self.$testWrapper.fadeIn(250);
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

/**
 * Функция одновременно загружает новый и сохраняет выбранные варианты текущего вопроса
 * @param number
 */
TestPassage.prototype.goto = function(number) {
    var self = this;
    this.clearErrors();

    $.ajax({
        dataType: 'json',
        type: 'POST',
        url: this.ajaxGoToUrl + '/' + number,
        data: self.$testForm.serializeArray(),
        success: function(data) {
            if (data.status === 'OK') {
                self._showQuestion(data.question);
            }
            else {
                self.showErrors(data.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            self.showErrors('Ошибка при обработке вопроса.', textStatus + ' | ' + errorThrown);
        },
    });
};

TestPassage.prototype.finish = function() {
    var self = this;
    this.clearErrors();

    $.ajax({
        dataType: 'json',
        type: 'POST',
        url: this.ajaxFinishUrl,
        data: self.$testForm.serializeArray(),
        success: function(data) {
            if (data.status === 'OK') {
                self.$testWrapper.fadeOut(150, function() {
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
        alert(msg);
    }
    else {
        this.$errContainer.html(msg).fadeIn(250);
    }
    if (techInfo) {
        console.debug(techInfo);
    }
};

TestPassage.prototype.clearErrors = function() {
    this.$errContainer.hide().html('');
};

TestPassage.prototype.handlerForm = function() {
    this.$testForm.on('submit', function(){
        return false;
    });
}

TestPassage.prototype._highlightReinit = function() {
    hljs.initHighlighting.called = false;
    hljs.initHighlighting();
}

TestPassage.prototype._checkNextBtnVisible = function(curNum) {
    if (curNum == this.options['count_q']) {
        this.$nextBtn.hide();
    } else {
        this.$nextBtn.show();
    }
}

TestPassage.prototype._showQuestion = function(q) {
    if (!q) {
        return;
    }

    this.$number.text(q.cur_num);
    this.$text.html(q.text);
    this.$variants.html(q.variants);
    this.$pagination.find('.pagination__item.active').removeClass('active');
    this.$pagination.find('.pagination__item--' + q.cur_num).addClass('active');
    this.$pagination.find('.pagination__item__link--' + q.cur_num).addClass('answered');
    this._highlightReinit();
    this._checkNextBtnVisible(q.cur_num);
}