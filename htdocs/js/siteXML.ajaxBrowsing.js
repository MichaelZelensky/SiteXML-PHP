/*
*
* SiteXML ajax browsing
*
* (c) 2015 Michael Zelensky
*
* requires jQuery
*
* */

$(function () {
    siteXML = {};

    siteXML.init = function () {
        siteXML.loadXML('/?sitexml');
        $(window).on('siteXML.xml.loaded', function () {
            siteXML.start();
        });
    };

    siteXML.start = function () {
        if (siteXML.xml === undefined) return;
        $('a[pid]').click(function(){

            /*******/
            //how to load the content for the page's content zones???
            /*******/

            console.log($(this).attr('pid'));
            return true;
        });
    };

    //
    siteXML.loadXML = function(path) {
        var xhr,
            me = this;
        if (window.XMLHttpRequest) { // Mozilla, Safari, IE7+ ...
            xhr = new XMLHttpRequest();
        } else if (window.ActiveXObject) { // IE 6 and older
            xhr = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xhr.open('GET', path);
        xhr.send();
        xhr.onload = function () {
            me.xmlIsLoaded = true;
            me.xml = this.responseXML;
            $(window).trigger('siteXML.xml.loaded');
        }
    };

    siteXML.init();

});