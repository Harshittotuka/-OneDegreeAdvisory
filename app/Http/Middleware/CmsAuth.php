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
            $role = self::rememberedRole($request);
            if ($role !== null) {
                $request->session()->put('cms_authenticated', true);
                $request->session()->put('cms_super_admin', $role === 'super');
            } else {
                return redirect()->route('admin.login');
            }
        }

        return $next($request);
    }

    /**
     * The role encoded in a valid persistent-login cookie ('super' | 'admin'),
     * or null if the request carries no valid "keep me signed in" cookie.
     */
    public static function rememberedRole(Request $request): ?string
    {
        $token = $request->cookie(self::REMEMBER_COOKIE);
        if (! is_string($token) || $token === '') {
            return null;
        }

        foreach (['super', 'admin'] as $role) {
            if (hash_equals(self::rememberToken($role), $token)) {
                return $role;
            }
        }

        return null;
    }

    /** Whether the request carries a valid persistent-login cookie (either role). */
    public static function validRemember(Request $request): bool
    {
        return self::rememberedRole($request) !== null;
    }

    /**
     * Stable secret token for "remember me", per role. Derived from that role's
     * password so it auto-invalidates the moment the password changes, and is
     * unforgeable without the app key. Stored in an encrypted, http-only cookie.
     */
    public static function rememberToken(string $role = 'admin'): string
    {
        if ($role === 'super') {
            return hash_hmac(
                'sha256',
                'oda-cms-remember-v1:super',
                (string) config('app.key').'|'.(string) config('site.super_admin_password')
            );
        }

        // 'admin' token preserved verbatim so existing remembered sessions stay valid.
        return hash_hmac(
            'sha256',
            'oda-cms-remember-v1',
            (string) config('app.key').'|'.(string) config('site.cms_password')
        );
    }
}
