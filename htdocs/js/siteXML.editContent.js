/**
 * SiteXML content edit script
 *
 * requires jQuery
 */

$(function() {
    var content = document.getElementsByClassName('siteXML-content');

    window.siteXMLsaveTimeout = [];

    for (var i = 0, n = content.length; i < n; i++) {
        $(content[i]).on('click', function () {
            $(this).prop('contenteditable', true).focus();
        });
        $(content[i]).on('input', function () {
            //send content back to be saved
            var content = this.innerHTML,
                cid = this.getAttribute('cid'),
                cidS = 'cid' + cid;

            if (window.siteXMLsaveTimeout[cidS]) {
                clearTimeout(window.siteXMLsaveTimeout[cidS]);
            }
            window.siteXMLsaveTimeout[cidS] = setTimeout(function(){
                $.ajax({
                    method : 'POST',
                    url : '/',
                    data : {
                        cid : cid,
                        content : content
                    }
                });
            }, 3000);
        });
    }
});