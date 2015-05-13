/**
 * SiteXML XML edit script
 *
 * requires jQuery
 *
 * @author Michael Zelensky 2015
 */

$(function(){
    var app = {
        els : {}
    };

    //
    app.init = function () {
        var container = $('<div id="siteXML-editXML-container" class="yui3-cssreset collapsed">' +
            '<div class="panel-button"></div>' +
            '<div class="tree"></div>' +
            '<div class="properties">' +
            '<table>' +
            '<tr class="properties-id"><td>Id</td><td><input type="text"></td></tr>' +
            '<tr class="properties-default"><td>Default</td><td><input type="checkbox"></td></tr>' +
            '<tr class="properties-startPage"><td>Start page</td><td><input type="checkbox"></td></tr>' +
            '<tr class="properties-show"><td>Show</td><td><input type="checkbox"></td></tr>' +
            '<tr class="properties-name"><td>Name</td><td><input type="text"></td></tr>' +
            '<tr class="properties-alias"><td>Alias</td><td><input type="text"></td></tr>' +
            '<tr class="properties-theme"><td>Theme</td><td><input type="text"></td></tr>' +
            '<tr class="properties-dir"><td>Dir</td><td><input type="text"></td></tr>' +
            '<tr class="properties-file"><td>File</td><td><input type="text"></td></tr>' +
            '<tr class="properties-type"><td>Type</td><td><input type="text"></td></tr>' +
            '<tr class="properties-content"><td>Content</td><td><input type="text"></td></tr>' +
            '<tr class="properties-nodeContent"><td>Node content</td><td><input type="text"></td></tr>' +
            '</table>' +
            '</div></div>')
            .css({
                zIndex : this.getMaxZIndex() + 1
            });
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
        this.els.propertiesShow = this.find('.properties-show');
        this.els.propertiesName = this.find('.properties-name');
        this.els.propertiesAlias = this.find('.properties-alias');
        this.els.propertiesTheme = this.find('.properties-theme');
        this.els.propertiesDir = this.find('.properties-dir');
        this.els.propertiesFile = this.find('.properties-file');
        this.els.propertiesContent = this.find('.properties-content');
        this.els.propertiesType = this.find('.properties-type');
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
            var ul, newli, newNode, id, parser,
                el = $(e.target);
            if (el.is('li')) {
                if (el.is('.collapsible')) {
                    el.toggleClass('collapsed');
                }
                me.showProperties(el);
            } else if (el.is('.panel-button')) {
                me.els.container.toggleClass('collapsed');
            } else if (el.is('.button')) {
                ul = el.closest('li').children('ul');
                if (!ul.length) {
                    ul = $('<ul></ul>');
                    el.closest('li').append(ul);
                }
                if (el.is('.addPage')) {
                    id = me.getMaxId('page') + 1;
                    parser = new DOMParser();
                    newNode = parser.parseFromString('<page id="' + id + '" />', "application/xml");
                    newli = me.renderItem(newNode.childNodes[0]);
                    ul.append(newli);
                }
                return false;
            }
        });
    };

    //
    app.showProperties = function (el) {
        var id = el.data('id'),
            nodeName = el.data('nodename'),
            nodeNameLC = nodeName.toLowerCase(),
            node = this.getNode(nodeName, id);
        this.els.container.find('table').show();
        this.hideAllPropertyFields();
        this.setAllProperties(node);
        if (nodeNameLC === 'site') {
            this.els.propertiesName.show();
        } else if (nodeNameLC === 'page') {
            this.els.propertiesId.show();
            this.els.propertiesStartPage.show();
            this.els.propertiesShow.show();
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

        }
    };

    //
    app.setAllProperties = function (node) {
        this.els.propertiesName.find('input').val(node.getAttribute('name'));
        this.els.propertiesId.find('input').val(node.getAttribute('id'));
        this.els.propertiesAlias.find('input').val(node.getAttribute('alias'));
        this.els.propertiesDir.find('input').val(node.getAttribute('dir'));
        this.els.propertiesFile.find('input').val(node.getAttribute('file'));
        this.els.propertiesType.find('input').val(node.getAttribute('type'));
        this.els.propertiesContent.find('input').val(node.getAttribute('content'));
        this.els.propertiesTheme.find('input').val(node.getAttribute('theme'));
        this.els.propertiesNodeContent.find('input').val(node.textContent);
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
        this.els.propertiesId.hide();
        this.els.propertiesStartPage.hide();
        this.els.propertiesDefault.hide();
        this.els.propertiesType.hide();
        this.els.propertiesShow.hide();
        this.els.propertiesName.hide();
        this.els.propertiesAlias.hide();
        this.els.propertiesTheme.hide();
        this.els.propertiesDir.hide();
        this.els.propertiesFile.hide();
        this.els.propertiesContent.hide();
        this.els.propertiesNodeContent.hide();
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
            html = '',
            pb = ' <a href="#" class="addPage button">[+p]</a>',
            mb = ' <a href="#" class="addMeta button">[+m]</a>',
            cb = ' <a href="#" class="addContent button">[+c]</a>',
            tb = ' <a href="#" class="addTheme button">[+t]</a>';
        nameLC = child.nodeName.toLowerCase();
        if (['site', 'theme', 'page'].indexOf(nameLC) >= 0) {
            cl = 'collapsed collapsible';
        } else {
            cl = '';
        }
        if (nameLC === 'site') {
            buttons = pb + mb + tb;
        } else if (nameLC === 'page') {
            buttons = pb + mb + cb;
        } else if (nameLC === 'theme') {
            buttons = mb + cb;
        } else {
            buttons = '';
        }
        dataAttributes = 'data-id="' + child.id + '"'
            + 'data-nodename="' + child.nodeName + '"'
        name = child.getAttribute('name');
        name = (name) ? ' name="' + name + '"' : '';
        html += '<li class="' + cl + '" ' + dataAttributes + '>'
            + child.nodeName
            + name
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

    app.init();
});