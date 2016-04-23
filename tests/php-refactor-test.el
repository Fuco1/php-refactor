;; -*- lexical-binding: t -*-

(require 'php-refactor)
(require 'php-mode)

(defmacro php-refactor-test-with-temp-buffer (initial initform &rest forms)
  "Setup a new buffer, then run FORMS.

First, INITFORM are run in the newly created buffer.

Then INITIAL is inserted (it is expected to evaluate to string).
If INITIAL contains | put point there as the initial
position (the character is then removed).  If it contains M, put
mark there (the character is then removed).

Finally, FORMS are run."
  (declare (indent 2)
           (debug (form form body)))
  `(save-window-excursion
     (with-temp-buffer
       (set-input-method nil)
       ,initform
       (pop-to-buffer (current-buffer))
       (insert ,initial)
       (goto-char (point-min))
       (let ((case-fold-search nil))
         (when (search-forward "M" nil t)
           (delete-char -1)
           (set-mark (point))
           (activate-mark)))
       (goto-char (point-min))
       (let ((case-fold-search nil))
         (when (search-forward "|" nil t)
           (delete-char -1)))
       ,@forms)))

(defmacro php-refactor-test-with-php-buffer (initial &rest forms)
  "Setup test buffer for php."
  (declare (indent 1)
           (debug (form body)))
  `(php-refactor-test-with-temp-buffer ,initial
       (php-refactor-test--setup-buffer)
     ,@forms))

(defun php-refactor-test--setup-buffer ()
  "Setup the test buffer."
  (php-mode)
  (delete-selection-mode 1)
  (transient-mark-mode 1))

(describe "Rename variable"

  (defun php-refactor-test--rename-variable (initial name expected)
    (php-refactor-test-with-php-buffer initial
      (php-refactor-rename-variable)
      (execute-kbd-macro name)
      (insert "|")
      (expect (buffer-string) :to-equal expected)))

  (it "should rename variable"
    (php-refactor-test--rename-variable
     "<?php
$foo = 1;
$bar = $f|oo + $a;
return $foo;"
     "baz"
     "<?php
$baz = 1;
$bar = $baz| + $a;
return $baz;"))

  (it "should rename variable outside a nested function"
    (php-refactor-test--rename-variable
     "<?php
$f|oo = 1;
$bar = function ($foo) { return $foo + $a; };
return $foo;"
     "baz"
     "<?php
$baz| = 1;
$bar = function ($foo) { return $foo + $a; };
return $baz;"))

  (it "should rename variable inside a nested function"
    (php-refactor-test--rename-variable
     "<?php
$foo = 1;
$bar = function ($fo|o) { return $foo + $a; };
return $foo;"
     "baz"
     "<?php
$foo = 1;
$bar = function ($baz|) { return $baz + $a; };
return $foo;")))

(describe "Inline variable"

  (defun php-refactor-test--inline-variable (initial expected)
    "Test variable inlining."
    (php-refactor-test-with-php-buffer initial
      (php-refactor-inline-variable)
      (insert "|")
      (expect (buffer-string) :to-equal expected)))

  (it "should inline variable"
    (php-refactor-test--inline-variable
     "<?php
$a = foo(($b + 4) . 'asd');
foreach ($|a as $item) {
    break;
}
return $a;"
     "<?php
foreach (|foo(($b + 4) . 'asd') as $item) {
    break;
}
return foo(($b + 4) . 'asd');"))

  (it "should inline first occurrence only up to second assignment"
    (php-refactor-test--inline-variable
     "<?php
$a = 1  ;
$b = $|a;
$c = $a + $b;
$a = 2;
$b = $a;
$c = $a + $b;"
     "<?php
$b = |1;
$c = 1 + $b;
$a = 2;
$b = $a;
$c = $a + $b;"))

  (it "should inline occurrence after second assignment up to the end but not before the second assignment"
    (php-refactor-test--inline-variable
     "<?php
$a = 1;
$b = $a;
$c = $a + $b;
$a = 2;
$b = $|a;
$c = $a + $b;"
     "<?php
$a = 1;
$b = $a;
$c = $a + $b;
$b = |2;
$c = 2 + $b;"))

  (it "should inline variable only outside anonymous function"
    (php-refactor-test--inline-variable
     "<?php
$a = 1;
$b = $|a;
$c = function ($b) { return $a + $b };
return $a;"
     "<?php
$b = |1;
$c = function ($b) { return $a + $b };
return 1;"))

  (it "should inline variable only inside anonymous function"
    (php-refactor-test--inline-variable
     "<?php
$a = 1;
$b = $a;
$c = function ($b) {
    $a = 'foo';
    return $|a + $b
};
return $a;"
     "<?php
$a = 1;
$b = $a;
$c = function ($b) {
    return |'foo' + $b
};
return $a;")))

(describe "Select expression"

  (defun php-refactor-test--select-expression (initial expected)
    "Test variable selection."
    (shut-up
     (php-refactor-test-with-php-buffer initial
       (php-refactor--select-expression)
       (insert "|")
       (goto-char (mark))
       (insert "M")
       (expect (buffer-string) :to-equal expected))))

  (it "should select expression"
    (php-refactor-test--select-expression
     "<?php
$foobar = $a + $b->['t|ext'];"
     "<?php
$foobar = |$a + $b->['text']M;"))

  (it "should select nested expression"
    (php-refactor-test--select-expression
     "<?php
$f = function () {
    $a = '|b';
};"
     "<?php
$f = function () {
    $a = |'b'M;
};"))

  (it "should select outer expression if called twice"
    (php-refactor-test--select-expression
     "<?php
$f = function () {
    $a = |'b'M;
};"
     "<?php
$f = |function () {
    $a = 'b';
}M;")))

(describe "Extract variable"

  (defun php-refactor-test--extract-variable (initial name expected)
    "Test variable extraction."
    (php-refactor-test-with-php-buffer initial
      (php-refactor-extract-variable (region-beginning) (region-end))
      (execute-kbd-macro name)
      (insert "|")
      (expect (buffer-string) :to-equal expected)))

  (it "should extract variable"
    (php-refactor-test--extract-variable
     "<?php
$a = |'foo' . 'bar'M . 'baz';"
     "foobar"
     "<?php
$foobar = 'foo' . 'bar';
$a = $foobar| . 'baz';"))

  (it "should extract variable and indent"
    (php-refactor-test--extract-variable
     "<?php
function f() {
    $a = |'foo' . 'bar'M . 'baz';
}"
     "foobar"
     "<?php
function f() {
    $foobar = 'foo' . 'bar';
    $a = $foobar| . 'baz';
}"))

  (it "should extract variable from foreach collection expression"
    (php-refactor-test--extract-variable
     "<?php
foreach (|$a + $b->['text']M as $c) {
    continue;
}"
     "foobar"
     "<?php
$foobar = $a + $b->['text'];
foreach ($foobar| as $c) {
    continue;
}")))
