/**
 * SiteXML content edit script
 *
 * requires jQuery
 */

$(function() {
    var content = document.getElementsByClassName('siteXML-content');

    for (var i = 0, n = content.length; i < n; i++) {
        $(content[i]).on('click', function () {
            $(this).prop('contenteditable', true).focus();
        });
        $(content[i]).on('input', function () {
            //send content back to be saved
            var content = this.innerHTML,
                cid = this.getAttribute('cid');

            if (window.siteXMLsaveTimeout) {
                clearTimeout(window.siteXMLsaveTimeout);
            }
            window.siteXMLsaveTimeout = setTimeout(function(){
                $.ajax({
                    method : 'POST',
                    url : '',
                    data : {
                        cid : cid,
                        content : content
                    }
                });
            }, 3000);
        });
    }
});