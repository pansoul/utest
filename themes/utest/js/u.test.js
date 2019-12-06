$(document).ready(function() {
    
    var modUrl = $('input[name="url"]').val();
    
    $('#test-run').click(function(e){
        e.preventDefault();
        
        var tid = $(this).data('tid');
        
        $.ajax({
            dataType: 'json',
            url: modUrl + '/run/' + tid,
            success: function(data)
            {
                if (data.status === 'OK') {
                    $('#test-starter').fadeOut(150,function(){
                        $('#test-q-text').html(data.text);
                        $('#test-variants').html(data.answer);
                        $('.test-wrapper').fadeIn(200);
                    });
                } else {
                    alert(data.status_message);
                }
            },
            error: function()
            {
                alert('Error running test');
            }
        });
    });
    
    
    $('#test-next, .test-q-paginator a').click(function(e){
        e.preventDefault();
        
        var tNext = $('#test-next'),
            num = $(this).attr('data-num'),
            str = $('#test-variants').serialize();            
    
        $.ajax({
            dataType: 'json',
            url: modUrl + '/q/' + num + '?' + str,
            success: function(data)
            {
                if (data.status === 'OK') {                
                    $('#test-q-text').html(data.text);
                    $('#test-variants').html(data.answer);
                    $('.test-wrapper').fadeIn(200);
                    $('.test-q-paginator .active').removeClass('active');
                    $('.test-q-paginator a.q-' + data.cur_num).parent().addClass('active');
                    $('#q-cur-count').text(data.cur_num);
                    tNext.attr('data-num', data.cur_num+1);
                    if (data.is_last)
                        tNext.fadeOut(150).addClass('hidden-next');
                    else if (tNext.hasClass('hidden-next'))
                        tNext.fadeIn(150).removeClass('hidden-next');
                } else {
                    alert(data.status_message);
                }
            },
            error: function()
            {
                alert('Error loading question');
            }
        });
    });
    
    
    $('#test-end').click(function(e){
        e.preventDefault();
        
        var str = $('#test-variants').serialize();  
        
        //$('#app-header').html(str);return false;
    
        $.ajax({
            dataType: 'json',
            url: modUrl + '/end/' + '?' + str,
            success: function(data)
            {
                if (data.status === 'OK') {                
                    $('.test-wrapper').fadeOut(150, function(){
                        $(this).html(data.result).fadeIn(200);
                    });
                } else {
                    alert(data.status_message);
                }
            },
            error: function()
            {
                alert('Error finish test');
            }
        });
    });

});