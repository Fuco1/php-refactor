# php-refactor [![Build Status](https://travis-ci.org/Fuco1/php-refactor.svg?branch=master)](https://travis-ci.org/Fuco1/php-refactor)

Refactoring library for php

# Installation

The easiest way now is to `git clone` this repo.  Marmalade and MELPA are planned in the future.

This package depends on `dash` (emacs lisp library).  Get it from ELPA or https://github.com/magnars/dash.el.

This package depends on https://github.com/nikic/PHP-Parser.  You will need to download it somewhere on your machine as well.

Then, in this (php-refactor) package, edit the file `php/config.php` and specify

* `PHP_PARSER_ROOT` - the path to the installation directory of `PHP-Parser`.
