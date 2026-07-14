$(function() {
    "use strict";
    skinChanger();
    CustomScrollbar();
    initSparkline();
    initCounters();
    CustomPageJS();
});
// Sparkline
function initSparkline() {
    $(".sparkline").each(function() {
        var $this = $(this);
        $this.sparkline('html', $this.data());
    });
}

// Counters JS 
function initCounters() {
    $('.count-to').countTo();
}

//Skin changer
function skinChanger() {
    $('.right-sidebar .choose-skin li').on('click', function() {
        var $body = $('body');
        var $this = $(this);

        var existTheme = $('.right-sidebar .choose-skin li.active').data('theme');
        $('.right-sidebar .choose-skin li').removeClass('active');
        $body.removeClass('theme-' + existTheme);
        $this.addClass('active');
        $body.addClass('theme-' + $this.data('theme'));
    });
}

// All Custom Scrollbar JS
function CustomScrollbar() {    
    $('.right_menu .slim_scroll').slimscroll({
        height: 'calc(100vh - 30px)',
        color: 'rgba(0,0,0,0.1)',
        position: 'right',
        size: '2px',
        alwaysVisible: false,
        borderRadius: '3px',
        railBorderRadius: '0'
    });

    $('.cwidget-scroll').slimscroll({
        height: '306px',
        color: 'rgba(0,0,0,0.4)',
        size: '2px',
        alwaysVisible: false,
        borderRadius: '3px',
        railBorderRadius: '2px'
    });
    
    $('.right-sidebar .slim_scroll').slimscroll({
        height: 'calc(100vh - 100px)',
        color: 'rgba(0,0,0,0.4)',
        size: '2px',
        alwaysVisible: false,
        borderRadius: '3px',
        railBorderRadius: '0'
    });
   
}

function CustomPageJS() {
    $(".boxs-close").on('click', function(){
        var element = $(this);
        var cards = element.parents('.card');
        cards.addClass('closed').fadeOut();
    });

    // Theme Light and Dark  ============
    $('.theme-light-dark .t-dark').on('click', function() {
        $('body').toggleClass('menu_dark');
    });

    // Right bar open close  ============  

    $(".js-right-sidebar").on('click',function() {
        $(".right_menu #rightsidebar").toggleClass("open stretchRight").siblings().removeClass('open stretchRight');
        if ($(".right_menu #rightsidebar").hasClass('open')) {
            $('.overlay').fadeIn();
        } else {
            $('.overlay').fadeOut();
        }
    });

    $('.overlay').on('click',function() {
        $('.open.stretchRight').removeClass('open stretchRight');
        $(this).fadeOut();
    });

    // Search with sortcut menu ===    
    $(".btn_overlay").on('click',function(){
        $(".overlay_menu").fadeToggle(200);
        $(this).toggleClass('btn-open').toggleClass('btn-close');
    });
    
    $('.overlay_menu .btn').on('click', function(){
        $(".overlay_menu").fadeToggle(200);   
        $(".overlay_menu button.btn").toggleClass('btn-open').toggleClass('btn-close');
        open = false;
    });
    
    //=========
    $('.form-control').on("focus", function() {
        $(this).parent('.input-group').addClass("input-group-focus");
    }).on("blur", function() {
        $(this).parent(".input-group").removeClass("input-group-focus");
    });
}



$(document).ready(function() {

    $(".h-menu  > li").hover(function(e) {
        if ($(window).width() > 943) {
            $(this).children("ul").stop(true, false).fadeToggle(150);
            e.preventDefault();
        }
    });
    //If width is more than 943px dropdowns are displayed on hover    
    $(".h-menu  > li").on('click',function() {
        if ($(window).width() <= 943) {
            $(this).children("ul").fadeToggle(150);
        }
    });
    //If width is less or equal to 943px dropdowns are displayed on click (thanks Aman Jain from stackoverflow)

    $(".h-bars").on('click',function(e) {
        $(".h-menu").toggleClass('show-on-mobile');
        e.preventDefault();
    });
    //when clicked on mobile-menu, normal menu is shown as a list, classic rwd menu story (thanks mwl from stackoverflow)
});


// Wraptheme Website live chat widget js please remove on your project
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/5c6d4867f324050cfe342c69/default';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();