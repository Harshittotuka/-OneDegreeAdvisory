<?php

namespace App\Http\Middleware;

use App\Models\CrmStudentAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The student portal's sign-in check. Separate from the CRM's: the session key
 * is its own, so a counsellor testing the student view in the same browser is
 * not signed out of the CRM, and the reverse.
 *
 * A student still holding the temporary password their counsellor issued is
 * sent to choose their own before anything else opens.
 */
class StudentAuth
{
    public const SESSION_KEY = 'student_account_id';

    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->session()->get(self::SESSION_KEY);
        $account = $id ? CrmStudentAccount::query()->with('lead')->find($id) : null;

        if (! $account || ! $account->canSignIn()) {
            $request->session()->forget(self::SESSION_KEY);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session has ended. Sign in again.'], 401);
            }

            return redirect()->route('student.login');
        }

        if ($account->must_change_password && ! $request->routeIs('student.password', 'student.password.update', 'student.logout')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Choose your own password first.'], 403);
            }

            return redirect()->route('student.password');
        }

        $request->attributes->set('student_account', $account);

        return $next($request);
    }
}
