(ert-deftest php-refactor-test-rename-variable ()
  (let ((initial "<?php
$foo = 1;
$bar = $f|oo + $a;
return $foo;"))
    (php-refactor-test-with-temp-buffer initial
        (progn
          (php-mode)
          (delete-selection-mode 1)
          (transient-mark-mode 1))
      (php-refactor-rename-variable)
      ;; (call-interactively 'kill-region)
      (execute-kbd-macro "baz")
      (insert "|")
      (should (equal (buffer-string) "<?php
$baz = 1;
$bar = $baz| + $a;
return $baz;")))))
