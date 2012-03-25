<?
/*

SiteXML:PHP v1.0

See information about SiteXML methodology: www.sitexml.info

(c) 2012 Michael Zelensky www.miha.in

Memos:
- parse config
- debug get_new_user_id - return always 1
- set access_level in constructor
- getSiteXML - always return full XML, need to parse it in accordance with the access level
- page alias (in getContent method)

*/

//file location and other settings
DEFINE('CONFIG', '.sitexml.cfg');
DEFINE('siteXML', '.site.xml');
DEFINE('USERS', '.users');
DEFINE('LOG', '.sitexml.log');
DEFINE('CONTENT_DIR', '.content/');
DEFINE('THEMES_DIR', '.themes/');
DEFINE('DEFAULT_CONTENT_FILE', '.content/.default.html');
DEFINE('DUMP_DEBUG_HTML', true); //debug info
DEFINE('TEXT', "content-type: text/html; charset=UTF-8");
DEFINE('XML', "content-type: application/xml; charset=UTF-8");
DEFINE('JAVASCRIPT0', "");
DEFINE('JAVASCRIPT1', '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script><script src="http://www.sitexml.info/libs/jsclient001.js" type="text/javascript"></script>');
DEFINE('JAVASCRIPT3', "");
DEFINE('JAVASCRIPT7', "");
DEFINE('CSS0', "");
DEFINE('CSS1', "");
DEFINE('CSS3', "");
DEFINE('CSS7', "");

//always start session
session_start();

//init
$siteXML = new siteXML();

# ****** ACTIONS @ **********

// @ VK login
if ($_GET['code']) {
  header (TEXT);
  $OUTPUT = $siteXML->VK($_GET['code']);

// @ logout
} elseif (isset($_GET['logout'])) {
  header (TEXT);
  unset($_SESSION['user_id']);
  unset($_SESSION['user_name']);
  unset($_SESSION['user_email']);
  $OUTPUT = json_encode(array('result'=>'logged out'));
  
// @ return site XML
} elseif (isset($_GET['sitexml'])) {
  header (XML);
  $OUTPUT = $siteXML->getSiteXML();

// @ get page 
} elseif ($_GET['id']) {
  header (TEXT);
  $id = (int) $_GET['id'];
  $OUTPUT = $siteXML->getPage($id);

// @ get content by page id and content name
} elseif ($_GET['pid'] && $_GET['cname']) {
  header (TEXT);
  $pid = (int) $_GET['pid'];
  $cname = $_GET['cname'];
  $OUTPUT = $siteXML->getContent($pid, $cname);

// @ save XML
} elseif (isset($_GET['xml']) && $_POST['data']) {
  header (TEXT);
  $OUTPUT = $siteXML->updateXML($_POST['data']);

// @ save content
} elseif (isset($_GET['cid']) && $_POST['content']) {
  header (TEXT);
  $cid = (int) $_GET['cid'];
  $OUTPUT = $siteXML->updateContent($cid, $content);
  
// @ default action, get site root page
} else {
  header (TEXT);
  $OUTPUT = $siteXML->getPage(0); 
}


# ************* OUTPUT ******************
echo $OUTPUT;

# End of script


# *********** SiteXML CLASS ***************

class SiteXML {

  var $config;
  var $DOM;
  var $ERRORS = array(
    'missing_content' =>'Error: content file is missing',
    'missing_content_tag' => 'Error: content element is missing',
    'missing_content_id' => 'Error: id parameter in content element is missing',
    'missing_theme'   =>'Error: theme is missing',
    'no_pages_in_sitexml' => 'Error: there are no pages in site.xml',
    'opening_xml_file'    => "Error opening xml file",
    'VK_get_user' => 'Error while getting VK user profile',
    'user_exists' => "User exists",
    'could_not_insert_user' => "Could not insert user",
    'wrong_content_id' => "Wrong content id",
    'error_writing_content_file' => "Error writing content file",
    'not_authorized' => 'Not authorized'
  );
  var $log; //process logging; kept until script terminate
  var $system_log; //constantly saved log on disk
  var $access_level = 1; //{0, 1, 3, 7} binary 0: read without ajax browsing; 001: read with ajax-browsing; 011: edit content; 111: edit xml
  var $default_theme_html;
  
