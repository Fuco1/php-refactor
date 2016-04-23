(ert-deftest php-refactor-test-rename-variable ()
  (let ((initial "<?php
$foo = 1;
$bar = $f|oo + $a;
return $foo;"))
    (php-refactor-test-with-php-buffer initial
      (php-refactor-rename-variable)
      (execute-kbd-macro "baz")
      (insert "|")
      (should (equal (buffer-string) "<?php
$baz = 1;
$bar = $baz| + $a;
return $baz;")))))

(ert-deftest php-refactor-test-rename-variable-outside-nested-function ()
  (let ((initial "<?php
$f|oo = 1;
$bar = function ($foo) { return $foo + $a; };
return $foo;"))
    (php-refactor-test-with-php-buffer initial
      (php-refactor-rename-variable)
      (execute-kbd-macro "baz")
      (insert "|")
      (should (equal (buffer-string) "<?php
$baz| = 1;
$bar = function ($foo) { return $foo + $a; };
return $baz;")))))

(ert-deftest php-refactor-test-rename-variable-inside-nested-function ()
  (let ((initial "<?php
$foo = 1;
$bar = function ($fo|o) { return $foo + $a; };
return $foo;"))
    (php-refactor-test-with-php-buffer initial
      (php-refactor-rename-variable)
      (execute-kbd-macro "baz")
      (insert "|")
      (should (equal (buffer-string) "<?php
$foo = 1;
$bar = function ($baz|) { return $baz + $a; };
return $foo;")))))

(defun php-refactor-test--inline-variable (initial expected)
  "Test variable inlining."
  (php-refactor-test-with-php-buffer initial
    (php-refactor-inline-variable)
    (insert "|")
    (should (equal (buffer-string) expected))))

(ert-deftest php-refactor-test-inline-variable ()
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

(ert-deftest php-refactor-test-inline-variable-multiple-assignment-first ()
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

(ert-deftest php-refactor-test-inline-variable-multiple-assignment-second ()
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

(ert-deftest php-refactor-test-inline-variable-nested-function-outside ()
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

(ert-deftest php-refactor-test-inline-variable-nested-function-inside ()
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
return $a;"))

(defun php-refactor-test--select-expression (initial expected)
  "Test variable selection."
  (php-refactor-test-with-php-buffer initial
    (php-refactor--select-expression)
    (insert "|")
    (goto-char (mark))
    (insert "M")
    (should (equal (buffer-string) expected))))

(ert-deftest php-refactor-select-expression ()
  (php-refactor-test--select-expression
   "<?php
$foobar = $a + $b->['t|ext'];"
   "<?php
$foobar = |$a + $b->['text']M;"))

(ert-deftest php-refactor-select-expression-nested ()
  (php-refactor-test--select-expression
   "<?php
$f = function () {
    $a = '|b';
};"
   "<?php
$f = function () {
    $a = |'b'M;
};"))

(ert-deftest php-refactor-select-expression-nested-repeated ()
  (php-refactor-test--select-expression
   "<?php
$f = function () {
    $a = |'b'M;
};"
   "<?php
$f = |function () {
    $a = 'b';
}M;"))
