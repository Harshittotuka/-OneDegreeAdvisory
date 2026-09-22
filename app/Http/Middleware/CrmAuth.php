<?php

namespace App\Http\Middleware;

use App\Models\CrmRememberToken;
use App\Models\CrmUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CrmAuth
{
    /** Cookie name for the "keep me signed in" persistent login. */
    public const REMEMBER_COOKIE = 'crm_remember';

    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('crm_user_id');
        $user = $userId ? CrmUser::query()->whereKey($userId)->where('is_active', true)->first() : null;

        if (! $user) {
            $request->session()->forget('crm_user_id');

            // "Keep me signed in" — re-establish the session from the long-lived cookie.
            $user = self::rememberedUser($request);
            if (! $user) {
                return redirect()->route('crm.login');
            }

            $request->session()->regenerate();
            $request->session()->put('crm_user_id', $user->id);
        }

        $request->attributes->set('crm_user', $user);
        view()->share('crmUser', $user);

        return $next($request);
    }

    /**
     * The active account behind this request's persistent-login cookie, or null
     * where there is none, it has lapsed, or the account has been deactivated.
     */
    public static function rememberedUser(Request $request): ?CrmUser
    {
        return CrmRememberToken::resolve($request->cookie(self::REMEMBER_COOKIE));
    }
}