  /* CONSTRUCTOR */
  
  function siteXML() {
    $this->log(__METHOD__);
    $this->system_log = array();
    $this->config = $this->getConfig();
    $this->DOM = simplexml_load_file(siteXML);
    if(!$this->DOM) {
      $this->stop($this->ERRORS['opening_xml_file'] . siteXML);
    }
    //default theme html
    $JS[0] = JAVASCRIPT0;
    $JS[1] = JAVASCRIPT1;
    $JS[3] = JAVASCRIPT3;
    $JS[7] = JAVASCRIPT7;
    $CSS[0] = CSS0;
    $CSS[1] = CSS1;
    $CSS[3] = CSS3;
    $CSS[7] = CSS7;
    $sitename = $this->getSiteName();
    $this->default_theme_html = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf8"><%META%><title>'. $sitename .'</title>'. $JS[$this->access_level] . $CSS[$this->access_level] .'<style>navi ul {list-style:none; padding: 20px} #footer, #footer a {color: #666}</style></head><body><div id="header" style="font-size: 3em">'. $sitename .'</div><div id="navi" style="float:left; width:180px"><%NAVI%></div><div id="main" style="padding:0 10px 20px 200px"><%CONTENT(main)%></div><div id="footer">This is <a href="http://www.sitexml.info">SiteXML</a> default theme<br/>SiteXML:PHP v1.0 <a href="/.site.xml">.site.xml</a></div></body></html>';
  }
  
  /* CONFIG */
  
  #parse config file and return CONFIG array;
  function getConfig() {
    $this->log(__METHOD__);
    /*
     examples:
     1. key: val - single word
     2. key: 'a string with spaces'
    */
    $config = array();
    //default values
    $config['missing_content']  = 'error';
    $config['missing_content_tag']  = 'error';
    $config['missing_theme']    = 'default';
    $config['missing_page_title']    = 'site';
    $config['no_pages_in_sitexml']    = 'welcome';
    $config['vk_app_id'] = '2854307';
    $config['vk_secret'] = 'XpSJ4YkLJ4iwffwmFT41';
    //read config file
    if (file_exists(CONFIG)) {
      $farsh = file_get_contents(CONFIG);
    } else {
      $this->stop('Error opening config file '. CONFIG);
    }
    /* 
      DO SOME PARSING HERE 
      ... but later... 
    */
    return $config;
  }

  /* GET */
  
  #returns sitexml depending on user grants
  function getSiteXML() {
    $this->log(__METHOD__);
    switch ($this->access_level) {
      case 7: 
        return file_get_contents(siteXML);
        break;
      default:
        return ''; //fig vam
        break;
    }
  }
  
  //return sitename
  function getSiteName() {
    $sitename = $this->getNodeAttr('//site', 'name');
    if (!$sitename) $sitename = $_SERVER['SERVER_NAME'];
    return $sitename;
  }
  
  //get content by page id and content name
  function getContent($pid, $cname) {
    $this->log(__METHOD__);
    $R = array();
    $page = $this->getPageNode($pid);
    $title = $this->getPageTitle($page);
    $address = "?id=". $attr['id'];
    $pid = $attr['id'];
    //getting content
    foreach($page->children() as $child) {
      if ($child->getName() == 'content') {
        $attr = $child->attributes();
        if ($attr['name'] == $cname) {
          $content_id = (string)$attr['id'];
          $content_file = CONTENT_DIR.(string)$child;
          $content = $this->getContentFromFile($content_file);
          break;
        }
      }
    }
    //output
    if ($content_id) {
      $R['content_id'] = $content_id;
      $R['content'] = $content;
    } else {
      $R['content_id'] = '';
      $R['content'] = $this->ERRORS['missing_content_id'];      
    }
    $R['page_title'] = $title;
    $R['page_address'] = $address;
    $R['pid'] = (int)$pid;
    return json_encode($R);
  }
  
