# Zen Cart - Language File Converter
Utility to convert pre ZC158 language files to ZC158 array format.

This is imperfect as there are too many oddities in the original files to do a 100% automatic conversion.
This will create lang. copies of the files in the correct places to enable comparison with the originals for manual tweaking.

## Useage
1) DO NOT USE ON A LIVE SITE.
2) TRY THIS ON A COPY OF YOUR LIVE SITE
3) DO NOT USE ON A LIVE SITE.

IS THAT CLEAR ENOUGH?

### Creating/converting a language pack
If you are mad enough to be the first to create/convert a language pack, consider putting it on GitHub immediately as there will always be tweaks to do and it is far easier to add them there, than update a ZC Plugin file download each time. Also it makes it easy for others to improve it.

1. This script does not do encoding conversion. The original language files should be utf-8 already. If not, there is info online on how to do batch conversions. Not necessary for files that have no accents (multibyte characters) like english.

2. Copy the original files to their correct locations.
Don't worry about them matching the original english file equivalents now, manual checking/comparison will have to be done in any case.

3. Copy this conversion script (single file) into your admin directory: from here it can access the shopfront files too.

4. Open the file to edit it.

There are some options at the start of the file. Edit as required. I suggest you do the shopfront first so you only half-break your shop!

$language_to_convert = 'spanish'; //set to your language fileset name

$convert_admin_files = false; // set to true to create admin files

$convert_shopfront_files = false; // set to true to create shopfront files

$allow_create_files = false; // prevent any file creation or WILL OVERWRITE THE NEW FILES EACH TIME THIS FILE IS RUN!!!

$debug = false; //set to true to display processing info

2) Log into your admin
3) Manually type the filename: YOUR_ADMIN/zz_lang_creator.php
If no options have been changed, it will not do anything.

If you have set to $allow_create_files to true, new files prefixed with .lang will be created in the same places.

Refreshing the shopfront will now result in a white screen of death and one or more debug log files which will indicate the location of the error.
There will be multiple errors for various reasons.

IF YOU RUN THE SCRIPT AGAIN IT WILL OVERWRITE YOUR CHANGES SO IMMEDIATELY RESET THE OPTIONS TO PREVENT THIS.

Use the information in the debug logs to correct the errors by comparing the file with the original.
It will help to use a code editor to highlight syntax errors.
There will be a comment fragments breaking the format and also embedded constants which need to be escaped with doubled percentage markers

For example

define('BOX_GV_ADMIN_QUEUE', 'Listado de ' . TEXT_GV_NAMES);

becomes

   'BOX_GV_ADMIN_QUEUE' => 'Listado de ' . '%%TEXT_GV_NAMES%%',

In the subdirectory language/YOURLANGUAGE/
there are now a lot of files, old and new mixed.
I suggest as you review/compare a file, rename the old one for future reference and eventual deletion

e.g. z_about_us.OLD php

Thus they wil get separated from the lang. files and also not be included as overrides to the lang. files.

## How it works
The script parses the hard-coded paths and retrieves a list of the files therein.

It parses each file using basic search and replace and create a new file prefixed with .lang in the same place.

It was not intended to be 100% comprehensive and to continue trying to make it perfect is subject to the law of diminishing returns, so I will not be making more changes to it.
But feel free to try.
