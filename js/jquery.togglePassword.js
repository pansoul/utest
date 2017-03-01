/*
* jQuery Show/Hide password plugin
*
* Copyright (c) 2013 Borovskih Pavel
* Dual licensed under the MIT and GPL licenses.
* Uses the same license as jQuery, see:
* http://jquery.org/license
*
* @version 1.0
*
* Example for use:
* $('.password-field').togglePassword({
*   wrapperClass: 'tp-wrapper',
*   linkShowText: 'show',
*   linkHideText: 'hide',
*   linkClass: 'tp-link',        
*   linkShowClass: 'tp-link-show',
*   linkHideClass: 'tp-link-hide',
*   gButton: false,
*   gButtonClass: 'tp-btn',
*   gButtonText: 'generate password',
*   gButtonPassLength: 8
* });
* 
* Options:
* wrapperClass (string) - name of class(es) for wrapper
* linkShowText (string) - text of link for showing of password
* linkHideText (string) - text of link for hidding of password
* linkClass (string) - name of class(es) for link
* linkShowClass (string) - set the name of class when password will be shown
* linkHideClass (string) - set the name of class when password will be hidden
* gButton (bool) - add a button, which will be generate a random password
* gButtonClass (string) - name of class for button
* gButtonText (string) - text for button
* gButtonPassLength (integer) - length of new password
* 
* The structure:
* After initializing of pligun for <input type="password" /> the final 
* DOM-structure will:
* 
* <div class="[wrapperClass]">
*   <a href="#" class="[linkClass][linkShowClass]">[linkShowText]</a>
*   <input type="text" />
*   <input type="password" /> // a native input
* </div>
* <input class="[gButtonClass]">[gButtonText]</span>
*/

(function($) {

    $.fn.togglePassword = function(options) {
        
        var o = $.extend({}, $.fn.togglePassword.defaults, options),
            id = 1;

        return this.each(function() {

            var $this = $(this),
                curClasses = $this.attr('class'),
                curName = $this.attr('name'),
                curValue = $this.attr('value'),
                linkedId = 'tp-' + id;
                
            id++;            
            $this.wrap('<span class=' + o.wrapperClass + '></span>');  
            $this.attr('data-linked-id', linkedId);
            var $parent = $this.parent();
                
            $('<input/>', {
                type: 'text',
                name: curName,
                value: curValue,
                'data-linked-id': linkedId,
                'class': curClasses,
                css: {
                    display: 'none'
                }
            }).appendTo($parent);
            
            var $inputText = $parent.find('input[type="text"]'),
                $inputPass = $parent.find('input[type="password"]');
                
            $('<a/>', {
                href: '#',
                title: 'Поменять режим видимости пароля',
                'class': o.linkClass + ' ' + o.linkHideClass, // default a password is hidden
                click: function(e) {
                    e.preventDefault();
                    
                    if ($inputText.css('display') === 'none') {
                        $inputPass.hide();
                        $inputText.show();
                    } else {
                        $inputPass.show();
                        $inputText.hide();
                    }

                    if (!$(this).hasClass(o.linkShowClass))
                        $(this).text(o.linkHideText);
                    else
                        $(this).text(o.linkShowText);
                    $(this).toggleClass(o.linkShowClass);    
                    $(this).toggleClass(o.linkHideClass);    
                },
                text: o.linkShowText
            }).appendTo($parent);
            
            $('input[data-linked-id="' + linkedId + '"]').keyup(function(){                
                $('input[data-linked-id="' + linkedId + '"]').val($(this).val());                
            });
            
            var $link = $parent.find('a');
            
            if (o.gButton) {                
                $('<span/>', {
                    text: o.gButtonText,
                    title: 'Автоматическая генерация пароля',
                    'class': o.gButtonClass,
                    click: function(e) {
                        e.preventDefault();
                        
                        var keylist = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
                        var newpassword = '';
                        for (i = 0; i < o.gButtonPassLength; i++)
                            newpassword += keylist.charAt(Math.floor(Math.random() * keylist.length));
                        $('input[data-linked-id="' + linkedId + '"]').val(newpassword);  
                        $inputPass.hide();
                        $inputText.show();
                        $link.text(o.linkHideText).removeClass(o.linkHideClass).addClass(o.linkShowClass);
                    }
                }).insertAfter($parent);
            }
            
        });

    }

    // default options
    $.fn.togglePassword.defaults = {
        wrapperClass: 'tp-wrapper',
        linkShowText: 'show',
        linkHideText: 'hide',
        linkClass: 'tp-link',        
        linkShowClass: 'tp-link-show',
        linkHideClass: 'tp-link-hide',
        gButton: false,
        gButtonClass: 'tp-btn',
        gButtonText: 'generate password',
        gButtonPassLength: 8
    };

})(jQuery);

