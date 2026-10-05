<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The team guide — how the CRM and the journey planner work — opened from
 * the CRM sidebar and from the planner's Help page.
 *
 * The guide is one self-contained page kept at docs/crm-guide.html, so the
 * same file is the one edited and the one the team reads. It is for the
 * team: a referral partner signs in to the CRM too, but this is not theirs.
 */
class CrmGuideController extends Controller
{
    public function show(Request $request): Response
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        abort_if($user->isPartner(), 403);

        $path = base_path('docs/crm-guide.html');
        abort_unless(is_file($path), 404);

        return response((string) file_get_contents($path), 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Cache-Control' => 'private, no-cache',
        ]);
    }
}
