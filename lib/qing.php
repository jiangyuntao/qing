<?php
error_reporting(E_ALL);

// 项目开始时间
define('BEGIN_TIMESTAMP', array_sum(explode(' ', microtime())));

// 目录分隔符
define('DS', DIRECTORY_SEPARATOR);

// 文件扩展名
define('EXT', '.php');

// lib 库目录
define('LIB', realpath('./'));

// 项目根目录
defined('ROOT') || define('ROOT', realpath('../') . DS);

// app 应用目录
defined('APP') || define('APP', realpath('../app') . DS);

// tmp 临时目录
defined('TMP') || define('TMP', ROOT . 'tmp' . DS);

// 移除全局变量
ini_get('register_globals')
    && unregister_globals('_GET', '_POST', '_REQUEST', '_SERVER', '_ENV', '_FILES', '_COOKIE', '_SESSION');

// 移除 magic_quotes
function_exists('set_magic_quotes_runtime')
    && get_magic_quotes_runtime()
    && set_magic_quotes_runtime(false);

if (get_magic_quotes_gpc()) {
    remove_magic_quotes($_GET);
    remove_magic_quotes($_POST);
    remove_magic_quotes($_COOKIE);
    ini_set('magic_quotes_gpc', 0);
}

/**
 * 引导程序
 *
 * @param string $app 应用所在目录
 * @param string $subdir 控制器子目录
 * @return void
 */
