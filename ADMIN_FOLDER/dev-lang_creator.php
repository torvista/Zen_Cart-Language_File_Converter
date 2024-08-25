<?php
// **** READ THE README FIRST *****
// Place this file in your admin folder. Log into admin and type the filename ADMIN/AA_lang_creator.php.

declare(strict_types=1);
/**
 * @link    https://github.com/torvista/Zen_Cart-Language_File_Converter
 * @version $Id: 24/08/2024 torvista
 */
//
//set to fileset/folder name of the files to be converted
$language_to_convert = 'english';

// set to true to target admin files
$convert_admin_files = false;

// set to true to target shopfront files
$convert_shopfront_files = false;

// set to true to allow processing of selected target and creation of files.
// THIS WILL OVERWRITE THE NEW lang FILES EACH TIME THIS FILE IS RUN!!! SO SET TO FALSE AFTER THE FIRST SUCCESSFUL RUN (a run with no script errors).
$allow_create_files = true;

// integer 0 - leave original files in place
// 1 - rename original files to *.OLD php so they are ignored: RECOMMENDED for future reference as some constants have been moved to other files
// 2 - delete the original files
$post_create_action = 0;

//true/false to show processing info
$debug = true;

//1 for find-replace (original method attempted), 2 for tokens (works better)
$conversion_method = 2;

///////////////////////////////////////////////////////////////////////////////
define(
    'TEXT_INTRO',
    '<h1>Under no circumstances should this file be used on a production website: use a working development copy to develop your new language</h1>
    <p>Please note that the conversion is only that, a conversion of the old files to the new format. It will not add/remove constants to equate the new files to the english lang files...that is a manual process you will need to do by hand using Beyond Compare or equivalent.</p>
    <h2>Read the comments and options in the file itself</h2>
    <p>Once you have read the comments and set the options to create the lang files and do something (or not) with the original source files, it will do so <b>every time</b> you run the file. So, unless there are processing errors or you wish to improve the results, you should only need to run this once and then remove it from your server once you start to do the edit/comparison by hand, to prevent accidentally overwriting your subsequent work.</p>
    <p>These new .lang files will be missing new constants and have surplus constants that have been combined/removed or moved elsewhere. Consequently, <b>do not register the language until you have done a complete comparison with the english fileset and made the definition lists equivalent</b>. Otherwise you will break the site unnecessarily.<br>Once you have done that, you may register the language. If it (probably) causes a blank page, there will be a debug log to indicate the problem location.</p>
    <p>Comments are welcome in the <a href="https://github.com/torvista/Zen_Cart-Language_File_Converter" target="_blank">GitHub</a>, code improvements even more so. Which would be a first.</p>'
);
define('DEBUG_MODE_ON', '<p><b>Debug mode is ON ($debug = true), showing array processing.</b></p>');
require(__DIR__ . '/includes/application_top.php');
$paths_to_scan = [];
if ($convert_admin_files) {
    $paths_to_scan = [
        DIR_FS_ADMIN . DIR_WS_LANGUAGES,
        DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language_to_convert . '/',
        DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language_to_convert . '/extra_definitions/',
        DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language_to_convert . '/modules/newsletters/',
    ];
}
if ($convert_shopfront_files) {
    array_push(
        $paths_to_scan,
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/aa_test/',
        DIR_FS_CATALOG_LANGUAGES,
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/extra_definitions/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/extra_definitions/classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/extra_definitions/responsive_classic/',
        //DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/html_includes/',
        //DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/html_includes/classic/',
        //DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/html_includes/responsive_classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/order_total/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/order_total/classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/order_total/responsive_classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/payment/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/payment/classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/payment/responsive_classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/shipping/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/shipping/classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/shipping/responsive_classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/responsive_classic/'
    );
}
if (!function_exists('mv_printVar')) {//formatted debugging output only
    /**
     * @param $a
     */
    function mv_printVar($a): void
    {
        $backtrace = debug_backtrace()[0];
        $fh = fopen($backtrace['file'], 'rb');
        $line = 0;
        $code = '';
        while (++$line <= $backtrace['line']) {
            $code = fgets($fh);
        }
        fclose($fh);
        preg_match('/' . __FUNCTION__ . '\s*\((.*)\)\s*;/u', $code, $name);
        echo '<pre>';
        if (!empty($name[1])) {
            echo '<strong>' . trim($name[1]) . '</strong> (' . gettype($a) . "):\n";
        }
        //var_export($a);
        print_r($a);
        echo '</pre><br>';
    }
}

/**
 * @param $token
 *
 * @return bool
 */
function is_constant1($token): bool
{
    //T_CONSTANT_ENCAPSED_STRING	"foo" or 'bar'
    //T_STRING	parent, self, etc.	identifiers, e.g. keywords like parent and self, function names, class names and more are matched. See also T_CONSTANT_ENCAPSED_STRING.
    //T_LNUMBER	123, 012, 0x1ac, etc.	integers
    //T_DNUMBER	0.12, etc.	floating point numbers
    return $token === T_CONSTANT_ENCAPSED_STRING
        || $token === T_STRING
        || $token === T_LNUMBER
        || $token === T_DNUMBER;
}