  //get content logic
  function getContentFromFile($file){
    if (file_exists(DEFAULT_CONTENT_FILE)) {
      $default_content = file_get_contents(DEFAULT_CONTENT_FILE);
    } else {
      $default_content = $this->ERRORS['missing_content'];
    }
    if (file_exists($file)) {
      return file_get_contents($file);
    } else {
      switch ($this->config['missing_content']) {
        case 'error':
          return $this->ERRORS['missing_content'];
          break;
        default:
          return $default_content;
          break;
      }
    }
  }
  
  //get titile logic
  function getPageTitle($node) {
    $attr = $node->attributes();
    $title[1] = (string) $attr['title'];
    $title[2] = (string) $attr['name'];
    $title[3] = $this->getSiteName();
    $title[4] = '';
    $title[5] = $this->config['missing_page_title'];
    if ($title[1]) {
      return $title[1];
    } else {
      switch ($this->config['missing_page_title']) {
        case 'page_name':
          return $title[2];
          break;
        case 'site':
          return $title[3];
          break;
        case 'none':
          return $title[4];
          break;
        default:
          return $title[5];
          break;
      }
    }
  }

  //get page logic
  function getPageNode($pid) {
    $pid = (int) $pid;
    $xpath[1] = "//page[@id='$pid']";
    $xpath[2] = "//page[@startpage='yes']";
    $xpath[3] = "//page";
    if ($this->getNodeAttr($xpath[1], "id")) {
      $page = $this->DOM->xpath($xpath[1]);
    } elseif ($this->getNodeAttr($xpath[2], "id")) {
      $page = $this->DOM->xpath($xpath[2]);
    } elseif ($this->getNodeAttr($xpath[3], "id")) {
      $page = $this->DOM->xpath($xpath[3]);
    } else {
      $page = false;
    }
    return $page[0];
  }
  
  //theme logic
  // @par node - page node; @return theme node or false
  function getPageThemeNode($node) {
    if (!$node) {
      return false;
    }
    $attr = $node->attributes();
    $theme_id = (string) $attr['theme'];
    $xpath[1] = "//theme[id='$theme_id']";
    $xpath[2] = "//theme[default='yes']";
    $xpath[3] = "//theme";
    if ($theme_id && $this->getNodeAttr($xpath[1], 'file')) {
      $theme = $this->DOM->xpath($xpath[1]);
    } elseif ($this->getNodeAttr($xpath[2], 'file')) {
      $theme = $this->DOM->xpath($xpath[2]);
    } elseif ($this->getNodeAttr($xpath[3], 'file')) {
      $theme = $this->DOM->xpath($xpath[3]);
    } else {
      return false;
    }
    return $theme[0];
  }
  
  // get theme html
  // @par $theme - theme node; @ return HTML
  function getPageTheme($theme) {
    if (!$theme) {
      return $this->default_theme_html;
    }
    $attr = $theme->attributes();
    $theme_file = THEMES_DIR . '/' . $attr['file'];
    if (file_exists($theme_file)) {
      return file_get_contents($theme_file);
    } else {
      $this->system_log("Theme file does not exists, page id: ". $attr['id']);
      return $this->default_theme_html;
    }
  }
  
