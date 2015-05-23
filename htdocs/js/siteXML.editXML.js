/**
 * SiteXML XML edit script
 *
 * requires jQuery
 *
 * @author Michael Zelensky 2015
 */

$(function(){
    'use strict';

    var app = {
        els : {},
        state : {}
    };

    //
    app.init = function () {
        var container = $('<div id="siteXML-editXML-container" class="yui3-cssreset collapsed">' +
            '<div class="panel-button"></div>' +
            '<div class="tree"></div>' +
            '<div class="properties">' +
            '<a class="close">x</a>' +
            '<table>' +
            '<tr class="properties-id"><td>Id</td><td><input type="text"></td></tr>' +
            '<tr class="properties-default"><td>Default</td><td><input type="checkbox"></td></tr>' +
            '<tr class="properties-startPage"><td>Start page</td><td><input type="checkbox"></td></tr>' +
            '<tr class="properties-nonavi"><td>Nonavi</td><td><input type="checkbox"></td></tr>' +
            '<tr class="properties-name"><td>Name</td><td><input type="text"></td></tr>' +
            '<tr class="properties-alias"><td>Alias</td><td><input type="text"></td></tr>' +
            '<tr class="properties-theme"><td>Theme</td><td><input type="text"></td></tr>' +
            '<tr class="properties-dir"><td>Dir</td><td><input type="text"></td></tr>' +
            '<tr class="properties-file"><td>File</td><td><input type="text"></td></tr>' +
            '<tr class="properties-type"><td>Type</td><td><input type="text"></td></tr>' +
            '<tr class="properties-content"><td>Content</td><td><input type="text"></td></tr>' +
            '<tr class="properties-nodeContent"><td>Node content</td><td><input type="text"></td></tr>' +
            '<tr class="properties-charset"><td>Charset</td><td><input type="text"></td></tr>' +
            '<tr class="properties-http-equiv"><td>Http-equiv</td><td><input type="text"></td></tr>' +
            '<tr class="properties-scheme"><td>Scheme</td><td><input type="text"></td></tr>' +
            '</table>' +
            '</div></div>')
            .css({
                zIndex : this.getMaxZIndex() + 1
            });
        container.find('input').after(' <span class="not-specified">Not specified</span>');
        container.find('tr').addClass('property');
        $('body').append(container);
        this.els.container = container;
        this.cacheEls();
        this.loadXML();
    };

    //
    app.cacheEls = function () {
        this.els.propertiesId = this.find('.properties-id');
        this.els.propertiesDefault = this.find('.properties-default');
        this.els.propertiesStartPage = this.find('.properties-startPage');
        this.els.propertiesNonavi = this.find('.properties-nonavi');
        this.els.propertiesName = this.find('.properties-name');
        this.els.propertiesAlias = this.find('.properties-alias');
        this.els.propertiesTheme = this.find('.properties-theme');
        this.els.propertiesDir = this.find('.properties-dir');
        this.els.propertiesFile = this.find('.properties-file');
        this.els.propertiesContent = this.find('.properties-content');
        this.els.propertiesType = this.find('.properties-type');
        this.els.propertiesHttpEquiv = this.find('.properties-http-equiv');
        this.els.propertiesScheme = this.find('.properties-scheme');
        this.els.propertiesCharset = this.find('.properties-charset');
        this.els.propertiesNodeContent = this.find('.properties-nodeContent');
    };

    //
    app.loadXML = function () {
        var me = this;
        $.ajax({
            method : 'GET',
            url : '/.site.xml'
        }).success(function (xml) {
            me.xml = xml;
            me.buildTree();
            me.bindEvents();
        });
    };

    //
    app.bindEvents = function () {
        var me = this;
        this.els.container.on('click', function (e) {
            var ul, li, newli, newNode, id, parser, parentNode, afterNode, beforeNode,
                doPrepend = false,
                beforeEl = false,
                afterEl = false,
                el = $(e.target);
            if (el.is('.expand-button')) {
                li = el.closest('li');
                li.toggleClass('collapsed');
            } else if (el.is('.node-name')) {
                li = el.closest('li');
                me.els.container.find('.selected').removeClass('selected');
                li.addClass('selected');
                me.showProperties(li);
            } else if (el.is('.panel-button')) {
                me.els.container.toggleClass('collapsed');
            } else if (el.is('.button')) {
                li = el.closest('li');
                ul = li.children('ul');
                if (!ul.length) {
                    ul = $('<ul></ul>');
                    el.closest('li').append(ul);
                }
                if (el.is('.delete')) { //delete element
                    li.removeClass('collapsed');
                    if (confirm('Delete element with all children?')) {
                        $(me.getNode(li.data('nodename'), li.data('id'))).remove();
                        li.remove();
                    } else {
                        li.addClass('collapsed');
                    }
                } else { //add elements
                    parser = new DOMParser();
                    if (el.is('.addPage')) { //add page preparations
                        id = me.getMaxId('page') + 1;
                        newNode = parser.parseFromString('<page id="' + id + '" />', "application/xml");

                    } else if (el.is('.addMeta')) { //add meta preparations
                        afterEl = ul.find('[data-nodename="meta"]');
                        if (afterEl.length) {
                            parentNode = me.getNode(li.data('nodename'), li.data('id'));
                            afterEl = afterEl.last();
                            afterNode = $(parentNode).find('meta').last();
                        } else {
                            afterEl = false;
                            doPrepend = true;
                        }
                        newNode = parser.parseFromString('<meta />', "application/xml");

                    } else if (el.is('.addContent')) { //add content preparations
                        id = me.getMaxId('content') + 1;
                        afterEl = ul.find('[data-nodename="content"]');
                        if (afterEl.length) {
                            parentNode = me.getNode(li.data('nodename'), li.data('id'));
                            afterEl = afterEl.last();
                            afterNode = $(parentNode).find('content').last();
                        } else {
                            afterEl = false;
                            doPrepend = true;
                        }
                        newNode = parser.parseFromString('<content id="' + id + '" />', "application/xml");

                    } else if (el.is('.addTheme')) { //add theme preparations
                        id = me.getMaxId('theme') + 1;
                        newNode = parser.parseFromString('<theme id="' + id + '" />', "application/xml");
                    }
                    newli = me.renderItem(newNode.childNodes[0]);
                    newli = $(newli);
                    if (afterEl) {
                        $(afterNode).after(newNode.childNodes[0]);
                        afterEl.after(newli);
                    } else if (doPrepend) {
                        $(me.getNode(li.data('nodename'), li.data('id'))).prepend(newNode.childNodes[0]);
                        ul.prepend(newli);
                    } else {
                        $(me.getNode(li.data('nodename'), li.data('id'))).append(newNode.childNodes[0]);
                        ul.append(newli);
                    }
                    li.removeClass('collapsed');
                    me.blink(newli);
                }
                me.saveXML();
                return false;
            } else if (el.is('.close')) {
                me.els.container.removeClass('withProperties');
            }
        });
        me.els.container.find('.properties').find('input').on('input change', function () {
            var li,
                sn = $(me.state.selectedNode),
                v = this.value,
                cl = 'not-specified',
                save = true;
            switch (this) {
                case me.els.propertiesId.find('input')[0]:
                    me.state.selectedNode.id = v;
                    break;
                case me.els.propertiesName.find('input')[0]:
                    me.els.propertiesName.removeClass(cl);
                    sn.attr('name', v);
                    break;
                case me.els.propertiesAlias.find('input')[0]:
                    me.els.propertiesAlias.removeClass(cl);
                    sn.attr('alias', v);
                    break;
                case me.els.propertiesType.find('input')[0]:
                    me.els.propertiesType.removeClass(cl);
                    sn.attr('type', v);
                    break;
                case me.els.propertiesDir.find('input')[0]:
                    me.els.propertiesDir.removeClass(cl);
                    sn.attr('dir', v);
                    break;
                case me.els.propertiesFile.find('input')[0]:
                    me.els.propertiesFile.removeClass(cl);
                    sn.attr('file', v);
                    break;
                case me.els.propertiesTheme.find('input')[0]:
                    me.els.propertiesTheme.removeClass(cl);
                    sn.attr('theme', v);
                    break;
                case me.els.propertiesContent.find('input')[0]:
                    me.els.propertiesContent.removeClass(cl);
                    sn.attr('content', v);
                    break;
                case me.els.propertiesCharset.find('input')[0]:
                    me.els.propertiesCharset.removeClass(cl);
                    sn.attr('charset', v);
                    break;
                case me.els.propertiesHttpEquiv.find('input')[0]:
                    me.els.propertiesHttpEquiv.removeClass(cl);
                    sn.attr('http-equiv', v);
                    break;
                case me.els.propertiesScheme.find('input')[0]:
                    me.els.propertiesScheme.removeClass(cl);
                    sn.attr('scheme', v);
                    break;
                case me.els.propertiesStartPage.find('input')[0]:
                    if (this.checked) {
                        me.clearStartPage();
                        sn.attr('startpage', 'yes');
                        me.els.propertiesStartPage.removeClass(cl);
                    } else {
                        sn.removeAttr('startpage');
                        me.els.propertiesStartPage.addClass(cl);
                    }
                    break;
                case me.els.propertiesDefault.find('input')[0]:
                    if (this.checked) {
                        me.clearDefaultTheme();
                        me.els.propertiesDefault.removeClass(cl);
                        sn.attr('default', 'yes');
                    } else {
                        me.els.propertiesDefault.addClass(cl);
                        sn.removeAttr('default');
                    }
                    break;
                case me.els.propertiesNonavi.find('input')[0]:
                    if (this.checked) {
                        me.els.propertiesNonavi.removeClass(cl);
                        sn.attr('nonavi', 'yes');
                    } else {
                        me.els.propertiesNonavi.addClass(cl);
                        sn.removeAttr('nonavi');
                    }
                    break;
                default :
                    save = false;
                    break;
            }
            if (save) {
                me.saveXML();
            }
        });
    };

    //
    app.clearStartPage = function () {
        $(this.xml).find('page[startpage=yes]').removeAttr('startpage');
    };

    //
    app.clearDefaultTheme = function () {
        $(this.xml).find('theme[default=yes]').removeAttr('default');
    };

    //
    app.showProperties = function (el) {
        var id = el.data('id'),
            nodeName = el.data('nodename'),
            nodeNameLC = nodeName.toLowerCase(),
            node = this.getNode(nodeName, id);
        this.state.selectedLi = el;
        this.state.selectedNode = node;
        this.els.container.addClass('withProperties');
        this.els.container.find('table').show();
        this.hideAllPropertyFields();
        this.setAllProperties(node);
        if (nodeNameLC === 'site') {
            this.els.propertiesName.show();
        } else if (nodeNameLC === 'page') {
            this.els.propertiesId.show();
            this.els.propertiesStartPage.show();
            this.els.propertiesNonavi.show();
            this.els.propertiesName.show();
            this.els.propertiesAlias.show();
            this.els.propertiesTheme.show();
        } else if (nodeNameLC === 'content') {
            this.els.propertiesId.show();
            this.els.propertiesName.show();
            this.els.propertiesType.show();
            this.els.propertiesNodeContent.show();
        } else if (nodeNameLC === 'theme') {
            this.els.propertiesId.show();
            this.els.propertiesDefault.show();
            this.els.propertiesDir.show();
            this.els.propertiesFile.show();
        } else if (nodeNameLC === 'meta') {
            this.els.propertiesName.show();
            this.els.propertiesContent.show();
            this.els.propertiesCharset.show();
            this.els.propertiesHttpEquiv.show();
            this.els.propertiesScheme.show();
        }
    };

    //
    app.setAllProperties = function (node) {
        var checked,
            cl = 'not-specified';
        if (node.hasAttribute('name')) {
            this.els.propertiesName.removeClass(cl);
        }
        this.els.propertiesName.find('input').val(node.getAttribute('name'));

        if (node.hasAttribute('id')) {
            this.els.propertiesId.removeClass(cl);
        }
        this.els.propertiesId.find('input').val(node.getAttribute('id'));

        if (node.hasAttribute('alias')) {
            this.els.propertiesAlias.removeClass(cl);
        }
        this.els.propertiesAlias.find('input').val(node.getAttribute('alias'));

        if (node.hasAttribute('dir')) {
            this.els.propertiesDir.removeClass(cl);
        }
        this.els.propertiesDir.find('input').val(node.getAttribute('dir'));

        if (node.hasAttribute('file')) {
            this.els.propertiesFile.removeClass(cl);
        }
        this.els.propertiesFile.find('input').val(node.getAttribute('file'));

        if (node.hasAttribute('type')) {
            this.els.propertiesType.removeClass(cl);
        }
        this.els.propertiesType.find('input').val(node.getAttribute('type'));

        if (node.hasAttribute('content')) {
            this.els.propertiesContent.removeClass(cl);
        }
        this.els.propertiesContent.find('input').val(node.getAttribute('content'));

        if (node.hasAttribute('theme')) {
            this.els.propertiesTheme.removeClass(cl);
        }
        this.els.propertiesTheme.find('input').val(node.getAttribute('theme'));

        this.els.propertiesNodeContent
            .removeClass(cl)
            .find('input').val(node.textContent);

        if (node.hasAttribute('scheme')) {
            this.els.propertiesScheme.removeClass(cl)
        }
        this.els.propertiesScheme.find('input').val(node.getAttribute('scheme'));

        if (node.hasAttribute('http-equiv')) {
            this.els.propertiesHttpEquiv.removeClass(cl)
        }
        this.els.propertiesHttpEquiv.find('input').val(node.getAttribute('http-equiv'));

        if (node.hasAttribute('charset')) {
            this.els.propertiesCharset.removeClass(cl)
        }
        this.els.propertiesCharset.find('input').val(node.getAttribute('charset'));

        if (node.hasAttribute('default')) {
            this.els.propertiesDefault.removeClass(cl);
            checked = ($(node).attr('default').toLowerCase() === 'yes');
        } else {
            checked = false;
        }
        this.els.propertiesDefault.find('input').prop('checked', checked);

        if (node.hasAttribute('startpage')) {
            this.els.propertiesStartPage.removeClass(cl);
            checked = ($(node).attr('startpage').toLowerCase() === 'yes');
        } else {
            checked = false;
        }
        this.els.propertiesStartPage.find('input').prop('checked', checked);

        if (node.hasAttribute('nonavi')) {
            this.els.propertiesNonavi.removeClass(cl);
            checked = ($(node).attr('nonavi').toLowerCase() === 'yes');
        } else {
            checked = false;
        }
        this.els.propertiesNonavi.find('input').prop('checked', checked);
    };

    //
    app.getNode = function (nodeName, id) {
        var xpath;
        if (id) {
            xpath = "//" + nodeName + "[@id='" + id + "']";
        } else {
            xpath = "//" + nodeName;
        }
        var node = this.xpath(xpath, true);
        return node;
    };

    //
    app.hideAllPropertyFields = function () {
        this.els.container.find('tr.property')
            .addClass('not-specified')
            .hide();
    };

    //
    app.buildTree = function () {
        var me = this,
            html = renderBranch (this.xml);
        this.els.container.find('.tree').append(html);
        function renderBranch (parent) { //recursive
            var child,
                i = 0,
                children = parent.children,
                n = children.length,
                html = '';
            if (n > 0) {
                html = '<ul>';
                for (i; i < n; i++) {
                    child = children[i];
                    html += me.renderItem(child)
                        + renderBranch(child)
                        + "</li>";
                }
                html += "</ul>";
            }

            return html;
        }
    };

    //
    app.renderItem = function (child) {
        var name, dataAttributes, cl, buttons, nameLC,
            id = '',
            html = '',
            pb = ' <a href="#" class="addPage button" title="Add page">[+p]</a>',
            mb = ' <a href="#" class="addMeta button" title="Add meta">[+m]</a>',
            cb = ' <a href="#" class="addContent button" title="Add content">[+c]</a>',
            minusb = ' <a href="#" class="delete button" title="Remove">[-]</a>',
            tb = ' <a href="#" class="addTheme button" title="Add theme">[+t]</a>';
        nameLC = child.nodeName.toLowerCase();
        if (['site', 'theme', 'page'].indexOf(nameLC) >= 0) {
            cl = 'collapsed collapsible';
        } else {
            cl = '';
        }
        if (nameLC === 'site') {
            buttons = pb + mb + tb;
        } else if (nameLC === 'page') {
            buttons = pb + mb + cb + minusb;
        } else if (nameLC === 'theme') {
            buttons = mb + cb + minusb;
        } else if (nameLC === 'meta' || nameLC === 'content') {
            buttons = minusb;
        } else {
            buttons = '';
        }
        dataAttributes = 'data-id="' + child.id + '"'
            + 'data-nodename="' + child.nodeName + '"';
        id = child.id;
        id = (id) ? ' id="' + id + '"' : '';
        name = child.getAttribute('name');
        name = (name) ? ' name="' + name + '"' : '';
        html += '<li class="' + cl + '" ' + dataAttributes + '>'
            + '<span class="expand-button"></span>'
            + '<span class="node-name">'
            + child.nodeName
            + id
            + name
            + '</span>'
            + buttons;

        return html;
    };

    //
    app.getMaxZIndex = function () {
        var zIndex,
            z = 0,
            all = document.getElementsByTagName('*');
        for (var i = 0, n = all.length; i < n; i++) {
            zIndex = document.defaultView.getComputedStyle(all[i],null).getPropertyValue("z-index");
            zIndex = parseInt(zIndex, 10);
            z = (zIndex) ? Math.max(z, zIndex) : z;
        }
        return z;
    };

    //
    app.find = function(selector) {
        return this.els.container.find(selector);
    };

    //
    app.getMaxId = function (what) {
        var xpath, nodes, node,
            id = 0;
        if (what === 'page') {
            xpath = '//page';
        } else if (what === 'content') {
            xpath = '//content';
        } else if (what === 'theme') {
            xpath = '//theme';
        }
        nodes = this.xpath(xpath);
        node = nodes.iterateNext();
        while (node) {
            id = Math.max(id, parseInt(node.getAttribute('id')));
            node = nodes.iterateNext();
        }
        return id;
    };

    //
    app.xpath = function (xpath, singleNode) {
        var nodes;
        if (singleNode) {
            nodes = this.xml.evaluate(xpath, this.xml, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);
            return nodes.singleNodeValue;
        } else {
            nodes = this.xml.evaluate(xpath, this.xml, null, XPathResult.ANY_TYPE, null);
            return nodes;
        }
    };

    //
    app.saveXML = function () {
        var xml,
            s = new XMLSerializer();
        xml = s.serializeToString(this.xml);
        $.ajax({
            method: 'post',
            url: '/',
            data: {
                sitexml : xml
            }

        });
    };

    //
    app.blink = function (el) {
        el.removeClass('blink').addClass('blink');
        window.setTimeout(function () {
            el.removeClass('blink');
        }, 250);
    };

    /* * */
    app.init();
});