function boot($app = '../app', $subdir = '') {
    import('lib.router');

    $router = registry('router', new Router());

    $request = isset($_SERVER['PATH_INFO'])
        ? $_SERVER['PATH_INFO']
        : (isset($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : '/');

    $handler = $router->match($request);

    dispatch($handler);
    dump(elapsed());
}

/**
 * 调度请求
 *
 * @param mixed $handler 
 * @return void
 */
function dispatch($handler = null) {
}

/**
 * 导入文件
 *
 * 以项目目录为根目录，如果最后一项是目录，则导入该目录下 init.php
 * 例如：import('app.controller.news');       // 导入 app/controller/news.php
 *       import('lib.vendor.twig');          // 导入 lib/vendor/twig/init.php
 *       import(QING_PATH . DS . 'util.php'); // 类似于导入 /qing_project/qing/util.php
 * 
 * @param mixed $alias
 * @access public
 * @return void
 */
function import($alias = null) {
    static $imported = array();

    if (is_array($alias)) {
        foreach ($alias as $v) {
            import($v);
        }
    }

    if (!$alias) {
        return false;
    }

    if (file_exists($alias)) {
        $filename = $alias;
    } else {
        $path = ROOT . str_replace('.', DS, $alias);

        if (is_dir($path)) {
            $filename = $path . DS . 'init' . EXT;
        } else {
            $filename = $path . EXT;
        }

        if (!file_exists($filename)) {
            return false;
        }
    }

    if (!in_array($filename, $imported)) {
        $imported[$filename] = $filename;
        return require $filename;
    }

    return true;
}

/**
 * 根据路由创建URL
 *
 * @param mixed $alias
 * @return string
 */
function url($alias = null, $params = array()) {
    $router = registry('router');
    return $router->generateUrl($alias, $params);
}

function elapsed() {
    $now = array_sum(explode(' ', microtime()));
    return sprintf("%.6f", $now - BEGIN_TIMESTAMP);
}

/**
 * 移除全局变量
 *
 * @return void
 */
function unregister_globals() {
    $args = func_get_args();
    foreach ($args as $arg) {
        foreach ($$arg as $k => $v) {
            if (array_key_exists($k, $GLOBALS)) {
                unset($GLOBALS[$k]);
            }
        }
    }
}

/**
 * 移除 Magic Quotes
 *
 * @param array $array
 * @return array
 */
function remove_magic_quotes($array = array()) {
    foreach ($array as $k => $v) {
        $array[$k] = is_array($v) ? remove_magic_quotes($v) : stripslashes($v);
    }
    return $array;
}

/**
 * 获取客户端IP
 *
 * @return string
 */
function get_client_ip() {
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * 加强版 var_dump()
 *
 * @param mixed $var
 * @return void
 */
function dump($var = null) {
    ob_start();
    var_dump($var);
    $html = ob_get_clean();

    echo '<pre>', h($html), '</pre>';
}

/**
 * 迷你版 htmlspecialchars()
 *
 * @param string $string
 * @param mixed $flags
 * @param string $charset
 * @param mixed $double_encode
 * @return string
 */
function h($string = '', $flags = ENT_COMPAT, $charset = 'ISO-8859-1', $double_encode = true) {
    return htmlspecialchars($string, $flags, $charset, $double_encode);
}

/**
 * 用来存储信息的注册表
 *
 * @param string $key 键名
 * @param mixed $value 值
 * @param mixed $overwrite 第二次及以后传入值是否覆盖
 * @return mixed
 */
function registry($key = '', $value = null, $overwrite = false) {
    static $registered = array();

    if (!$key) {
        return false;
    }

    if (is_null($value)) {
        if (isset($registered[$key])) {
            return $registered[$key];
        } else {
            return false;
        }
    } else {
        if (isset($registered[$key])) {
            if ($overwrite) {
                return $registered[$key] = $value;
            } else {
                return false;
            }
        } else {
            return $registered[$key] = $value;
        }
    }
}

/**
 * 将数组转化为字符串
 *
 * @param array $array 
 * @return string
 */
function array_to_string($array = array()) {
    $content = "<?php\n";
    $content .= "return array(\n";
    foreach ($array as $key => $value) {
        $content .= "    '" . $key . "' => '" . $value . "',\n";
    }
    $content .= ");";
    return $content;
}

/**
 * 压缩PHP代码
 *
 * @param string $src PHP代码或者文件名
 * @return string
 */
function compress_php_src($src = '') {
    // Whitespaces left and right from this signs can be ignored
    static $IW = array(
        T_CONCAT_EQUAL,             // .=
        T_DOUBLE_ARROW,             // =>
        T_BOOLEAN_AND,              // &&
        T_BOOLEAN_OR,               // ||
        T_IS_EQUAL,                 // ==
        T_IS_NOT_EQUAL,             // != or <>
        T_IS_SMALLER_OR_EQUAL,      // <=
        T_IS_GREATER_OR_EQUAL,      // >=
        T_INC,                      // ++
        T_DEC,                      // --
        T_PLUS_EQUAL,               // +=
        T_MINUS_EQUAL,              // -=
        T_MUL_EQUAL,                // *=
        T_DIV_EQUAL,                // /=
        T_IS_IDENTICAL,             // ===
        T_IS_NOT_IDENTICAL,         // !==
        T_DOUBLE_COLON,             // ::
        T_PAAMAYIM_NEKUDOTAYIM,     // ::
        T_OBJECT_OPERATOR,          // ->
        T_DOLLAR_OPEN_CURLY_BRACES, // ${
        T_AND_EQUAL,                // &=
        T_MOD_EQUAL,                // %=
        T_XOR_EQUAL,                // ^=
        T_OR_EQUAL,                 // |=
        T_SL,                       // <<
        T_SR,                       // >>
        T_SL_EQUAL,                 // <<=
        T_SR_EQUAL,                 // >>=
    );
    if (is_file($src) && !$src = file_get_contents($src)) {
        return false;
    }
    $tokens = token_get_all($src);
    
    $new = "";
    $c = sizeof($tokens);
    $iw = false; // ignore whitespace
    $ih = false; // in HEREDOC
    $ls = "";    // last sign
    $ot = null;  // open tag
    for ($i = 0; $i < $c; $i++) {
        $token = $tokens[$i];
        if (is_array($token)) {
            list($tn, $ts) = $token; // tokens: number, string, line
            $tname = token_name($tn);
            if ($tn == T_INLINE_HTML) {
                $new .= $ts;
                $iw = false;
            } else {
                if($tn == T_OPEN_TAG) {
                    if (strpos($ts, " ") || strpos($ts, "\n") || strpos($ts, "\t") || strpos($ts, "\r")) {
                        $ts = rtrim($ts);
                    }
                    $ts .= " ";
                    $new .= $ts;
                    $ot = T_OPEN_TAG;
                    $iw = true;
                } elseif ($tn == T_OPEN_TAG_WITH_ECHO) {
                    $new .= $ts;
                    $ot = T_OPEN_TAG_WITH_ECHO;
                    $iw = true;
                } elseif ($tn == T_CLOSE_TAG) {
                    if ($ot == T_OPEN_TAG_WITH_ECHO) {
                        $new = rtrim($new, "; ");
                    } else {
                        $ts = " ".$ts;
                    }
                    $new .= $ts;
                    $ot = null;
                    $iw = false;
                } elseif (in_array($tn, $IW)) {
                    $new .= $ts;
                    $iw = true;
                } elseif ($tn == T_CONSTANT_ENCAPSED_STRING || $tn == T_ENCAPSED_AND_WHITESPACE) {
                    if ($ts[0] == '"') {
                        $ts = addcslashes($ts, "\n\t\r");
                    }
                    $new .= $ts;
                    $iw = true;
                } elseif ($tn == T_WHITESPACE) {
                    $nt = @$tokens[$i+1];
                    if (!$iw && (!is_string($nt) || $nt == '$') && !in_array($nt[0], $IW)) {
                        $new .= " ";
                    }
                    $iw = false;
                } elseif ($tn == T_START_HEREDOC) {
                    $new .= "<<<S\n";
                    $iw = false;
                    $ih = true; // in HEREDOC
                } elseif ($tn == T_END_HEREDOC) {
                    $new .= "S;";
                    $iw = true;
                    $ih = false; // in HEREDOC
                    for ($j = $i+1; $j < $c; $j++) {
                        if (is_string($tokens[$j]) && $tokens[$j] == ";") {
                            $i = $j;
                            break;
                        } elseif ($tokens[$j][0] == T_CLOSE_TAG) {
                            break;
                        }
                    }
                } elseif ($tn == T_COMMENT || $tn == T_DOC_COMMENT) {
                    $iw = true;
                } else {
                    if (!$ih) {
                        $ts = strtolower($ts);
                    }
                    $new .= $ts;
                    $iw = false;
                }
            }
            $ls = "";
        } else {
            if (($token != ";" && $token != ":") || $ls != $token) {
                $new .= $token;
                $ls = $token;
            }
            $iw = true;
        }
    }
    return $new;
}
