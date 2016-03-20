;;; test-helper.el --- Helper for tests.

;; Copyright (C) 2016 Matus Goljer

;; Author: Matus Goljer <matus.goljer@gmail.com>
;; Maintainer: Matus Goljer <matus.goljer@gmail.com>
;; Created: 19th March 2016

;;; Commentary:

;; Grab bag of utilities for running ert-refactor tests.

;;; Code:

(require 'ert)
(require 'dash)
(require 'f)
(require 'php-mode)

(let ((dir (f-parent (f-dirname (f-this-file)))))
  (add-to-list 'load-path dir))
(require 'php-refactor)

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
     (let ((case-fold-search nil))
       (with-temp-buffer
         (set-input-method nil)
         ,initform
         (pop-to-buffer (current-buffer))
         (insert ,initial)
         (goto-char (point-min))
         (when (search-forward "M" nil t)
           (delete-char -1)
           (set-mark (point))
           (activate-mark))
         (goto-char (point-min))
         (when (search-forward "|" nil t)
           (delete-char -1))
         ,@forms))))

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

(provide 'test-helper)
;;; test-helper.el ends here