  //replace content in HTML logic() 
  //@par html, @par pagenode - page node, @par themenode - theme node if exists, @result html
  function replaceContent($html, $pagenode, $themenode=false){
    //search for content in html
    $macrocommands = array(
      'CONTENT',
      'C'
    );
    $cnames = array();
    foreach ($macrocommands as $mc) {
      $tmp = $html;
      while ($tmp) {
        $tmp = stristr($tmp, "<%". $mc);
        if ($tmp) {
          $startpos = strpos($tmp, '(');
          $endpos = strpos($tmp, ')%>');
          if ($endpos) {
            $cnames[] = substr($tmp, $startpos+1, $endpos-$startpos-1);
          }
          $tmp = substr($tmp, $endpos);
        }
      }
    }
    $cnames = array_unique($cnames);
    $search = array();
    $replace = array();
    $attr = $pagenode->attributes();
    $pid = $attr['id'];
    foreach ($cnames as $cname) {
      foreach($macrocommands as $mc) {
        $search[] = "<%$mc($cname)%>";
        $c = json_decode($this->getContent($pid, $cname), true);
        $replace[] = '<div class="sx-ajaxable" contenteditable="false" cid="'. $c['id'] .'" cname="'. $cname .'">'. $c['content'] .'</div>';
      }
    }
    $R = str_replace($search, $replace, $html);
    //theme content
    if (!$themenode) {
      return $R;
    } else {
      //go on
    }
    $macrocommands = array(
      'TCONTENT',
      'TC'
    );
    $cnames = array();
    foreach ($macrocommands as $mc) {
      $tmp = $R;
      while ($tmp) {
        $tmp = stristr($tmp, "<%". $mc);
        if ($tmp) {
          $startpos = strpos($tmp, '(');
          $endpos = strpos($tmp, ')%>');
          if ($endpos) {
            $cnames[] = substr($tmp, $startpos+1, $endpos-$startpos-1);
          }
          $tmp = substr($tmp, $endpos);
        }
      }
    }
    $cnames = array_unique($cnames);
    $search = array();
    $replace = array();
    foreach ($cnames as $cname) {
      foreach($themenode->children() as $c) {
        $attr = $c->attributes();
        if ($c->getName() == 'content' && $attr['name'] == $cname) {
          $content_file = CONTENT_DIR . $c;
          if (file_exists($content_file)) {
            $content = file_get_contents($content_file);
          } else {
            $content = $this->ERRORS['missing_content'];
          }
          foreach($macrocommands as $mc) {
            $search[] = "<%$mc($cname)%>";
            $replace[] = '<div contenteditable="false" cid="'. $attr['id'] .'" cname="'. $cname .'">'. $content .'</div>';
          }
        }
      }
    }
    $R = str_replace($search, $replace, $R);
    return $R;
  }
  
  //replace navi
  function replaceNavi($html) {
    $tmp = $html;
    $search = array();
    $replace = array();
    while($tmp) {
      $tmp = stristr($tmp, '<%NAVI');
      if ($tmp) {
        $startpos = strlen('<%NAVI');
        $endpos = strpos($tmp, '%>');
      } else {
        $endpos = false;
      }
      if ($endpos) {
        $inside = substr($tmp, $startpos+1, $endpos-$startpos);
        $pars = str_replace(array('(', ')'), '', $inside);
        if ($pars) {
          $PARS = split($pars, ',');
        } else {
          $PARS = array();
        }
        $s = '<%NAVI'. $inside .'%>';
        $search[] = $s;
        $replace[] = $this->getNavi($PARS[0], $PARS[1]);
        $tmp = substr($tmp, strlen($s));
      } else {
        $tmp = false;
      }
    }
    return str_replace($search, $replace, $html);
  }
  
  //return navi
  //@par start - root page id; @par level - navi level
  function getNavi($start, $level) {
    $start = (int) $start;
    $level = (int) $level;
    if (!$level) $level = 2;
    if (!$start) {
      $start = 0;
      $xpath = "//site";
    } else {
      $xpath = "//page[@id='$start']";
    }
    $page = $this->DOM->xpath($xpath);
    $cur_level = 1;
    $navi = '<navi>'. $this->getNaviLevel($page, $level, $cur_level) .'</navi>';
    return $navi;
  }
  
  function getNaviLevel($node, $level, $cur_level) {
    $node = $node[0];
    if ($level <= $cur_level) {
      return false;
    } else {
      //go on
    }
    $html = '';
    if ($node->children()) {
      $html .= '<ul>';
      foreach ($node->children() as $child) {
        if ($child->getName() == 'page') {
          $attr = $child->attributes();
          if ($attr['navi'] != 'no') {
            $href = '/?id='. $attr['id'];
            $theme_id = $attr['theme'];
            $html .= '<li><a href="'. $href .'" pid="'. $attr['id'] .'" theme_id="" contenteditable="false">' . $attr['name'] . '</a>';
            $html .= $this->getNaviLevel($child, $level, $cur_level+1);
          }
        }
      }
      $html .= '</ul>';
    }
    return $html;
  }
  
