jQuery(document).ready(function($) {

    $(document).on('click', '.post-type-link a', function(event) {
        event.preventDefault();
        $('html, body').animate({
            scrollTop: $("#top").offset().top
        }, 600);
        $main = $('#main');
        $padding_size = $(window).height() - $('#top').height() - $('#full-page-title').outerHeight();
        $main.find('#content .single').html('<div class="working" style="padding-bottom:' + $padding_size + 'px"></div>');
        $clicked_id = $(this).parent('li').data('postid'); //this is the id number of the post link that is clicked in the accordian
        $sb_nav = $('.post_type-accordion');
        $sb_nav.find('li').removeClass('current');
        $sb_nav.find('*[data-postid="' + $clicked_id + '"]').addClass('current');
        $clicked_href = $(this).attr("href"); //this is the href of the post link that is clicked in the accordian
        $main_nav = $('#menu-main-menu');
        $main_nav.find('li').removeClass('current-menu-item');
        $main_nav.find('*[href="' + $clicked_href + '"]').parent('li').addClass('current-menu-item');
        $.ajax({
            url: ajaxposttype.ajaxurl,
            type: 'post',
            data: {
                action: 'jma_ajax_content',
                //query_vars: ajaxposttype.query_vars,
                postid: $clicked_id
            },
            success: function($html) {
                $html_array = $html.split('jmasp'); //explode the string from the php side

                $('#main, #full-page-title').find('h1').html($html_array[0]);
                $main.find('#content .single').html($html_array[1]); //.portfolio_item
                $('.jma-header-image-wrap').fadeOut(200, function() {
                    $(this).html($html_array[2])
                });
                $('.jma-header-image-wrap').fadeIn(200);

                $('.jma_nivo').each(function() {
                    $args = {
                        controlNav: false,
                        prevText: '',
                        nextText: '',

                    };
                    if ($(this).data('settings')) {
                        $pairs = $(this).data('settings').split('|');
                        var $i;
                        for ($i = 0; $i < $pairs.length; ++$i) {
                            $elements = $pairs[$i].split(':');
                            $args[$elements[0]] = $elements[1];
                        }
                    }
                    $(this).nivoSlider($args);
                });
            }
        });
    });
});