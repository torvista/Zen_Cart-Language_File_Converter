<?php
// **** READ THE README FIRST (!!) *****
// Place this file in your admin folder. Log into admin and manually type the filename ADMIN_FOLDER/dev-lang_creator.php.
// (the "dev-" prefix hides the file from Git)

declare(strict_types=1);

/**
 * @link https://github.com/torvista/Zen_Cart-Language_File_Converter
 * @note uses tokens
 * @version $Id: 15 Apr 2026 torvista & ChatGPT
 */

/**
 * @var $PHP_SELF
 */

require 'includes/application_top.php';
$languages = zen_get_languages();

$language_to_convert = '';
$languages_pulldown = [];
foreach ($languages as $key => $value) {
    $languages_pulldown[$key]['id'] = $value['directory'];
    $languages_pulldown[$key]['text'] = $value['directory'];
    if (!empty($_POST['language_to_convert']) && $value['directory'] === $_POST['language_to_convert']) {
        $language_to_convert = $_POST['language_to_convert'];
    }
}

//admin or storefront
$fileset_source = empty($_POST['fileset_source']) ? '' : zen_db_prepare_input($_POST['fileset_source']);
$create_files = !empty($_POST['create_files']);

// integer 0 - leave original files in place
// 1 - rename original files to *.LEGACY php so they are ignored: RECOMMENDED for future reference as some constants have been moved to other files
$legacy_file_action = empty($_POST['legacy_file_action']) ? 0 : (int)$_POST['legacy_file_action'];
unset($_POST);

$paths_to_scan = [];

if ($fileset_source === 'admin') {
    $paths_to_scan = [
        DIR_FS_ADMIN . DIR_WS_LANGUAGES,
        DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language_to_convert . '/',
        DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language_to_convert . '/extra_definitions/',
        DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language_to_convert . '/modules/newsletters/',
        //plugins
        DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language_to_convert . '/dbio/',
    ];

    $files_to_skip = [
        DIR_FS_ADMIN . DIR_WS_LANGUAGES . $language_to_convert . '/' . 'ih_about.php',
    ];
}

if ($fileset_source === 'storefront') {
    $paths_to_scan = [
        DIR_FS_CATALOG_LANGUAGES,
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/extra_definitions/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/order_total/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/payment/',
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/modules/shipping/',
    ];

    //Get all template folders
    $templates = zen_get_catalog_template_directories();
    foreach ($templates as $template_dir => $template_info) {
        $paths_to_scan[] = DIR_FS_CATALOG_LANGUAGES . $language_to_convert . "/$template_dir/";
        $paths_to_scan[] = DIR_FS_CATALOG_LANGUAGES . $language_to_convert . "/extra_definitions/$template_dir/";
        $paths_to_scan[] = DIR_FS_CATALOG_LANGUAGES . $language_to_convert . "/modules/order_total/$template_dir/";
        $paths_to_scan[] = DIR_FS_CATALOG_LANGUAGES . $language_to_convert . "/modules/payment/$template_dir/";
        $paths_to_scan[] = DIR_FS_CATALOG_LANGUAGES . $language_to_convert . "/modules/shipping/$template_dir/";
    }

    //Custom folders to include
    $custom_folders_storefront = [
    ];

    //Files to exclude
    $files_to_skip = [
        DIR_FS_CATALOG_LANGUAGES . $language_to_convert . '/extra_definitions/bootstrap/zca_bootstrap_id.php',
    ];
    $paths_to_scan = array_merge($paths_to_scan, $custom_folders_storefront);
}