  function replaceMeta($html, $page) {
    $meta = array();
    $metaH = '';
    //site meta
    $sitemeta = $this->DOM->xpath("//site/meta");
    foreach ($sitemeta as $child) {
      if ($child->getName() == 'meta') {
        $attr = $child->attributes();
        $name = (string) $attr['name'];
        $meta[$name] = (string) $child;
      }
    }
    //page meta
    foreach ($page->children() as $child) {
      if ($child->getName() == 'meta') {
        $attr = $child->attributes();
        $name = (string) $attr['name'];
        $meta[$name] = (string) $child;
      }
    }
    $meta['generator'] = 'SiteXML:PHP v1.0';
    //html
    foreach ($meta as $k=>$v) {
      $metaH .= '<meta name="'.$k.'" content="'. $v .'" />';
    }
    //replacing
    $html = str_replace("<%META%>", $metaH, $html);
    return $html;
  }
  
  function replaceTitle($html, $page) {
    $attr = $page->attributes();
    $title = $attr['title'];
    if (!$title) {
      $title = $attr['name'];
    }
    $html = str_replace("<%TITLE%>", $title, $html);
    return $html;
  }
  
  function replaceTPATH($html, $theme) {
    $attr = $theme->attributes();
    $path = '/.themes/'. $attr['path'];
    $html = str_replace("<%TPATH%>", $path, $html);
    return $html;
  }
  
  //get the whole page output
  function getPage($id) {
    $this->log(__METHOD__ . ' ' . $id);
    $page = $this->getPageNode($id);
    $theme = $this->getPageThemeNode($page);
    $page_html = $this->getPageTheme($theme);
    $page_html = $this->replaceTPATH($page_html, $theme);
    $page_html = $this->replaceNavi($page_html);
    $page_html = $this->replaceTitle($page_html, $page);
    $page_html = $this->replaceContent($page_html, $page, $theme);
    $page_html = $this->replaceMeta($page_html, $page);
    return $page_html;
  }
  
  /* UPDATE */
  
  //update site.xml
  function updateXML($xml) {
    if ($this->access_level == 7) {
      $dom = new DOMDocument();
      $dom->loadXML($xml);
      if ($dom->save(siteXML)) {
        $this->system_log(siteXML ." was updated by ". $_SESSION['user_name'] ." [id:". $_SESSION['user_id'] ."]");
        $R = array('result' => "XML saved");
      } else {
        $R = array('error' => 'could not save XML');
      }
    } else {
      $R = array('error' => 'not authorized');
    }
    return json_encode($R);
  }
  
  //update content
  function updateContent($cid, $content) {
    $this->log(__METHOD__ . "content id: $cid");
    if ($this->access_level < 3) {
      $R['result'] = $this->ERRORS['not_authorized'];
    } else {
      $xpath = "//content[@id='$cid']";
      if (!$this->getNodeAttr($xpath, 'id')) {
        $this->stop($this->ERRORS['wrong_content_id']);
      }
      $node = $this->DOM->xpath($xpath);
      $content_file = CONTENT_DIR.$node;
      if (file_put_contents($content_file, $new_content)) {
        $R['result'] = "Content saved";
      } else {
        $R['result'] = $this->ERRORS['error_writing_content_file'];
      }
    }
    return json_encode($R);
  }
  
  /* XML */
  
  # @par $node SimpleXMLObject
  # @par $attr string, attribute name
  # returns string
  function getNodeAttr($xpath, $attr) {
    $node = $this->DOM->xpath($xpath);
    if ($node[0]) {
      $r = $node[0]->attributes();
      return (string) $r[$attr];
    } else {
      return false;
    }
  }
  
  /* USER */
  
  # get user by id
  # @return array in case of success, otherwise false; see users table in /.users file
  function get_user($id) {
    /* in accordance with .users:
      user = array (
        0 => id,
        1 => vk_id,
        2 => fb_id,
        3 => openid,
        4 => name,
        5 => email,
        6 => 
      )
    */
    if (!$id) return false;
    if (!file_exists(USERS)) return false;
    $users = split(file_get_contents(USERS),"\n");
    foreach($users as $user) {
      if (substr($user, 0, 1) != "#") {
        $u = split($user, ',');
        if ($u[1] == $id) {
          $u['id'] = $u[0];
          $u['vk_id'] = $u[1];
          $u['fb_id'] = $u[2];
          $u['openid'] = $u[3];
          $u['name'] = $u[4];
          $u['email'] = $u[5];
          return $u;
        }
      }
    }
    return false;
  }
  
