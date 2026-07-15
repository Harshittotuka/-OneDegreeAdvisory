<?php

namespace App\Http\Middleware;

use App\Models\CrmUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CrmAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('crm_user_id');
        $user = $userId ? CrmUser::query()->whereKey($userId)->where('is_active', true)->first() : null;

        if (! $user) {
            $request->session()->forget('crm_user_id');

            return redirect()->route('crm.login');
        }

        $request->attributes->set('crm_user', $user);
        view()->share('crmUser', $user);

        return $next($request);
    }
}
