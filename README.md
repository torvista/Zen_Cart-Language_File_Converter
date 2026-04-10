# Zen Cart - Language File Converter
##  Last tested on Zen Cart 2.2.2.

Utility to convert legacy (pre-ZC158 language files that use lists of ``define('CONSTANT_NAME', 'constant value')``)  
to the current array (lang.\*) format.

This will create lang.\* copies of the files in the same locations to enable comparison with the originals for manual tweaking.

The automatic conversion may be imperfect due to oddities in the original files... you WILL need to review/manually fix the inconsistencies, but these will all create debug logs to indicate the problem file.

## Usage
1. DO NOT USE ON A LIVE SITE.
2. USE THIS ON A DEVELOPMENT COPY OF YOUR LIVE SITE: this work will break the page loading until you have fixed all the issues.
3. DO NOT USE ON A LIVE SITE.

IS THAT CLEAR ENOUGH?

This script does not do encoding conversion. The original language files should be utf-8 already. If not, there is info online on how to do batch conversions. Not necessary for files that have no accents (multibyte characters) like english.

1. If you are trying to update an old language pack, copy those files to their correct locations.
Don't worry about them working or matching the original english file equivalents now, manual checking/comparison will have to be done in any case.

1. Copy this conversion script (single file) into your admin directory: from here it can access the shopfront files too.

2. Log into your admin

3. Manually type the filename: `YOUR_ADMIN/dev-lang_creator.php`

4. Read the notes, do a Test Run

5. For the storefront, you may add your custom (template) directories to search in the array ``$custom_folders_storefront``
in the script

6. After running the creation, that will probably cause a white screen of death (WSOD) due to an oddity in a new file...but there will always be an associated debug log file to indicate the location of the error.  
Use the information in the debug log to correct the error by comparing the offending file with the original and the core english version.  
If you are not yet using a code editor or IDE to highlight syntax errors, well, what can I say.
  
Maybe there will be comment fragments breaking the format and embedded constants which need to be escaped with double percentage delimiters.
For example

```
define('BOX_GV_ADMIN_QUEUE', 'Listado de ' . TEXT_GV_NAMES);
```

becomes

```
   'BOX_GV_ADMIN_QUEUE' => 'Listado de %%TEXT_GV_NAMES%%',
```

In the subdirectory `language/YOURLANGUAGE/`
there are now a lot of files, old and new mixed.

I suggest as you review/compare a file, rename the extension of the old one from .php to something else (so it cannot be auto-parsed by any script) for future reference and eventual deletion.

e.g. `about_us.OLD php`

Thus, they will both be visually separated from the lang. files and not be included as overrides to the lang. files.

## Documentation Links 

- [1.5.8 Language Files](https://docs.zen-cart.com/dev/languages/158_language_files/)


## Creating/converting a language pack
If you are creating a new language pack or renovating an old one, it's an exponential job (tends towards zero, but never actually gets there), there are always tweaks. 
So, you should consider putting it on GitHub from the start as it is far easier to tweak that with minor changes, rather than creating/submitting a ZC Plugin file for submission each time you change a comma. Also, it makes it easy for others to improve it.
