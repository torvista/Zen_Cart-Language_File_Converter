# Zen Cart - Language File Converter
Utility to convert pre ZC158 language files to array format.

This is imperfect as there are too many oddities in the original files to do a 100% automatic conversion.
This will create lang copies of the files in the correct places to enable comparison with the originals for manual tweaking.

##Useage
1) DO NOT USE ON A LIVE SITE.
2) TRY THIS ON A COPY OF YOUR LIVE SITE
3) DO NOT USE ON A LIVE SITE.

IS THAT CLEAR ENOUGH?

1) Copy the file into your admin directory: from here it can access the shopfront files too.
2) Open the file to edit it.

There are some options at the start of the file. Edit as required. I suggest you do the shopfront first.

$language_to_convert = 'spanish'; //set to your language fileset
$convert_admin_files = true; // set to true to create admin files
$convert_shopfront_files = false; // set to true to create shopfront files

$allow_create_files = false; // prevent any file creation or WILL OVERWRITE THE NEW FILES EACH TIME THIS FILE IS RUN!!!

$debug = true; //set to true to display processing info

2) Log into your admin
3) Manually type the filename: YOUR_ADMIN/zz_lang_creator.php
If you have set to $allow_create_files to true, new files prefixed with .lang will be created in the same places.

Refreshing the shopfront will most likely result in a white screen of death and a debug log file which will indicate the location of the error.
There will be multiple errror, for various reasons. Just work your way through them.

IF YOU RUN THE SCRIPT AGAIN IT WILL OVERWRITE YOUR CHANGES SO IMMEDIATELY RESET THE OPTIONS TO PREVENT THIS.

Use the information in the debug logs to correct the errors by comparing the file with the original.
There will be a comment fragments breaking the format and also embedded constants which need to be escaped with doubled percentage markers

For example
define('BOX_GV_ADMIN_QUEUE', 'Listado de ' . TEXT_GV_NAMES);

becomes
   'BOX_GV_ADMIN_QUEUE' => 'Listado de ' . '%%TEXT_GV_NAMES%%',


##How it works
The script will parse the hard-coded paths, retrieve the list of the files therein and create new ones prefixed with .lang in the same places.
It just uses basic search and replacing which was intended to be quick to code, but ended up not so quick to do but to continue trying to make it perfect is subject to the law of diminishing returns, but go ahead and try.
I will NOT be making more changes to it.