  // insert user
  // @return user array
  function insert_user($u) {
    $this->log(__METHOD__);
    //exists? => die
    if ($this->get_user($u['id'])) {
      $this->stop($this->ERRORS['user_exists'] . " " . join($u, ','));
    }
    $u['id'] = $this->get_new_user_id();
    //all right
    $u_verified = array(
      $u['id'],
      $u['vk_id'],
      $u['fb_id'],
      $u['openid'],
      $u['name'],
      $u['email']
    );
    if (file_put_contents(USERS, "\n". join($u_verified, ','), FILE_APPEND)) {
      $this->system_log("User ". $u['id'] ." added");
    } else {
      $this->stop($this->ERRORS['could_not_insert_user'] . join($u_verified, ','));
    }
    return $u_verified;
  }

  # get new id for new user
  //*** buggy: always return 1
  function get_new_user_id() {
    $max = 1;
    if (!file_exists(USERS)) return $max;
    $users = split(file_get_contents(USERS),"\n");
    foreach($users as $user) {
      if (substr($user, 0, 1) != "#") {
        $u = split($user, ',');
        if (((int)$u[0]) > $max) {
          $max = $u[0] + 1;
        }
      }
    }
    return $max;
  }

  //VK login
  function VK($code) {
    $this->log(__METHOD__);
    $query = "https://oauth.vk.com/access_token?client_id=". $this->config['vk_app_id'] ."&client_secret=". $this->config['vk_secret'] ."&code=$code";
    $res = file_get_contents($query);
    $res = json_decode($res, true);
    //check if user exists
    if (!file_exists(USERS)) return false;
    $users = split("\n",file_get_contents(USERS));
    foreach($users as $user) {
      if (substr($user, 0, 1) != "#") {
        $u = split(',', $user);
        if ($u[1] == $id) {
          $u['id']      = $u[0];
          $u['vk_id']   = $u[1];
          $u['fb_id']   = $u[2];
          $u['openid']  = $u[3];
          $u['name']    = $u[4];
          $u['email']   = $u[5];
          break;
        }
      }
    }
    //user does not exist: retrieve user profile from VK and save
    if (!$u) {
      //get user data from VK
      $access_token = $res['access_token'];
      if ($access_token) {
        $query = "https://api.vk.com/method/getProfiles?uid=". $res['user_id'] ."&access_token=$access_token";
        $res = file_get_contents($query);
        $res = json_decode($res, true);
        $res = $res['response'][0];
        //save
        $u = array(
          'vk_id' => $res['uid'], 
          'name' => $res['first_name'] . ' ' . $res['last_name']
        );
        $user = $this->insert_user($u);
      } else {
        $this->stop($this->ERRORS['VK_get_user']);
      }
    //user exists
    } else {
      //session is already started
      $_SESSION["user_id"] = $user['id'];
      $_SESSION["user_email"] = $user['email'];
      $_SESSION["user_name"] = $user['name'];
      $this->system_log("User ". $user['id'] ."logged in");
      $R = '<script>opener.unlock_loggedin('. json_encode(array($_SESSION['user_id'], $_SESSION['user_name'])) .'); window.setTimeout(close, 1000); </script>';
      return $R;
    }
  }
  
  /* SYSTEM */
  
  function log($msg) {
    $this->log[] = $msg;
  }
  
  function system_log($msg) {
    $this->system_log[] = date('Y-m-d H:i:s') . ' ' .$msg;
  }
  
  function write_system_log() {
    $raw = '';
    foreach($this->system_log as $string) {
      $raw .= $string . "\r\n";
    }
    file_put_contents(LOG, $raw, FILE_APPEND);
  }
  
  function stop($msg) {
    die($msg);
  }
}
?>