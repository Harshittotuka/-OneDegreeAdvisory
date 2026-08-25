<?php

use App\Http\Controllers\Mcp\PageBuilderMcpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MCP endpoint
|--------------------------------------------------------------------------
|
| The Page Builder as an MCP server, so a claude.ai Project can author pages
| here. Registered outside the `web` group on purpose: an MCP client is not a
| browser, so it holds no session and no CSRF token.
|
| Streamable HTTP wants one endpoint answering both POST and GET. We answer
| POST with a single JSON object and decline the GET stream with 405, which the
| spec allows for a server that never pushes to the client.
|
*/

Route::post('/', [PageBuilderMcpController::class, 'handle'])->name('mcp.handle');
Route::get('/', [PageBuilderMcpController::class, 'notAllowed'])->name('mcp.stream');
Route::delete('/', [PageBuilderMcpController::class, 'notAllowed'])->name('mcp.delete');