// function only used for outputting formatted debugging output to browser
if (!function_exists('mv_printVar')) {
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
 * @param  string  $source_filename
 * @param  bool  $create_file
 * @return void
 */
function convertFileToLang(string $source_filename, bool $create_file = false): void
{
    $code = file_get_contents($source_filename);
    $tokens = token_get_all($code);

    $output = "<?php\n";
    $arrayBody = "";

    $count = count($tokens);

    $firstDocblockCaptured = false;
    $startedArrayContent = false;
    $seenNonHeaderContent = false;

    $pendingComment = null;

    $lastLine = 1;

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];

        $tokenId = is_array($token) ? $token[0] : null;
        $tokenText = is_array($token) ? $token[1] : $token;
        $tokenLine = is_array($token) ? $token[2] : $lastLine;

        // ---- DECLARE(strict_types=1); ----
        if ($tokenId === T_DECLARE && !$seenNonHeaderContent) {
            $declareStatement = '';

            for (; $i < $count; $i++) {
                $t = $tokens[$i];
                $text = is_array($t) ? $t[1] : $t;

                $declareStatement .= $text;

                if ($text === ';') {
                    break;
                }
            }

            $output .= trim($declareStatement) . "\n\n";
            $lastLine = $tokenLine;
            continue;
        }

        // ---- DOCBLOCKS (/** */) ----
        if ($tokenId === T_DOC_COMMENT) {
            if (!$firstDocblockCaptured && !$seenNonHeaderContent) {
                $output .= trim($tokenText) . "\n\n";
                $firstDocblockCaptured = true;
            } else {
                if ($startedArrayContent) {
                    $lineDiff = $tokenLine - $lastLine;
                    if ($lineDiff > 1) {
                        $arrayBody .= str_repeat("\n", $lineDiff - 1);
                    }
                }

                $arrayBody .= "    " . trim($tokenText) . "\n";
                $startedArrayContent = true;
            }

            $lastLine = $tokenLine;
            continue;
        }

        // ---- NORMAL COMMENTS ----
        if ($tokenId === T_COMMENT) {
            // Look ahead to next non-whitespace token
            $j = $i + 1;
            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                $j++;
            }

            $nextToken = $tokens[$j] ?? null;
            $nextTokenId = is_array($nextToken) ? $nextToken[0] : null;
            $nextTokenText = is_array($nextToken) ? strtolower($nextToken[1]) : $nextToken;

            // Comment directly before define → store it
            if ($nextTokenId === T_STRING && $nextTokenText === 'define') {
                $pendingComment = trim($tokenText);
                $lastLine = $tokenLine;
                continue;
            }

            // Header comment
            if (!$seenNonHeaderContent) {
                $output .= trim($tokenText) . "\n";
            } else {
                if ($startedArrayContent) {
                    $lineDiff = $tokenLine - $lastLine;
                    if ($lineDiff > 1) {
                        $arrayBody .= str_repeat("\n", $lineDiff - 1);
                    }
                }

                $arrayBody .= "    " . trim($tokenText) . "\n";
                $startedArrayContent = true;
            }

            $lastLine = $tokenLine;
            continue;
        }

        // ---- DEFINE HANDLING ----
        if ($tokenId === T_STRING && strtolower($tokenText) === 'define') {
            if ($startedArrayContent) {
                $lineDiff = $tokenLine - $lastLine;
                if ($lineDiff > 1) {
                    $arrayBody .= str_repeat("\n", $lineDiff - 1);
                }
            }

            // Move to "("
            while ($i < $count && (!is_string($tokens[$i]) || $tokens[$i] !== '(')) {
                $i++;
            }

            // Get constant name
            $i++;
            while ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                $i++;
            }

            $constName = trim($tokens[$i][1], "'\"");

            // Move to comma
            while ($i < $count && (!is_string($tokens[$i]) || $tokens[$i] !== ',')) {
                $i++;
            }

            // ---- CAPTURE VALUE ----
            $i++;
            $value = '';
            $parenDepth = 1;

            for (; $i < $count; $i++) {
                $t = $tokens[$i];
                $text = is_array($t) ? $t[1] : $t;

                if ($text === '(') {
                    $parenDepth++;
                } elseif ($text === ')') {
                    $parenDepth--;
                    if ($parenDepth === 0) {
                        break;
                    }
                }

                $value .= $text;
            }

            $value = trim($value);

            // Output pending comment ABOVE define
            if ($pendingComment !== null) {
                $arrayBody .= "    {$pendingComment}\n";
                $pendingComment = null;
            }

            $arrayBody .= "    '{$constName}' => {$value},\n";
            $startedArrayContent = true;

            // Skip to semicolon
            while ($i < $count && (!is_string($tokens[$i]) || $tokens[$i] !== ';')) {
                $i++;
            }

            $lastLine = $tokenLine;
            continue;
        }

        // ---- TRACK HEADER BREAK ----
        if (
            $tokenId !== T_WHITESPACE &&
            $tokenId !== T_OPEN_TAG &&
            $tokenId !== T_DOC_COMMENT &&
            $tokenId !== T_DECLARE
        ) {
            $seenNonHeaderContent = true;
        }

        $lastLine = $tokenLine;
    }

    // ---- FINAL OUTPUT ----
    $output .= "\$define = [\n";
    $output .= rtrim($arrayBody, "\n") . "\n";
    $output .= "];\n\nreturn \$define;\n";

    // ---- WRITE FILE ----
    $dir = dirname($source_filename);
    $filename = basename($source_filename);
    $newFile = $dir . DIRECTORY_SEPARATOR . 'lang.' . $filename;

    if ($create_file) {
        file_put_contents($newFile, $output);
        echo '<p>New file created:<b>"' . $newFile . '"</b></p>';
    } else {
        echo '<p>New file <em>would</em> be created:<b>"' . $newFile . '"</b></p>';
    }
}