/** function used only for line-by-line examination of the token processing
 * @param $state
 * @param $token
 *
 * @return void
 */
function dump1($state, $token): void
{
    if (is_array($token)) {
        echo "$state: " . token_name($token[0]) . " [$token[1]] on line $token[2]";
    } else {
        echo "$state: Symbol '$token'";
    }
    echo "\n<br>\n<br>";
}

/**
 * @param $value
 * @return array|string|string[]|null
 */
function strip($value)
{
    return preg_replace('!^([\'"])(.*)\1$!', '$2', $value);
}

?>
    <!doctype html>
    <html <?php
    echo HTML_PARAMS; ?>>
    <head>
        <?php
        require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
    </head>
    <body>
    <!-- header //-->

    <?php
    require DIR_WS_INCLUDES . 'header.php'; ?>
    <!-- header_eof //-->

    <!-- body //-->
    <div class="container-fluid">
        <!-- body_text //-->
        <h1>Create lang.*.php files from fileset : "<?php
            echo $language_to_convert; ?>"</h1>
        <?php
        echo TEXT_INTRO;
        if ($debug) {
            echo DEBUG_MODE_ON;
        }
        if ($conversion_method === 1) {
            $using_method = ' using method 1: find-replace';
        } elseif ($conversion_method === 2) {
            $using_method = ' using method 2: tokens';
        } else {
            $using_method = '';
        }
        if ($convert_admin_files) {
            echo "<h2>Create lang.*.php files for admin$using_method.</h2>";
        }
        if ($convert_shopfront_files) {
            echo "<h2>Create lang.*.php files for shopfront$using_method.</h2>";
        }
        if (!$convert_admin_files && !$convert_shopfront_files) {
            echo '<h2>Neither Admin nor Shopfront files are currently set for conversion</h2>';
        }
        if ($debug) {
            mv_printVar($paths_to_scan);
        }
        foreach ($paths_to_scan as $path_to_scan) {
            echo '<h2>Path to scan: "' . $path_to_scan . '"</h2>';
            $file_list = false;
            //get the main language file
            if (file_exists($path_to_scan . $language_to_convert . ".php")) {
                $file_list = [$path_to_scan . $language_to_convert . ".php"];
            } else {
                $file_list = glob($path_to_scan . "*.php");
            }
            if ($file_list === false || count($file_list) === 0) {
                echo '<h3>No files found in: "' . $path_to_scan . '"</h3>';
                continue;
            }
            if ($debug) {
                echo '$file_list: all files';
                mv_printVar($file_list);
            }

            //filter out any pre-existing lang.*.php files
            $file_list = preg_grep("/lang\./", $file_list, PREG_GREP_INVERT);
            if ($debug) {
                echo '$file_list: any pre-existing lang.*.php removed';
                mv_printVar($file_list);
            }
            //mv_printVar($file_list);die;
            foreach ($file_list as $filename) {
                echo '<p>File to convert: <b>' . $filename . '</b></p>';
                $contents = '';
                switch ($conversion_method) {
                    case 1: //find-replace
                        $contents = trim(file_get_contents($filename));

                        //remove comments
                        $contents = preg_replace('!/\*.*?\*/!s', '', $contents);
                        $contents = preg_replace('!;(\s*)//(.*)!', ";\n", $contents);
                        // echo $contents;die;
                        foreach (token_get_all($contents) as $token) {
                            if ($token[0] !== T_COMMENT) {
                                continue;
                            }
                            $contents = str_replace($token[1], '', $contents);
                        }

                        //remove empty lines
                        $contents = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $contents);

                        //remove php end tag
                        $contents_length = strlen($contents);
                        $pos_end_tag = strrpos($contents, "?>");
                        if ($pos_end_tag === $contents_length - 2) {
                            if ($debug) {
                                echo '<p>php end tag "?>" found and removed<p>';
                            }
                            $contents = substr_replace($contents, '', $pos_end_tag, 2);
                        }

                        //make leading "define" structure consistent
                        $contents = str_replace("define ('", "define('", $contents);
                        $contents = str_replace("define ( '", "define('", $contents);

                        //make definition comma separator consistent (no space)
                        $contents = str_replace("' ,'", "','", $contents);
                        $contents = str_replace("' , '", "','", $contents);
                        $contents = str_replace("', '", "','", $contents);
                        $contents = str_replace("',  '", "','", $contents);

                        // Just in case it's a quote comma then space double
                        // quote or space constant, deal with that too.
                        $contents = str_replace("', ", "',", $contents);

                        //remove "define"
                        $contents = str_replace("define('", "    '", $contents);

                        // Fix zen_href_link calls before fixing comma
                        $pattern = "/,(\s)*'',(\s)*'SSL'\)/";
                        $replacement = ")";
                        $contents = preg_replace($pattern, $replacement, $contents);
                        $pattern = "/,(\s)*'',(\s)*'NONSSL'\)/";
                        $contents = preg_replace($pattern, $replacement, $contents);

                        //replace comma with "=>"
                        $contents = str_replace("',", "' => ", $contents);

                        //remove trailing ");"
                        $contents = str_replace(");", ",", $contents);

                        //find start of the array list
                        $start = strpos($contents, "    '");
                        if ($start === false) {
                            echo '<p class="messageStackWarning">Error: start of constants not identified.</p>';
                        }
                        $contents = substr_replace($contents, '$define = [' . "\n", $start, 0);

                        //end of the array list
                        $contents .= "\n" . '];';
                        $contents .= "\n\n" . 'return $define;';
                        break;

                    case 2: //tokens
                        $defines = [];
                        $state = 0;
                        $key = '';
                        $value = '';

                        $contents = trim(file_get_contents($filename));
//remove empty lines
                        //$contents = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $contents);
                        $tokens = token_get_all($contents);
                        $token = reset($tokens);
                        while ($token) {
                            //use this function for nitty-gritty examination of the tokens line by line
                            // dump1($state, $token);
                            //first check is for a recognised construct/not a single character
                            if (is_array($token)) {
                                if ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                                    // do nothing
                                } elseif ($token[0] === T_STRING && strtolower(
                                        $token[1]
                                    ) === 'define') { //"define" detected, set state 1
                                    $state = 1;
                                } elseif ($state === 2 && is_constant1(
                                        $token[0]
                                    )) { // opening bracket has been passed, capture constant NAME: set state 3
                                    $key = "'" . strip($token[1]) . "'";
                                    $state = 3;
                                } elseif ($state === 4 && is_constant1(
                                        $token[0]
                                    )) { // separator "," has been passed, capture constant CONTENT
                                    $value .= $token[1];
                                    //$state = 5;
                                }
                            } else {
                                $symbol = trim($token);
                                if ($symbol === '(' && $state === 1) { // the previous token was a "define", now the start "(" is detected: set state 2
                                    $state = 2;
                                } elseif ($symbol === ',' && $state === 3) { // constant name already captured, separator found: set state 4
                                    $state = 4;
                                } elseif ($symbol === ')' && $state === 4) { // closing bracket detected, $value is complete: reset to state 0
                                    $defines[$key] = $value;
                                    $value = '';
                                    $state = 0;
                                } elseif ($state === 4) {//found . of embedded constant: continue state 4
                                    $value .= $symbol;
                                }
                            }
                            $token = next($tokens);
                        }
//mv_printVar($defines);
                        $defines_list = '';
                        foreach ($defines as $k => $v) {
                            $defines_list .= "    $k => $v,\n"; //ident as four spaces
                        }
                        //echo str_replace("\n", "\n<br>", $defines_list); // show content of defines
                        $contents = "<?php\n" . '$define' . " = [\n";
                        $contents .= $defines_list;
                        $contents .= "];\n\nreturn " . '$define;';
                        break;
                }

                $file_info = pathinfo($filename);
                $filename_lang = $file_info['dirname'] . '/lang.' . $file_info['filename'] . '.php';

                if ($allow_create_files) {
                    file_put_contents($filename_lang, $contents);
                    echo '<p>New file created:<b>' . $filename_lang . '</b></p>';
                    switch ($post_create_action) {
                        //rename old files to *.OLD php
                        case (1):
                            $filename_new = substr_replace($filename, '.OLD php', strrpos($filename, '.php'));
                            $file_renamed = rename($filename, $filename_new);
                            if (!$file_renamed) {
                                echo '<p>error: source file "' . $filename . '" NOT renamed to "' . $filename_new . '"</p>';
                            } elseif ($debug) {
                                echo '<p>source file "' . $filename . '" renamed to "' . $filename_new . '"</p>';
                            }
                            break;

                        //delete old files
                        case (2):
                            $file_deleted = unlink($filename);
                            if (!$file_deleted) {
                                echo '<p>error: source file "' . $filename . '" NOT deleted</p>';
                            } elseif ($debug) {
                                echo '<p>source file "' . $filename . ": deleted</p>";
                            }
                            break;

                        //do nothing with the old files
                        default:
                            if ($debug) {
                                echo '<p>source file "' . $filename . '" left in place</p>';
                            }
                    }
                } else {
                    echo '<p>New file <em>would</em> be created (currently disabled in script):<b>' . $filename_lang . '</b></p>';
                }
                echo '<hr style="text-align:left;width:75%;margin-right:100%">';
            }
            echo '<hr>';
        }
        ?>
        <!-- body_text_eof //-->
    </div>
    <!-- body_eof //-->

    <!-- footer //-->
    <?php
    require DIR_WS_INCLUDES . "footer.php"; ?>
    <!-- footer_eof //-->

    </body>
    </html>
<?php
require DIR_WS_INCLUDES . 'application_bottom.php';
