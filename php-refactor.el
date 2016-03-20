;;; php-refactor.el --- Refactoring library for php

;; Copyright (C) 2016 Matúš Goljer

;; Author: Matúš Goljer <matus.goljer@gmail.com>
;; Maintainer: Matúš Goljer <matus.goljer@gmail.com>
;; Version: 0.0.1
;; Created: 8th March 2016
;; Package-requires: ((dash "2.12.0") (multiple-cursors "1.2.2") (f "0.17.0") (shut-up "0.3"))
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

(require 'f)
(require 'dash)
(require 'multiple-cursors)
(require 'shut-up)

(defvar php-refactor--parser (concat (f-dirname (f-this-file)) "/bin/parser")
  "Path to php parser executable.")

(defun php-refactor--run-parser (command &rest args)
  "Run php parser with COMMAND.

ARGS are arguments for the parser for the specified command."
  (let ((tmp-file (make-temp-file "php-refactor")))
    (unwind-protect
        (progn
          (shut-up-write-region (point-min) (point-max) tmp-file)
          (with-temp-buffer
            (apply
             'call-process
             "php" nil (current-buffer) nil
             php-refactor--parser command tmp-file args)
            ;; (pop-to-buffer (current-buffer))
            (goto-char (point-min))
            (json-read)))
      (delete-file tmp-file))))

(defun php-refactor--get-variables ()
  "Get variables."
  (php-refactor--run-parser "variables"))

(defun php-refactor--get-variables-at-point (&optional point)
  "Get variables at POINT."
  (setq point (or point (point)))
  (php-refactor--run-parser "variables-at-point" (number-to-string point)))

(defun php-refactor--get-variable (&optional point)
  "Get variable at POINT."
  (setq point (or point (point)))
  (php-refactor--run-parser "variable" (number-to-string point)))

(defun php-refactor--select-variable (variable)
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
    (push-mark (+ current-start len) t)
    (setq deactivate-mark nil)
    (activate-mark)
    (mc/maybe-multiple-cursors-mode)))

(defun php-refactor-rename-variable ()
  "Rename variable at point."
  (interactive)
  (php-refactor--select-variable (php-refactor--get-variable)))

(defun php-refactor-inline-variable ()
  "Inline variable definition."
  (interactive)
  (-let* (((&alist 'uses uses 'name name) (php-refactor--get-variable))
          (uses (append uses nil))
          (len (length name))
          ;; TODO: extract "current variable" finding logic
          (current-var (-find (-lambda ((&alist 'beg beg))
                                (and (>= (point) beg)
                                     (< (point) (+ beg len)))) uses))
          ;; first usage to which an expression is assigned before the
          ;; current var
          (inlined-var
           (-find (-lambda ((&alist 'beg beg 'assignedExpression expr))
                    (and (<= beg (cdr (assoc 'beg current-var)))
                         (consp expr)))
                  (reverse uses)))
          ((&alist 'assignedExpression (&alist 'text text
                                               'end inline-expr-end)
                   'beg inline-beg) inlined-var)
          ;; first usage to which an expression is assigned after the
          ;; current var
          ((&alist 'beg limit-beg)
           (-find (-lambda ((&alist 'beg beg 'assignedExpression expr))
                    (and (> beg (cdr (assoc 'beg current-var)))
                         (consp expr)))
                  uses))
          (text (s-trim (s-chop-suffix ";" (s-trim text)))))
    (save-excursion
      (mapc (-lambda ((&alist 'beg beg))
              (goto-char beg)
              (cond
               ((= (point) inline-beg)
                (delete-region inline-beg inline-expr-end)
                (delete-blank-lines))
               ((and (> (point) inline-beg) (< (point) (or limit-beg (point-max))))
                (delete-region (point) (+ beg len))
                (insert text))))
            (reverse uses)))))

;; (bind-key "C-x C-d v" 'php-refactor-rename-variable php-mode-map)

(provide 'php-refactor)
;;; php-refactor.el ends here
