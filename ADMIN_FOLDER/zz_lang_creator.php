<?php
/** @link https://github.com/torvista/Zen_Cart-Language_File_Converter
 * @version $Id: 09/01/2022 torvista
 */

$language_to_convert = 'spanish'; //set to your language fileset name

$convert_admin_files = false; // set to true to create admin files

$convert_shopfront_files = false; // set to true to create shopfront files

$allow_create_files = false; // prevent any file creation or WILL OVERWRITE THE NEW FILES EACH TIME THIS FILE IS RUN!!!

$unlink_after_create = false; // prevent any file removal or WILL REMOVE THE OLD FILES EACH TIME THIS FILE IS RUN!!!

$debug = false; //set to true to display processing info

/////////////////////////////////////////////
require('includes/application_top.php');
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
    $paths_to_scan = [
        DIR_FS_CATALOG_LANGUAGES,
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/extra_definitions/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/extra_definitions/classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/extra_definitions/responsive_classic/',
       // DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/html_includes/',
        //DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/html_includes/classic/',
       // DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/html_includes/responsive_classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/order_total/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/order_total/classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/order_total/responsive_classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/payment/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/payment/classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/payment/responsive_classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/shipping/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/shipping/classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/shipping/responsive_classic/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/responsive_classic/',
    ];
}
if (!function_exists('mv_printVar')) {//debugging tool only
    /**
     * @param $a
     */
    function mv_printVar($a)
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
?>
    <!doctype html>
    <html <?php echo HTML_PARAMS; ?>>
    <head>
        <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
    </head>
    <body>
    <!-- header //-->

    <?php require DIR_WS_INCLUDES . 'header.php'; ?>
    <!-- header_eof //-->

    <!-- body //-->
    <div class="container-fluid">
        <!-- body_text //-->
        <h1>Create lang.*.php files for : "<?php echo $language_to_convert; ?>"</h1>
        <?php
            if ($convert_admin_files) {
             echo '<h2>create lang.*.php files for admin</h2>';
                }
            if ($convert_shopfront_files) {
             echo '<h2>create lang.*.php files for shopfront</h2>';
                }
        if (!$convert_admin_files && !$convert_shopfront_files) {
             echo '<h2>no files set for conversion</h2>';
        }
        if ($debug) {
            mv_printVar($paths_to_scan);
        }
        foreach ($paths_to_scan as $path_to_scan) {
            echo '<h2>path to scan:' . $path_to_scan . '</h2>';

            //get the main language file
            if (file_exists($path_to_scan . $language_to_convert . ".php")) {
                $filelist = [$path_to_scan . $language_to_convert . ".php"];
            } else {
                $filelist = glob($path_to_scan . "*.php");
            }
            if ($debug) {
                echo '$filelist: all files';
                mv_printVar($filelist);
            }

            //filter out any pre-existing lang.*.php files
            $filelist = preg_grep("/lang\./", $filelist, PREG_GREP_INVERT);
            if ($debug) {
                echo '$filelist: any pre-existing lang.*.php removed';
                mv_printVar($filelist);
            }

            foreach ($filelist as $filename) {
                echo '<p>File to convert: <b>' . $filename . '</b></p>';

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
                $replacement = ")"; 
                $contents = preg_replace($pattern, $replacement, $contents);
                
                //replace comma with  =>
                $contents = str_replace("',", "' => ", $contents);

                //remove trailing );
                $contents = str_replace(");", ",", $contents);

                //find start of array list
                $start = strpos($contents, "    '");
                if ($start === false) {
                    echo '<p class="messageStackWarning">Error: start of constants not identified.</p>';
                }
                $contents = substr_replace($contents, '$define = [' . "\n", $start, 0);

                //end of array list
                $contents .= "\n" . '];';
                $contents .= "\n\n" . 'return $define;';

                $file_info = pathinfo($filename);
                $filename_lang = $file_info['dirname'] . '/lang.' . $file_info['filename'] . '.php';

                if ($allow_create_files) {
                     file_put_contents($filename_lang, $contents);
                    echo '<p>New file created:<b>' . $filename_lang . '</b></p>';
                    echo '<hr style="text-align:left;width:75%;margin-right:100%">';
                    if ($unlink_after_create) { 
                       unlink($filename); 
                    }
                } else {
                    echo '<p>New file would be created (currently disabled in script):<b>' . $filename_lang . '</b></p>';
                }
            }
            echo '<hr>';
        }
        ?>
        <!-- body_text_eof //-->
    </div>
    <!-- body_eof //-->

    <!-- footer //-->
    <?php require DIR_WS_INCLUDES . "footer.php"; ?>
    <!-- footer_eof //-->

    </body>
    </html>
<?php
require DIR_WS_INCLUDES . 'application_bottom.php';
