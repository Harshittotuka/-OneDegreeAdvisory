<?php

namespace App\Http\Middleware;

use App\Support\CmsCrmBackupManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleCmsCrmBackup
{
    public function __construct(private CmsCrmBackupManager $backups) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);

            $routeName = (string) optional($request->route())->getName();
            if ($response->getStatusCode() < 400
                && $request->isMethodSafe() === false
                && str_starts_with($routeName, 'admin.')
                && str_ends_with($routeName, '.upload')
            ) {
                $this->backups->markDirty('cms-upload');
            }

            return $response;
        } finally {
            $this->backups->schedule();
        }
    }
}
