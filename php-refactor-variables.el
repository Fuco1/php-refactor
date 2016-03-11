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

(require 'json)

(defun php-refactor-get-variables (file)
  "Get variables."
  (with-temp-buffer
    (call-process
     "php" nil (current-buffer) nil
     "/home/matus/.emacs.d/projects/php-refactor/bin/parser" "variables" file)
    ;; (pop-to-buffer (current-buffer))
    (goto-char (point-min))
    (json-read)))

(php-refactor-get-variables "./php/RunParser.php")

(defun php-refactor-get-variable (file point)
  "Get variable at point."
  (with-temp-buffer
    (call-process
     "php" nil (current-buffer) nil
     "/home/matus/.emacs.d/projects/php-refactor/bin/parser" "variable" file (number-to-string point))
    ;; (pop-to-buffer (current-buffer))
    (goto-char (point-min))
    (json-read)))

(php-refactor-get-variable "./php/RunParser.php" 12)

(--tree-map (if (and (consp it)
                     (eq (car it) 'position))
                (cons 'position (1+ (cdr it)))
              it) '((id) (position . 7) (uses . [((expression . -1) (position . 7)) ((expression . -1) (position . 64))]) (function . 0) (argument . :json-false) (initialized . t) (name . "$parser")))

'((id) (position . 7) (uses . [((expression . -1) (position . 7)) ((expression . -1) (position . 64))]) (function . 0) (argument . :json-false) (initialized . t) (name . "$parser"))
((id) (position . 8) (uses . [((expression . -1) (position . 7)) ((expression . -1) (position . 64))]) (function . 0) (argument . :json-false) (initialized . t) (name . "$parser"))


(provide 'php-refactor-variables)
;;; php-refactor-variables.el ends here
