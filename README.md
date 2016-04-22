# php-refactor [![Build Status](https://travis-ci.org/Fuco1/php-refactor.svg?branch=master)](https://travis-ci.org/Fuco1/php-refactor)

Refactoring library for php

# Installation

The easiest way now is to `git clone` this repo.  Marmalade and MELPA are planned in the future.

This package depends on `dash` (emacs lisp library).  Get it from ELPA or https://github.com/magnars/dash.el .

This package depends on `multiple-cursors` (emacs lisp library).  Get it from ELPA or https://github.com/magnars/multiple-cursors.el .

This package depends on `f` (emacs lisp library).  Get it from ELPA or https://github.com/rejeep/f.el .

This package depends on `shut-up` (emacs lisp library).  Get it from ELPA or https://github.com/cask/shut-up .

To install php dependencies you will need [Composer](https://getcomposer.org/).  Once installed, run `composer install` in the repository to pull in the dependencies.

In emacs, add `(require 'php-refactor)` somewhere in your config.

# Available refactorings

* php-refactor-inline-variable
* php-refactor-rename-variable
