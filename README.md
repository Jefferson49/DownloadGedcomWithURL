# DownloadGedcomWithURL
A [weebtrees](https://webtrees.net) 2.1 custom module to download GEDCOM files on URL requests with the tree name, GEDCOM file name and authorization provided as parameters within the URL.

**URL Format:**  
http://MY_URL/webtrees/index.php?route=/webtrees/DownloadGedcomWithURL&tree=MY_TREE&file=MY_FILENAME&privacy=MY_PRIVACY_LEVEL&format=MY_EXPORT_FORMAT&encoding=MY_ENCODING&line_endings=MY_ENDINGS

**Example URLs**  
http://MY_URL/webtrees/index.php?route=/webtrees/DownloadGedcomWithURL&tree=tree1

http://MY_URL/webtrees/index.php?route=/webtrees/DownloadGedcomWithURL&tree=tree1&file=download

http://MY_URL/webtrees/index.php?route=/webtrees/DownloadGedcomWithURL&tree=tree1&file=test&privacy=user&format=zip&encoding=ANSEL&line_endings=LF

**URL parameters**  
* MY_TREE specifies the webtrees tree name
  * Mandatory parameter

* MY_FILENAME has to be provided without .ged extension, i.e. use this_file instead of this_file.ged
  * Tree name is taken as default if MY_FILENAME is not provided

* MY_PRIVACY_LEVEL specifies the user role, in which the GEDCOM export is executed
  * Valid values: gedadmin, user, visitor, none (Default)

* MY_EXPORT_FORMAT specifies the file format for the export
  * Valid values: gedcom (Default), zip, zipmedia, gedzip

* MY_ENCODING specifies the encoding of the generated GEDCOM file
  * Valid values: UTF-8 (Default), UTF-16BE, ANSEL, ASCII, CP1252

* MY_ENDINGS specifies the line endings in the generated GEDCOM file
  * Valid values: CRLF (Default), LF

**Example Script:**  
The file ExamplePythonScript.py contains an example, how an automatic download could be performed with a Python script

**IMPORTANT SECURITY NOTE:**  
Please note that installing this module will enable everyone, who can reach the webtrees URL, to download the GEDCOM files from webtrees. Therefore, you should consider to use this module in secure private networks only or apply additional access restrictions, e.g. for certain IP addresses only.

**Installation**  
Copy the folder "change_language_with_url" into the "module_v4" folder of your webtrees installation.

**Webtrees versions:**  
The module was developed and tested with [webtrees 2.1.4](https://webtrees.net/download)

**Github repository:**  
https://github.com/Jefferson49/DownloadGedcomWithURL
