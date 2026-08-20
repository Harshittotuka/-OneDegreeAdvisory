<?php

/*
 * Partial override of the framework's validation messages. Laravel's FileLoader
 * reads its own lang directory first and then this one, merging recursively, so
 * only the keys named here change and every other message keeps its default.
 *
 * `email` is overridden because a malformed address is the same dead end for a
 * visitor as a placeholder one (see the `real_email` rule in AppServiceProvider):
 * both now point them at the admissions mailbox instead of just saying "invalid".
 */
return [
    'email' => config('site.forms.email_help'),
];