/**
 * @param  string  $filename
 * @param  int  $legacy_file_action
 * @return void
 */
function handle_legacy_file(string $filename, int $legacy_file_action = 0): void
{
    switch ($legacy_file_action) {
        //rename source files to *.LEGACY php
        case (1):
            $filename_new = substr_replace($filename, '.LEGACY php', strrpos($filename, '.php'));
            $file_renamed = rename($filename, $filename_new);
            if (!$file_renamed) {
                echo '<p>Error: source file "' . $filename . '" NOT renamed to "' . $filename_new . '"</p>';
            } else {
                echo '<p>Source file "' . $filename . '" renamed to "' . $filename_new . '"</p>';
            }
            break;

        //do nothing with the source files
        default:
            echo '<p>Source file "' . $filename . '" left in place</p>';
    }
}

?>
    <!doctype html>
    <html <?= HTML_PARAMS ?>>
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
        <h1>Developer Tool: Create lang.*.php files from legacy Language Files</h1>
        <h2 class="mark">UNDER NO CIRCUMSTANCES SHOULD YOU EXECUTE THIS FILE ON YOUR WORKING/PRODUCTION SHOP: YOU WILL BREAK IT!</h2>
        <h3>BEFORE you do ANYTHING, copy/make a backup of the current /language directories of both the Admin and Storefront!</h3>
        <p>The new files created by this script are unlikely to be 100% perfect and so may cause a white-screen-of-death (WSOD), requiring some manual fixes (just review the /log/debug files to determine where the fault lies).<br>
            Consequently, you MUST use a duplicate (working) development copy of your shop to create and review the new files, in your own time, stress-free.<br>
            As these are <b>new</b> files causing the problem, there is no irreparable damage, just work though the debugs, there should be very few.</p>

        <h3>Notes</h3>
        <ul class="list-group">
            <li class="list-group-item">The conversion only creates lang.* <b>equivalents</b> from the existing files. It will NOT add or remove constants to bring those files "up-to-date" to match the current english lang.* files.<br>
                Hence this initial creation is only the start of the process: you still <b>must</b> do a manual compare of the english files vs. your files to ensure that all definitions are in place/match their english equivalents (by using Beyond Compare or similar).
            </li>

            <li class="list-group-item">The script parses ALL the template directories. If you don't want that to happen, you need to modify the section //Get all template folders
            </li>

            <li class="list-group-item">Do a Test Run to show the file paths being parsed...LOOK AT THE RESULTS.<br>
                Maybe you have some custom directory you want to add (in the array $custom_folders_storefront).
                Maybe you have some files you don't want to add (in the array $files_to_skip).
            </li>

            <li class="list-group-item">
                If the source file define is a constant whose value is another constant,<br>
                e.g<br>
                <pre>define('BOX_TOOLS_GOOGLE_MERCHANT_CENTER', BOX_CONFIGURATION_GOOGLE_MERCHANT_CENTER);//Tools Menu</pre>
                This will cause a WSOD in the lang. file: the value must be wrapped in '%%.....%%'.<br>
                e.g<br>
                <pre>'BOX_TOOLS_GOOGLE_MERCHANT_CENTER' => '%%BOX_CONFIGURATION_GOOGLE_MERCHANT_CENTER%%',</pre>
            </li>

            <li class="list-group-item">The script will use any existing legacy file to create a lang. equivalent.<br>
                If you run it a second time, and have left the original files in place (not renamed), the lang. files will be created again, overwriting any fixes you have done.<br>
                So...don't run it twice on the same fileset or remove/rename the legacy files.
            </li>

            <li class="list-group-item">
                Comments are welcome in the <a href="https://github.com/torvista/Zen_Cart-Language_File_Converter" target="_blank">GitHub</a>, code improvements even more so. Which would be a first.
            </li>
        </ul>
        <?php
        echo zen_draw_form('lang_creator', basename($PHP_SELF), params: 'class="form-inline"');
        ?>
        <fieldset>
            <legend>Run Options</legend>
            <div class="form-group">
                <label>Language directory to convert: <?= zen_draw_pull_down_menu('language_to_convert', $languages_pulldown, parameters: 'class="form-control"') ?></label>
            </div>
            <br>
            <div class="form-group control-label">Which fileset to process:<br>
                <label>Admin <?= zen_draw_radio_field('fileset_source', 'admin', parameters: 'class="form-control" required') ?></label>&nbsp;&nbsp;
                <label>Storefront <?= zen_draw_radio_field('fileset_source', 'storefront', parameters: 'class="form-control" required') ?></label>
            </div>
            <br>
            <div class="form-group control-label">Legacy source files:<br>
                <label>Leave them in place <?= zen_draw_radio_field('legacy_file_action', '0', parameters: 'class="form-control" required') ?></label>&nbsp;&nbsp;
                <label>Rename to *.LEGACY php <?= zen_draw_radio_field('legacy_file_action', '1', parameters: 'class="form-control" required') ?></label>
            </div>
            <br>
            <div class="form-group control-label">Action on "Run":<br>
                <label>Test Run (no conversion/creation) <?= zen_draw_radio_field('create_files', '0', parameters: 'class="form-control" required') ?></label>&nbsp;&nbsp;
                <label>Create the lang.* files <?= zen_draw_radio_field('create_files', '1', parameters: 'class="form-control" required') ?></label>
            </div>
            <br>
            <button type="submit" class="btn btn-success" onclick="return confirm('Sure?');">Run!</button>
        </fieldset>
        <?php
        echo '</form>';

        if (!empty($fileset_source)) {
            echo "<h2>Creating lang.*.php files for $fileset_source</h2>";
            mv_printVar($paths_to_scan);

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
                echo '$file_list: all files';
                mv_printVar($file_list);

                //filter out any pre-existing lang.*.php files
                $file_list = preg_grep("/lang\./", $file_list, PREG_GREP_INVERT);

                //filter out excluded files
                $file_list = array_diff($file_list, $files_to_skip);

                echo '$file_list: pre-existing lang.*.php and excluded files removed';
                mv_printVar($file_list);

                foreach ($file_list as $filename) {
                    echo '<p>File to convert: <b>"' . $filename . '"</b></p>';
                    convertFileToLang($filename, $create_files);
                    handle_legacy_file($filename, $legacy_file_action);
                    echo '<hr style="text-align:left;width:75%;margin-right:100%;">';
                }
                echo '<hr>';
            }
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
