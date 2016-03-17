;;; php-refactor-variables.el --- Refactoring library for php

;; Copyright (C) 2016 Matúš Goljer

;; Author: Matúš Goljer <matus.goljer@gmail.com>
;; Maintainer: Matúš Goljer <matus.goljer@gmail.com>
;; Version: 0.0.1
;; Created: 8th March 2016
;; Package-requires: ((dash "2.12.0"))
;; Keywords: languages, convenience

;; This program is free software; you can redistribute it and/or
;; modify it under the terms of the GNU General Public License
;; as published by the Free Software Foundation; either version 3
;; of the License, or (at your option) any later version.

;; This program is distributed in the hope that it will be useful,
;; but WITHOUT ANY WARRANTY; without even the implied warranty of
;; MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
;; GNU General Public License for more details.

;; You should have received a copy of the GNU General Public License
;; along with this program. If not, see <http://www.gnu.org/licenses/>.

;;; Commentary:

;;; Code:

(require 'thingatpt)

(require 'json)
(require 'multiple-cursors)

(defun php-refactor-get-variables (file)
  "Get variables."
  (with-temp-buffer
    (call-process
     "php" nil (current-buffer) nil
     "/home/matus/.emacs.d/projects/php-refactor/bin/parser" "variables" file)
    ;; (pop-to-buffer (current-buffer))
    (goto-char (point-min))
    (json-read)))

;; (php-refactor-get-variables "/home/matus/.emacs.d/projects/php-refactor/php/RunParserTest.php")

(defun php-refactor-get-variable (&optional file point)
  "Get variable at point."
  ;; TODO: add mechanism to copy TRAMP files locally and run analysis on those
  (setq file (or file (buffer-file-name)))
  (setq point (or point (point)))
  (with-temp-buffer
    (call-process
     "php" nil (current-buffer) nil
     "/home/matus/.emacs.d/projects/php-refactor/bin/parser" "variable" file (number-to-string point))
    ;; (pop-to-buffer (current-buffer))
    (goto-char (point-min))
    (json-read)))

;; (php-refactor-get-variable "./php/RunParser.php" 12)
;; (php-refactor-get-variable "./php/Parser.php" 712)

(defun php-refactor-select-variable (variable)
  "Select all occurrences of VARIABLE in current function."
  (-let* (((&alist 'uses uses 'name name) variable)
          ;; append to convert [] to ()
          (uses (append uses nil))
          (point (point))
          (len (length name))
          (current-start nil))
    (mapc (-lambda ((&alist 'beg beg))
            (if (and (<= beg point)
                     (< point (+ beg len)))
                (setq current-start beg)
              (goto-char (1+ beg))
              (set-mark (+ beg len))
              (mc/create-fake-cursor-at-point)))
          uses)
    (goto-char (1+ current-start))
    (push-mark (+ current-start len))
    (setq deactivate-mark nil)
    (activate-mark)
    (mc/maybe-multiple-cursors-mode)))

(defun php-refactor-rename-variable ()
  "Rename variable at point."
  (interactive)
  (php-refactor-select-variable (php-refactor-get-variable)))

(defun php-refactor-inline-variable ()
  "Inline variable definition."
  (interactive)
  (-let* (((&alist 'uses uses 'name name) (php-refactor-get-variable))
          (uses (append uses nil))
          (len (length name))
          ;; TODO: extract "current variable" finding logic
          (current-var (-find (-lambda ((&alist 'beg beg))
                                (and (>= (point) beg)
                                     (< (point) (+ beg len)))) uses))
          (inlined-var
           (-find (-lambda ((&alist 'beg beg 'assignedExpression expr))
                    (and (<= beg (cdr (assoc 'beg current-var)))
                         (consp expr)))
                  (reverse uses)))
          ((&alist 'assignedExpression (&alist 'text text
                                               'end inline-expr-end)
                   'beg inline-beg) inlined-var)
          (text (s-trim (s-chop-suffix ";" (s-trim text)))))
    (save-excursion
      (catch 'done
        (mapc (-lambda ((&alist 'beg beg))
                (goto-char beg)
                (cond
                 ((= (point) inline-beg)
                  (delete-region inline-beg inline-expr-end)
                  (delete-blank-lines))
                 ((> (point) inline-beg)
                  (delete-region (point) (+ beg len))
                  (insert text))
                 (t (throw 'done t))))
              (reverse uses))))))

;; (bind-key "C-x C-d v" 'php-refactor-rename-variable php-mode-map)

(provide 'php-refactor-variables)
;;; php-refactor-variables.el ends here
