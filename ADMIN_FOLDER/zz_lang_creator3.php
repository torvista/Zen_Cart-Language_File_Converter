<?php
/**
 * @copyright Copyright 2003-2020 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license   http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version   $Id: DrByte 2020 May 16 Modified in v1.5.7 $
 */

$allow_conversion = false;
$language_to_convert = 'spanish';
$debug = false;

/////////////////////////////////////////////
require('includes/application_top.php');
//admin only
/*
$paths_to_scan = [
    DIR_FS_ADMIN . DIR_WS_LANGUAGES,
    DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language_to_convert . '/',
    DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language_to_convert . '/extra_definitions/',
    DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language_to_convert . '/modules/newsletters/',
];*/
$paths_to_scan = [
    //DIR_FS_CATALOG_LANGUAGES,
    DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/',
    DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/classic/',
    DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/extra_definitions/',
    DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/extra_definitions/classic/',
    DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/extra_definitions/responsive_classic/',
    DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/html_includes/',
    DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/html_includes/classic/',
    DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/html_includes/responsive_classic/',
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
if (!function_exists('mv_printVar')) {//debugging only
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
        <h1>Convert Language Files</h1>
        <h2>Convert language files: "<?php echo $language_to_convert; ?>"</h2>
        <?php

        if ($debug) {
            mv_printVar($paths_to_scan);
        }
        foreach ($paths_to_scan as $path_to_scan) {
            echo '<h2>path to scan:' . $path_to_scan . '</h2>';
            $filelist = glob($path_to_scan . "*.php");
            if ($debug) {
                mv_printVar($filelist);
            }
            //filter out any pre-existing lang.*.php files
            $filelist = preg_grep("/lang\./", $filelist, PREG_GREP_INVERT);
            if ($debug) {
                mv_printVar($filelist);
            }

            //get filename only, to ease final renaming
            $filelist = str_replace($path_to_scan, '', $filelist);
            if ($debug) {
                mv_printVar($filelist);
            }

            foreach ($filelist as $filename) {
                echo '<p>File to convert: ' . $path_to_scan . '<b>' . $filename . '</b></p>';

                $contents = trim(file_get_contents($path_to_scan . $filename));
                if ($language_to_convert === 'spanish') {
                    $contents = str_replace(
                        "//Spanish Language Pack for Zen Cart 1.5x: https://github.com/torvista/Zen-Cart-1.5x-Spanish-Language-Pack",
                        "// Spanish Language Pack for Zen Cart: https://github.com/torvista/Zen_Cart-Spanish_Language_Pack", $contents);
                    }
                /*TEMP
                $contents_length = strlen($contents);
                $pos_end_tag = strrpos($contents, "?>");
                if ($pos_end_tag === $contents_length - 2) {
                    if ($debug) {
                        echo '<p>php end tag "?>" found and removed<p>';
                    }
                    $contents = substr_replace($contents, '', $pos_end_tag, 2);
                }

                //remove empty lines
                $contents = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $contents);

                // 01 leading text: remove "define"
                $contents = str_replace("define ('", "define('", $contents);
                $contents = str_replace("define('", "    '", $contents);
                // 02 middle comma with  =>
                $contents = str_replace(", ", " => ", $contents);
                // 03 trailing );
                $contents = str_replace(");", ",", $contents);

                //find start of array list
                $start = strpos($contents, "    '");
                if ($start === false) {
                    '<p class="messageStackWarning">Error: start of defines not identified.</p>';
                }
                $contents = substr_replace($contents, '$define = [' . "\n", $start, 0);

                //end of array list
                $contents = $contents . "\n" . '];';
                $contents = $contents . "\n\n" . 'return $define;';

TEMP*/
                if ($allow_conversion) {
                    //file_put_contents($path_to_scan . 'lang.' . $filename, $contents);
                    file_put_contents($path_to_scan . '' . $filename, $contents);
                    echo '<p>File converted:' . $path_to_scan . 'lang.<b>' . $filename . '</b></p>';
                } else {
                    echo '<p>File to be converted (currently not enabled in script):' . $path_to_scan . 'lang.<b>' . $filename . '</b></p>';
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
    <?php require DIR_WS_INCLUDES . "footer.php"; ?>
    <!-- footer_eof //-->

    </body>
    </html>
<?php
require DIR_WS_INCLUDES . 'application_bottom.php';
