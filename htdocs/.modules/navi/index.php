<?php

function getNavi_partner($siteXML1, $obj = false, $maxlevel = 0, $level = 0) {
    $level ++;
    if (!$obj) $obj = $siteXML1->obj;
    $HTML = '';
    $objAttr = $siteXML1->attributes($obj);
    $objId = '' . $objAttr['id'];
    if ($maxlevel <> 0 && $maxlevel >= $level) {
        foreach($obj as $k => $v) {
            if (strtolower($k) == 'page') {
                $attr = $siteXML1->attributes($v);
                if ((isset($attr['show']) && ($attr['show'] == 'no'))) continue;
                $href = (isset($attr['alias'])) ? '/' . $attr['alias'] : '/?id=' . $attr['id'];
                if ($level <> 1) {
                    $HTML .= '<li><a href="' . $href . '" pid="' . $attr['id'] . '">' . $attr['name'] . '</a>';
                } elseif ($level == 1) {
                    $HTML .= '<li class="first" id="li_' . $attr['id'] . '"><a href="' . $href . '" pid="' . $attr['id'] . '">' . $attr['name'] . '</a>';
                }
                $HTML .= getNavi_partner($siteXML1, $v, $maxlevel, $level);
                $HTML .= '</li>';
            }
        }
        if ($HTML <> '') {
            if ($level == 1) {
                $HTML = "<ul id=\"menu\">$HTML</ul>";
            }
            if ($level == 2) {
                $HTML = "<ul id=\"ul_$objId\">$HTML</ul>";
            }
        }
    }

    return $HTML;
}

$siteXML1 = new siteXML();

echo getNavi_partner($siteXML1, $siteXML1->getPageObj(2), 2);