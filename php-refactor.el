;;; php-refactor.el --- Refactoring library for php

;; Copyright (C) 2014 Matúš Goljer <matus.goljer@gmail.com>

;; Author: Matúš Goljer <matus.goljer@gmail.com>
;; Maintainer: Matúš Goljer <matus.goljer@gmail.com>
;; Version: 0.0.1
;; Created: 6th November 2014
;; Package-requires: ((dash "2.9.0") (ov "1.0"))
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
(require 'dash)
(require 'ov)

;; TODO: get rid of absolute path
(defun php-refactor-get-ast (file)
  "Get the abstract syntax tree of FILE."
  (with-temp-buffer
    (call-process "php" nil (current-buffer) nil "/home/matus/.emacs.d/projects/php-refactor/php/php-parse.php" file)
    (goto-char (point-min))
    (read (current-buffer))))


;; Filters
(defun php-refactor-function-filter (path)
  (-let [((head)) path]
    (memq head '(Stmt_Function Stmt_ClassMethod))))


;; Pure functions operating on AST
(defun php-refactor-get-variables (ast &optional pred)
  "Return all variables in AST.

If optional argument PRED is non-nil, use it as a filter for
`-tree-visit'."
  (let (vars)
    (dash--tree-visit
     (or pred 'ignore)
     (-lambda ((fst snd))
       (when (eq fst 'Expr_Variable)
         (if (eq (car snd) 'Expr_Variable)
             (push snd vars)
           (push (cdr snd) vars))))
     ast)
    (nreverse vars)))

(defun php-refactor-get-function (ast)
  "Return all functions defined in AST."
  (let (funs)
    (dash--tree-visit
     (-lambda ((x)) (--when-let (memq (car x) '(Stmt_Function Stmt_ClassMethod))
                      (push x funs)
                      it))
     'identity
     ast)
    (nreverse funs)))


;; Functions operating on current buffer and AST

(defun php-refactor-get-current-function (ast)
  "Return function or method under point.

AST is the abstract syntax tree representing the current buffer's
code.  See `php-refactor-get-ast'."
  (catch 'has-fun
    (dash--tree-visit
     (-lambda ((x)) (--when-let (memq (car x) '(Stmt_Function Stmt_ClassMethod))
                      (when (-let [(&alist :beg s :end e) x]
                              (and (<= s (point)) (< (point) e)))
                        (throw 'has-fun x))
                      it))
     'identity
     ast)))

;; TODO: rewrite this crap using the above "primitives"
(defun my-hilight-php-function ()
  ;; TODO: abstract this visitor pattern
  (let (functions)
    (--tree-map-nodes
     (and (listp it)
          (eq (car it) 'Stmt_Function))
     (push (if (symbolp (cadr it)) (cdr it) it) functions) test-parse)
    (setq functions (nreverse functions))
    (ov-clear 'funhi)
    (-let [(&alist :beg s :end e) (-first (-lambda ((&alist :beg s :end e)) (and (<= s (point)) (< (point) e))) functions)]
      (when s (ov s e 'funhi t 'face 'highlight)))))

(defun my-hilight-php-variables ()
  ;; TODO: abstract this visitor pattern
  (let (functions)
    (--tree-map-nodes
     (and (listp it)
          (eq (car it) 'Stmt_Function))
     (push (if (symbolp (cadr it)) (cdr it) it) functions) test-parse)
    (setq functions (nreverse functions))
    (ov-clear 'varhi)
    (-let [fun (-first (-lambda ((&alist :beg s :end e)) (and (<= s (point)) (< (point) e))) functions)]
      (if fun
          (let (vars)
            (--tree-map-nodes
             (or (and (listp it)
                      (eq (car it) 'Expr_Variable))
                 (and (listp it)
                      (listp (cdr it))
                      (eq (cadr it) 'Expr_Variable)))
             (push (if (symbolp (cadr it)) (cdr it) it) vars) fun)
            (setq vars (nreverse vars))
            (-let* (((&alist :name name) (-first (-lambda ((&alist :beg s :end e)) (and (<= s (point)) (< (point) e))) vars))
                    (vars-to-hl (-filter (-lambda ((&alist :name n)) (equal n name)) vars)))
              (-map (-lambda ((&alist :beg s :end e)) (ov s e 'varhi t 'face 'font-lock-warning-face)) vars-to-hl)))
        (let (vars)
          (--tree-map-nodes
           (and (or (and (listp it)
                         (eq (car it) 'Expr_Variable))
                    (and (listp it)
                         (listp (cdr it))
                         (eq (cadr it) 'Expr_Variable)))
                (-let [(&alist :beg beg) it] (message "%s" beg) (-none? (-lambda ((&alist :beg s :end e)) (and (<= s beg) (< beg e))) functions)))
           (push (if (symbolp (cadr it)) (cdr it) it) vars) test-parse)
          (setq vars (nreverse vars))
          (-let* (((&alist :name name) (-first (-lambda ((&alist :beg s :end e)) (and (<= s (point)) (< (point) e))) vars))
                  (vars-to-hl (-filter (-lambda ((&alist :name n)) (equal n name)) vars)))
            (-map (-lambda ((&alist :beg s :end e)) (ov s e 'varhi t 'face 'font-lock-warning-face)) vars-to-hl)))))))

(provide 'php-refactor)
;;; php-refactor.el ends here
