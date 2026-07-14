if (typeof jQuery === "undefined") {
    throw new Error("jQuery plugins need to be before this file");
}

$(function() {
    "use strict";
    $.AdminAlpino.browser.activate();    
    $.AdminAlpino.select.activate();

    setTimeout(function() {
        $('.page-loader-wrapper').fadeOut();
    }, 50);
});

$.AdminAlpino = {};
$.AdminAlpino.options = {
    colors: {
        red: '#ec3b57',
        pink: '#E91E63',
        purple: '#ba3bd0',
        deepPurple: '#673AB7',
        indigo: '#3F51B5',
        blue: '#2196f3',
        lightBlue: '#03A9F4',
        cyan: '#00bcd4',
        green: '#4CAF50',
        lightGreen: '#8BC34A',
        yellow: '#ffe821',
        orange: '#FF9800',
        deepOrange: '#f83600',
        grey: '#9E9E9E',
        blueGrey: '#607D8B',
        black: '#000000',
        blush: '#dd5e89',
        white: '#ffffff'
    },
    leftSideBar: {
        scrollColor: 'rgba(0,0,0,0.5)',
        scrollWidth: '4px',
        scrollAlwaysVisible: false,
        scrollBorderRadius: '0',
        scrollRailBorderRadius: '0'
    }    
}


/* Form - Select - Function ======*/
$.AdminAlpino.select = {
    activate: function() {
        if ($.fn.selectpicker) {
            $('select:not(.ms)').selectpicker();
        }
    }
}
/* Browser - Function =======*/
var edge = 'Microsoft Edge';
var ie10 = 'Internet Explorer 10';
var ie11 = 'Internet Explorer 11';
var opera = 'Opera';
var firefox = 'Mozilla Firefox';
var chrome = 'Google Chrome';
var safari = 'Safari';

$.AdminAlpino.browser = {
    activate: function() {
        var _this = this;
        var className = _this.getClassName();

        if (className !== '') $('html').addClass(_this.getClassName());
    },
    getBrowser: function() {
        var userAgent = navigator.userAgent.toLowerCase();

        if (/edge/i.test(userAgent)) {
            return edge;
        } else if (/rv:11/i.test(userAgent)) {
            return ie11;
        } else if (/msie 10/i.test(userAgent)) {
            return ie10;
        } else if (/opr/i.test(userAgent)) {
            return opera;
        } else if (/chrome/i.test(userAgent)) {
            return chrome;
        } else if (/firefox/i.test(userAgent)) {
            return firefox;
        } else if (!!navigator.userAgent.match(/Version\/[\d\.]+.*Safari/)) {
            return safari;
        }

        return undefined;
    },
    getClassName: function() {
        var browser = this.getBrowser();

        if (browser === edge) {
            return 'edge';
        } else if (browser === ie11) {
            return 'ie11';
        } else if (browser === ie10) {
            return 'ie10';
        } else if (browser === opera) {
            return 'opera';
        } else if (browser === chrome) {
            return 'chrome';
        } else if (browser === firefox) {
            return 'firefox';
        } else if (browser === safari) {
            return 'safari';
        } else {
            return '';
        }
    }
}
