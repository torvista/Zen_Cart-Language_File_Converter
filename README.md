# Zen Cart - Language File Converter


Utility to convert pre ZC158 language files to ZC158 array format.

This is imperfect as there are too many oddities in the original files to do a 100% automatic conversion.
This will create lang. copies of the files in the correct places to enable comparison with the originals for manual tweaking.

## Usage
1. DO NOT USE ON A LIVE SITE.
2. USE THIS ON A DEVELOPMENT COPY OF YOUR LIVE SITE
3. DO NOT USE ON A LIVE SITE.

IS THAT CLEAR ENOUGH?

### Creating/converting a language pack
If you are mad enough to be the first to create/convert a language pack, consider putting it on GitHub immediately as there will always be tweaks to do and it is far easier to add them there, than update a ZC Plugin file download each time. Also it makes it easy for others to improve it.

1. This script does not do encoding conversion. The original language files should be utf-8 already. If not, there is info online on how to do batch conversions. Not necessary for files that have no accents (multibyte characters) like english.

2. Copy the original files to their correct locations.
Don't worry about them matching the original english file equivalents now, manual checking/comparison will have to be done in any case.

3. Copy this conversion script (single file) into your admin directory: from here it can access the shopfront files too.

There are some options at the start of the file. Edit as required. I suggest you do the shopfront first so you only half-break your shop!

```
//set to fileset/folder name of the files to be converted
$language_to_convert = 'spanish';

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
```

4. Log into your admin

5. Manually type the filename: `YOUR_ADMIN/zz_lang_creator.php`
If no options have been changed, it will not do anything.

If you have set `$allow_create_files` to `true`, new files prefixed with `.lang` will be created in the same places.

If the original language files were in use/registered, refreshing the shopfront will now result in a white screen of death and one or more debug log files which will indicate the location of the error.
There will be multiple errors for various reasons.

IF YOU RUN THE SCRIPT AGAIN IT WILL OVERWRITE YOUR CHANGES SO IMMEDIATELY RESET THE OPTIONS TO PREVENT THIS, OR REMOVE/RENAME THE FILE.

Use the information in the debug logs to correct the errors by comparing the file with the original.
It will help to use a code editor to highlight syntax errors.
There will be a comment fragments breaking the format and also embedded constants which need to be escaped with doubled percentage markers

For example

```
define('BOX_GV_ADMIN_QUEUE', 'Listado de ' . TEXT_GV_NAMES);
```

becomes

```
   'BOX_GV_ADMIN_QUEUE' => 'Listado de ' . '%%TEXT_GV_NAMES%%',
```

In the subdirectory `language/YOURLANGUAGE/`
there are now a lot of files, old and new mixed.
I suggest as you review/compare a file, rename the old one for future reference and eventual deletion

e.g. `z_about_us.OLD php`

Thus they wil get separated from the lang. files and also not be included as overrides to the lang. files.

## How it works
### Method 1
Original attempt 
The script parses the hard-coded paths and retrieves a list of the files therein.

It parses each file using basic search and replace and creates a new file prefixed with `.lang` in the same place.

### Method 2
The script parses the files into tokens which works much better.

### Documentation Links 

- [1.5.8 Language Files](https://docs.zen-cart.com/dev/languages/158_language_files/)

