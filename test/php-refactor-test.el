(defun php-refactor-test--setup-buffer ()
  "Setup the test buffer."
  (php-mode)
  (delete-selection-mode 1)
  (transient-mark-mode 1))

(ert-deftest php-refactor-test-rename-variable ()
  (let ((initial "<?php
$foo = 1;
$bar = $f|oo + $a;
return $foo;"))
    (php-refactor-test-with-temp-buffer initial
        (php-refactor-test--setup-buffer)
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
    (php-refactor-test-with-temp-buffer initial
        (php-refactor-test--setup-buffer)
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
    (php-refactor-test-with-temp-buffer initial
        (php-refactor-test--setup-buffer)
      (php-refactor-rename-variable)
      (execute-kbd-macro "baz")
      (insert "|")
      (should (equal (buffer-string) "<?php
$foo = 1;
$bar = function ($baz|) { return $baz + $a; };
return $foo;")))))
