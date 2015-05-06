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
        $(content[i])
            .on('input blur', function (e) {
                //send content back to be saved
                var content = this.innerHTML,
                    cid = this.getAttribute('cid'),
                    cidS = 'cid' + cid;

                if (window.siteXMLsaveTimeout[cidS]) {
                    clearTimeout(window.siteXMLsaveTimeout[cidS]);
                }

                function save () {
                    $.ajax({
                        method : 'POST',
                        url : '/',
                        data : {
                            cid : cid,
                            content : content
                        }
                    });
                }
                if (e.type === 'input') {
                    window.siteXMLsaveTimeout[cidS] = setTimeout(save, 3000);
                } else {
                    save();
                }
            });
    }
});