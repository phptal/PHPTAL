
# PHPTAL - Template Attribute Language for PHP

Master: [![Build Status](https://secure.travis-ci.org/pornel/PHPTAL.png?branch=master)](http://travis-ci.org/pornel/PHPTAL)

Usage requirements
==================

To use PHPTAL in your projects, you will only require PHP 5.1.2 or later.

If you want to use the builtin internationalisation system (I18N) the gettext extension must be compiled into PHP (`--with-gettext`).


Non-PEAR install
================

To run you only need PHPTAL.php and files in PHPTAL directory. Other files are for unit tests and PEAR installer.

Get the latest PHPTAL package from http://phptal.org.

    tar zxvf PHPTAL-X.X.X.tar.gz
    mv PHPTAL-X.X.X/PHPTAL* /path/to/your/php/include/path/


PEAR Install
============

Get the latest PHPTAL package from http://phptal.org.

Then run:

    pear install PHPTAL-X.X.X.tar.gz



Getting the latest development version
======================================

You can checkout the latest development version using:

    svn co https://svn.motion-twin.com/phptal/trunk phptal



PHPTAL development requirements
===============================

If you want to hack PHPTAL (don't forget to send me patches), you will require:

  - The PHPTAL development package
  - PEAR (to easily install other tools)
    http://pear.php.net

  - Phing to run maintainance tasks

        pear channel-discover pear.phing.info
        pear install phing/phing

  - PHPUnit 3.4 to run tests

        pear channel-discover pear.phpunit.de
		pear channel-discover pear.symfony-project.com
		pear channel-discover components.ez.no
        pear install phpunit/PHPUnit
