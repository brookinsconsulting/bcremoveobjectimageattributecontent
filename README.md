BC Remove Object Image Attribute Content
===================

This extension implements a solution to provide the ability to change the administration UI locale (language) on the fly. This solution requires and provides an extension based kernel class overrides to store cache by siteaccess name + locale identifier and switch ini locale per request dynamically for just the one request.


Version
=======

* The current version of BC Remove Object Image Attribute Content is 0.7.0

* Last Major update: June 15, 2015


Copyright
=========

* BC Remove Object Image Attribute Content is copyright 1999 - 2016 Brookins Consulting

* See: [COPYRIGHT.md](COPYRIGHT.md) for more information on the terms of the copyright and license


License
=======

BC Document Reader is licensed under the GNU General Public License.

The complete license agreement is included in the [LICENSE](LICENSE) file.

BC Document Reader is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License or at your
option a later version.

BC Document Reader is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The GNU GPL gives you the right to use, modify and redistribute
BC Document Reader under certain conditions. The GNU GPL license
is distributed with the software, see the file doc/LICENSE.

It is also available at [http://www.gnu.org/licenses/gpl.txt](http://www.gnu.org/licenses/gpl.txt)

You should have received a copy of the GNU General Public License
along with BC Document Reader in doc/LICENSE.  If not, see [http://www.gnu.org/licenses/](http://www.gnu.org/licenses/).

Using BC Document Reader under the terms of the GNU GPL is free (as in freedom).

For more information or questions please contact: license@brookinsconsulting.com


Requirements
============

The following requirements exists for using BC Remove Object Image Attribute Content extension:


### eZ Publish version

* Make sure you use eZ Publish version 4.x (required) or higher.

* Designed and tested with eZ Publish Community Project GitHub Release tag (via composer) v2015.01.3


### PHP version

* Make sure you have PHP 5.x or higher.


Dependencies
============

* This extension command line script only depends on eZ Publish Legacy only


Features
========

### Command Line Script

This solution provides a single multi use command line script:

* eZ Publish PHP Command Line script : `extension/ezpremoveobjectimageattributecontent/bin/php/ezpremoveobjectimageattributecontent.php`


Installation
============

### Extension Installation via Composer

Run the following command from your project root to install the extension:

    bash$ composer require brookinsconsulting/ezpremoveobjectimageattributecontent dev-master;


### Extension Activation

eZ Publish Legacy extension script extensions are **not** activated via ini settings. Normal site.ini extension activation settings are not required to use this extension and it's solution.


Usage
=====

The solution is configured to work virtually by default once properly installed.

### Example Usage: Script Usage Help Command

First, Run the provided and detailed script help command:

    php ./extension/ezpremoveobjectimageattributecontent/bin/php/ezpremoveobjectimageattributecontent.php --help;

**Note**: The above command documents very clearly the required / optional script parameters with examples and defaults.


### Example Usage: Test evaluate (mock) the removal multiple image attribute(s) image content from all versions of multiple image object(s), informational execution only, makes no change to database

    php ./extension/ezpremoveobjectimageattributecontent/bin/php/ezpremoveobjectimageattributecontent.php --object-ids=126,127,180 --attribute-identifiers=image,image33 --script-verbose=true --script-verbose-level=3 --version=new --test-only;

**Note**: Use of the `--test-only` parameter ensures that no mater which variation (parameter combinations) of the command used, the script will make no changes to any part of the database.


### Example Usage: Remove single image attribute image content from a single image object's current version

    php ./extension/ezpremoveobjectimageattributecontent/bin/php/ezpremoveobjectimageattributecontent.php --object-ids=126 --attribute-identifiers=image --script-verbose=true --script-verbose-level=3 --version=current;


### Example Usage: Remove multiple image attribute(s) image content from all versions of multiple image object(s)

    php ./extension/ezpremoveobjectimageattributecontent/bin/php/ezpremoveobjectimageattributecontent.php --object-ids=126,127,180 --attribute-identifiers=image,image33 --script-verbose=true --script-verbose-level=3 --version=all;


### Example Usage: Remove multiple image attribute(s) image content in a new version multiple image object(s)

    php ./extension/ezpremoveobjectimageattributecontent/bin/php/ezpremoveobjectimageattributecontent.php --object-ids=126,127,180 --attribute-identifiers=image,image33 --script-verbose=true --script-verbose-level=3 --version=new;


Troubleshooting
===============

### Read the FAQ

Some problems are more common than others. The most common ones are listed in the the [doc/FAQ.md](doc/FAQ.md)


### Support

If you have find any problems not handled by this document or the FAQ you can contact Brookins Consulting through the support system: [http://brookinsconsulting.com/contact](http://brookinsconsulting.com/contact)

