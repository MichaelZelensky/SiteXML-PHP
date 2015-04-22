<?php
/**
 * SiteXML parser
 *
 * v 2.0
 *
 * (c) 2015 Michael Zelensky
 *
 * About SiteXML technology: www.sitexml.info
 *
 */

DEFINE('DEBUG', true);
DEFINE('siteXML', '.site.xml');
DEFINE('CONTENT_DIR', '.content/');
DEFINE('THEMES_DIR', '.themes/');
DEFINE('DEFAULT_THEME_HTML', '<!DOCTYPE html><html>
    <head><meta http-equiv="Content-Type" content="text/html; charset=utf8">
    <%META%>
    <%SCRIPT%>
    <title><%TITLE%></title>
    <style>navi ul {list-style:none; padding: 20px} #footer, #footer a {color: #666}</style>
    </head><body>
    <div id="header" style="font-size: 3em"><%SITENAME%></div><div id="navi" style="float:left; width:180px"><%NAVI%></div>
    <div id="main" style="padding:0 10px 20px 200px"><%CONTENT(main)%></div>
    <div id="footer">This is <a href="http://www.sitexml.info">SiteXML</a> default theme<br/>SiteXML:PHP v1.0
    <a href="/.site.xml">.site.xml</a></div></body></html>');

$siteXML = new siteXML();

echo $siteXML->page();

class SiteXML {

    var $pid;
    var $obj;
    var $pageObj;

    function siteXML() {
        $this->obj = $this->getObj();
        $this->pid = $this->getPid();
        $this->pageObj = $this->getPageObj($this->pid);
        $this->themeObj = $this->getTheme();
        //print_r($this->obj);
    }

    //
    function getObj () {
        if (!file_exists(siteXML)) die ('Fatal error: .site.xml does not exist');
        $obj = simplexml_load_file(siteXML, 'SimpleXMLElement', LIBXML_DTDLOAD);
        if (!$obj) {
            die ('Fatal error: .site.xml is not a well formed XML');
        } else {
            return $obj;
        }
    }

    //
    function getPid () {
        if (isset($_GET['id'])) {
            $pid = $_GET['id'];
        } else {
            $defaultPid = $this->getDefaultPid();
            if (!$defaultPid) {
                $defaultPid = $this->getFirstPagePid();
            }
            $pid = $defaultPid;
        }
        if (!$pid) {
            die('Fatal error: no pages in this site');
        } else {
            return $pid;
        }
    }

    //recursive
    function getDefaultPid ($pageObj = false) {
        if (!$pageObj) $pageObj = $this->obj;
        $defaultPid = false;
        foreach ($pageObj as $k => $v) {
            if (strtolower($k) == 'page') {
                $attr = $this->attributes($v);
                if (strtolower($attr['startpage']) == 'yes') {
                    $defaultPid = $attr['id'];
                    break;
                } else {
                    $defaultPid = $this->getDefaultPid($v);
                    if ($defaultPid) break;
                }
            }
        }
        return $defaultPid;
    }

    //
    function getFirstPagePid () {
        $pid = false;
        foreach ($this->obj as $k=>$v) {
            if (strtolower($k) == 'page') {
                $attr = $this->attributes($v);
                $pid = $attr['id'];
                break;
            }
        }
        return $pid;
    }

    //recursive
    function getPageObj ($pid, $pageObj = false) {
        if (!$pageObj) $pageObj = $this->obj;
        $page = false;
        foreach ($pageObj as $k => $v) {
            if (strtolower($k) == 'page') {
                $attr = $this->attributes($v);
                if ($attr['id'] == $pid) {
                    $page = $v;
                    break;
                } else {
                    $page = $this->getPageObj($pid, $v);
                    if ($page) break;
                }
            }
        }
        return $page;
    }

    /*
     * @param {Object} page object. If not given, $this->pageObj will be used
     * @returns {Object} theme by page object
     */
    function getTheme($pageObj = false) {
        if (!$pageObj) $pageObj = $this->pageObj;
        $attr = $this->attributes($pageObj);
        if (!empty($attr['theme'])) {
            $themeId = $attr['theme'];
        } else {
            $themeId = false;
        }
        if ($themeId) {
            $themeObj = $this->getThemeObj($themeId);
        } else {
            $themeObj = $this->getDefaultThemeObj();
        }
        return $themeObj;
    }

    //@returns {Object} theme by id or FALSE
    function getThemeObj($themeId) {
        $themeObj = false;
        foreach ($this->obj as $k => $v) {
            if (strtolower($k) == 'theme') {
                $attr = $this->attributes($v);
                if (strtolower($attr['id']) == $themeId) {
                    $themeObj = $v;
                    break;
                }
            }
        }
        return $themeObj;
    }

    //@returns {Object} default or first theme
    function getDefaultThemeObj() {
        $firstThemeObj = false;
        $themeObj = false;
        foreach ($this->obj as $k => $v) {
            if (strtolower($k) == 'theme') {
                if (!$firstThemeObj) $firstThemeObj = $v;
                $attr = $this->attributes($v);
                if (strtolower($attr['default']) == 'yes') {
                    $themeObj = $v;
                    break;
                }
            }
        }
        if ($themeObj) $themeObj = $firstThemeObj;
        return $themeObj;
    }

    /*
     * @param {Object} theme || If not given, DEFAULT_THEME_HTML will be returned
     * @returns {String} theme html
     */
    function getThemeHTML($themeObj = false) {
        if (!$themeObj) {
            $this->error('SiteXML error: template does not exist, default template HTML will be used');
            $themeHTML = DEFAULT_THEME_HTML;
        } else {
            $attr = $this->attributes($themeObj);
            $dir = (empty($attr['dir'])) ? '' : $attr['dir'];
            $path = THEMES_DIR;
            if (substr($path, -1) != '/') $path .= '/';
            if (!empty($dir)) $path .= $dir;
            if (substr($path, -1) != '/') $path .= '/';
            if (!empty($attr['file'])) {
                $path .= $attr['file'];
                if (file_exists($path)) {
                    $themeHTML = file_get_contents($path);
                } else {
                    $this->error('SiteXML error: template file does not exist, default template HTML will be used');
                    $themeHTML = DEFAULT_THEME_HTML;
                }
            } else {
                $this->error('SiteXML error: template file missing, default template HTML will be used');
                $themeHTML = DEFAULT_THEME_HTML;
            }
        }
        return $themeHTML;
    }

    //
    function getTitle() {
        $pageObj = $this->pageObj;
        $attr = $this->attributes($pageObj);
        return (isset($attr['title'])) ? $attr['title'] : '';
    }

    //
    function getSiteName () {
        $attr = $this->attributes($this->obj);
        if (isset($attr['name'])) {
            $siteName = $attr['name'];
        } else {
            $siteName = $_SERVER['HTTP_HOST'];
        }
        return $siteName;
    }

    //
    function getThemePath ($themeObj = false) {
        if (!$themeObj) $themeObj = $this->themeObj;
        $attr = $this->attributes($themeObj);
        if (isset($attr['dir'])) {
            $dir = $attr['dir'];
            if (substr($dir, -1) != '/') $dir .= '/';
        } else {
            $dir = '';
        }
        return $dir;
    }

    //
    function getMetaHTML ($pageObj = false) {
        if (!$pageObj) $pageObj = $this->pageObj;
        $metaHTML = '';
        foreach ($this->obj as $k => $v) {
            if (strtolower($k) == 'meta') {
                $metaHTML .= $this->singleMetaHTML($v);
            }
        }
        foreach ($pageObj as $k => $v) {
            if (strtolower($k) == 'meta') {
                $metaHTML .= $this->singleMetaHTML($v);
            }
        }
        return $metaHTML;
    }

    //
    function singleMetaHTML ($metaObj) {
        $attr = $this->attributes($metaObj);
        $metaHTML = '<meta';
        foreach ($attr as $k => $v) {
            $metaHTML .= " $k=\"$v\"";
        }
        $metaHTML .= ">";
        return $metaHTML;
    }

    //
    function replaceMacroCommands ($HTML) {
        $macroCommands = array(
            '<%THEME_PATH%>',
            '<%SITENAME%>',
            '<%TITLE%>',
            '<%META%>',
            '<%NAVI%>'
        );
        $replacement = array(
            $this->getThemePath(),
            $this->getSiteName(),
            $this->getTitle(),
            $this->getMetaHTML(),
            $this->getNavi()
        );
        $HTML = str_replace($macroCommands, $replacement, $HTML);
        return $HTML;
    }

    //
    function replaceThemeContent ($HTML) {
        return $this->replaceContent($HTML, 'theme');
    }

    //
    function replacePageContent ($HTML) {
        return $this->replaceContent($HTML, 'page');
    }

    //
    function replaceContent($HTML, $where) {
        if ($where == 'page') {
            $obj = $this->pageObj;
        } elseif ($where == 'theme') {
            $obj = $this->themeObj;
        } else {
            return;
        }
        foreach ($obj as $k => $v) {
            if (strtolower($k) == 'content') {
                $attr = $this->attributes($v);
                $name = $attr['name'];
                $search = "<%CONTENT($name)%>";
                if (strpos($HTML, $search) !== false) {
                    $file = CONTENT_DIR . $attr['file'];
                    if (file_exists($file)) {
                        $HTML = str_replace($search, file_get_contents($file), $HTML);
                    } else {
                        $this->error("Error: content file " . $attr['file'] . " does not exist");
                    }
                }
            }
        }

        return $HTML;
    }

    //
    function getNavi($obj = false, $level = 0) {
        $level ++;
        if (!$obj) $obj = $this->obj;
        $HTML = '';
        foreach($obj as $k => $v) {
            if (strtolower($k) == 'page') {
                $attr = $this->attributes($v);
                $HTML .= '<li><a href="?id=' . $attr['id'] . '">' . $attr['name'] . '</a>';
                $HTML .= $this->getNavi($v, $level);
                $HTML .= '</li>';
            }
        }
        if ($HTML <> '') $HTML = "<ul class=\"siteXML-navi level-$level\">$HTML</ul>";
        return $HTML;
    }

    //
    function page () {
        $pageHTML = $this->getThemeHTML($this->themeObj);
        $pageHTML = $this->replacePageContent($pageHTML);
        $pageHTML = $this->replaceThemeContent($pageHTML);
        $pageHTML = $this->replaceMacroCommands($pageHTML);
        return $pageHTML;
    }

    /*
     * @param {String} $error
     * */
    function error ($error) {
        if (DEBUG) {
            echo "$error\n";
        }
    }

    /*
     * @param {SimpleXML Object} $obj
     * */
    function attributes ($obj) {
        if (!$obj) return false;
        $attr = $obj->attributes();
        $newattr = array();
        foreach ($attr as $k => $v) {
            $newattr[strtolower($k)] = $v;
        }
        return $newattr;
    }
}