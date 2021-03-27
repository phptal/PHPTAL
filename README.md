
# PHPTAL - Template Attribute Language for PHP

[![Monthly Downloads](https://poser.pugx.org/phptal/phptal/d/monthly)](https://packagist.org/packages/phptal/phptal)
[![License](https://poser.pugx.org/phptal/phptal/license)](LICENSE)
[![Build Status](https://travis-ci.org/phptal/PHPTAL.svg?branch=master)](https://travis-ci.org/phptal/PHPTAL)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/phptal/PHPTAL/badges/quality-score.png)](https://scrutinizer-ci.com/g/phptal/PHPTAL/)

Requirements
============

If you want to use the builtin internationalisation system (I18N), the php-gettext extension must be installed or compiled into PHP (`--with-gettext`).

Composer install (recommended)
==============================

You can install this package by using [Composer](http://getcomposer.org).
Link to Packagist: https://packagist.org/packages/phptal/phptal

```sh
composer require phptal/phptal
```

Manual install
==============

Get the latest PHPTAL tarball from https://github.com/phptal/PHPTAL/releases

    tar zxvf PHPTAL-X.X.X.tar.gz
    mv PHPTAL-X.X.X/PHPTAL* /path/to/your/php/include/path/

Changelog
=========

[Please see the projects releases page](https://github.com/phptal/PHPTAL/releases)

Getting the latest development version
======================================

You can find the latest development version on github:

	https://github.com/phptal/PHPTAL

Addition development requirements (optional)
============================================

If you would like to generate the offical html/text handbook by calling
`make doc`, you will need to install the `xmlto` package. Please use
your operating systems package manager to install it.
