<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CmsAuth
{
    /** Cookie name for the "keep me signed in" persistent login. */
    public const REMEMBER_COOKIE = 'cms_remember';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('cms_authenticated')) {
            // "Keep me signed in" — re-establish the session from the long-lived cookie.
            if (self::validRemember($request)) {
                $request->session()->put('cms_authenticated', true);
            } else {
                return redirect()->route('admin.login');
            }
        }

        return $next($request);
    }

    /** Whether the request carries a valid persistent-login cookie. */
    public static function validRemember(Request $request): bool
    {
        $token = $request->cookie(self::REMEMBER_COOKIE);

        return is_string($token) && $token !== '' && hash_equals(self::rememberToken(), $token);
    }

    /**
     * Stable secret token for "remember me". Derived from the CMS password so it
     * auto-invalidates the moment the password changes, and is unforgeable
     * without the app key. Stored in an encrypted, http-only cookie.
     */
    public static function rememberToken(): string
    {
        return hash_hmac(
            'sha256',
            'oda-cms-remember-v1',
            (string) config('app.key').'|'.(string) config('site.cms_password')
        );
    }
}
