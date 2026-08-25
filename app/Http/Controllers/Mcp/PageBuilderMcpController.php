<?php

namespace App\Http\Controllers\Mcp;

use App\Exceptions\PageBuilderException;
use App\Http\Controllers\Controller;
use App\Support\PageBuilderTokens;
use App\Support\PageBuilderWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * MCP server for the Page Builder, so a claude.ai Project (or Claude Desktop,
 * or Claude Code) can author pages on this site.
 *
 * Streamable HTTP, stateless: one endpoint, POST carries JSON-RPC, and a
 * request is answered with a single `application/json` object rather than an SSE
 * stream. The spec permits this and it keeps the server free of session state,
 * which matters on shared hosting where nothing long-lived survives.
 *
 * The one rule that is easy to get wrong: **never answer 401**. A 401 makes
 * Claude start OAuth discovery, and since this site has no authorization server
 * the connector then fails with "couldn't reach the MCP server" instead of
 * telling the user their token is wrong. So `initialize` and `tools/list` are
 * open, and the token is checked inside `tools/call`, where a failure comes back
 * as a readable tool error that Claude can relay.
 */
class PageBuilderMcpController extends Controller
{
    /** Newest first — the first entry is what we advertise when we cannot agree. */
    private const PROTOCOL_VERSIONS = ['2025-11-25', '2025-06-18', '2025-03-26'];

    public function __construct(
        private PageBuilderWriter $writer,
        private PageBuilderTokens $tokens,
    ) {}

    /* ───────────────────────── Transport ───────────────────────── */

    public function handle(Request $request): JsonResponse|Response
    {
        if (! config('page_api.mcp.enabled', true)) {
            return $this->rpcError(null, -32601, 'The Page Builder MCP server is switched off on this site.', 404);
        }

        // Streamable HTTP requires an Origin check to blunt DNS rebinding.
        // Server-to-server callers send no Origin at all, which is fine; a
        // browser-supplied one has to be a host we recognise.
        $origin = (string) $request->header('Origin', '');
        if ($origin !== '' && ! $this->originAllowed($origin)) {
            return $this->rpcError(null, -32600, 'Origin not allowed.', 403);
        }

        $version = (string) $request->header('MCP-Protocol-Version', '');
        if ($version !== '' && ! in_array($version, self::PROTOCOL_VERSIONS, true)) {
            return $this->rpcError(null, -32600, 'Unsupported MCP-Protocol-Version: '.$version, 400);
        }

        $body = $request->json()->all();
        if (! is_array($body) || $body === []) {
            return $this->rpcError(null, -32700, 'Expected a JSON-RPC message body.', 400);
        }

        // A batch is a plain array of messages.
        if (array_is_list($body)) {
            $results = [];
            foreach ($body as $message) {
                if (! is_array($message)) {
                    continue;
                }
                $result = $this->dispatch($message, $request);
                if ($result !== null) {
                    $results[] = $result;
                }
            }

            return $results === []
                ? response()->noContent(202)
                : response()->json($results);
        }

        $result = $this->dispatch($body, $request);

        // A notification or response carries no id and gets no body back.
        return $result === null
            ? response()->noContent(202)
            : response()->json($result);
    }

    /** No SSE stream is offered, which the spec expects to be signalled as 405. */
    public function notAllowed(): JsonResponse
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'error' => ['code' => -32601, 'message' => 'This MCP endpoint accepts POST only; it does not offer an SSE stream.'],
        ], 405);
    }

    /* ───────────────────────── JSON-RPC ───────────────────────── */

    /** @return array<string, mixed>|null  null for a notification (no reply) */
    private function dispatch(array $message, Request $request): ?array
    {
        $id = $message['id'] ?? null;
        $method = (string) ($message['method'] ?? '');
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];

        // No method + an id means this is a response to us; nothing to do.
        if ($method === '') {
            return null;
        }

        // Notifications (no id) are acknowledged with 202 and no body.
        if ($id === null) {
            return null;
        }

        try {
            return match ($method) {
                'initialize' => $this->ok($id, $this->initializeResult($params)),
                'ping' => $this->ok($id, new \stdClass),
                'tools/list' => $this->ok($id, ['tools' => $this->toolDefinitions()]),
                'tools/call' => $this->ok($id, $this->callTool($params, $request)),
                // Declared capabilities are tools only, but some clients probe
                // these anyway; an empty list is friendlier than an error.
                'resources/list' => $this->ok($id, ['resources' => []]),
                'resources/templates/list' => $this->ok($id, ['resourceTemplates' => []]),
                'prompts/list' => $this->ok($id, ['prompts' => []]),
                default => $this->err($id, -32601, 'Unknown method: '.$method),
            };
        } catch (Throwable $e) {
            report($e);

            return $this->err($id, -32603, 'Internal error handling '.$method.'.');
        }
    }

    private function initializeResult(array $params): array
    {
        $asked = (string) ($params['protocolVersion'] ?? '');
        $version = in_array($asked, self::PROTOCOL_VERSIONS, true) ? $asked : self::PROTOCOL_VERSIONS[0];

        return [
            'protocolVersion' => $version,
            'capabilities' => ['tools' => new \stdClass],
            'serverInfo' => [
                'name' => 'onedegree-page-builder',
                'title' => config('site.name', 'One Degree Advisory').' — Page Builder',
                'version' => '1.0.0',
            ],
            'instructions' => $this->instructions(),
        ];
    }

    /** Guidance the client shows the model once, at connection time. */
    private function instructions(): string
    {
        return <<<'TXT'
        This server builds pages on the One Degree Advisory website.

        Every tool needs an access token. If the connector was not configured
        with an Authorization header, pass the token issued in /admin/pages as
        the `token` argument on each call.

        Before composing a page, call list_block_types, then get_block_schema
        for the specific types you intend to use. Only the field keys that
        schema lists are stored — anything else is silently dropped, so check
        the layout you get back to confirm what was actually saved.

        Pages are always created hidden. You cannot publish, and you cannot
        edit a page that is already live — duplicate it into a draft instead.
        After writing, give the human the preview_url and tell them to publish
        from /admin/pages once they are happy with it.
        TXT;
    }

    /* ───────────────────────── Tools ───────────────────────── */

    private function toolDefinitions(): array
    {
        $token = [
            'token' => [
                'type' => 'string',
                'description' => 'Page Builder access token from /admin/pages. Omit only if the connector already sends it as an Authorization header.',
            ],
        ];

        $pageFields = [
            'title' => ['type' => 'string', 'description' => 'Page title, up to 160 characters. Also seeds the URL slug.'],
            'path' => ['type' => 'string', 'description' => 'Public URL path, e.g. "/study-in-finland". Defaults to /briefs/{slug}. Reserved prefixes (/admin, /api, /storage, /login, /logout) and paths already in use are ignored.'],
            'page_title' => ['type' => 'string', 'description' => 'SEO <title>. Capped at 90 characters. Defaults to "{title} | One Degree Advisory".'],
            'meta_description' => ['type' => 'string', 'description' => 'SEO meta description. Capped at 170 characters.'],
            'layout' => [
                'type' => 'array',
                'description' => 'Page body: an array of rows. Each row is {width: "" or "full", cols: [{span: 1-12, blocks: [{type, data}]}]}. Column spans in a row should sum to 12. Call get_block_schema for the valid field keys of each block type.',
                'items' => ['type' => 'object'],
            ],
        ];

        return [
            [
                'name' => 'list_pages',
                'title' => 'List pages',
                'description' => 'Every page on the site with its slug, title, URL path, whether it is live, and whether this connector may edit it.',
                'inputSchema' => ['type' => 'object', 'properties' => $token],
            ],
            [
                'name' => 'get_page',
                'title' => 'Get a page',
                'description' => 'One page in full, including its complete block layout and SEO fields.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $token + ['slug' => ['type' => 'string', 'description' => 'The page slug, from list_pages.']],
                    'required' => ['slug'],
                ],
            ],
            [
                'name' => 'list_block_types',
                'title' => 'List block types',
                'description' => 'The names and one-line descriptions of every available block type. Call this first when composing a page, then get_block_schema for the ones you want.',
                'inputSchema' => ['type' => 'object', 'properties' => $token],
            ],
            [
                'name' => 'get_block_schema',
                'title' => 'Get block schema',
                'description' => 'The exact field keys, types and options for the named block types, plus a blank example of each. Only these field keys are stored.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $token + [
                        'types' => [
                            'type' => 'array',
                            'description' => 'Block type names from list_block_types, e.g. ["hero", "brief_cards"].',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                    'required' => ['types'],
                ],
            ],
            [
                'name' => 'create_page',
                'title' => 'Create a draft page',
                'description' => 'Create a new page. It is always hidden (visible: false) and 404s for the public until a human publishes it in /admin/pages.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $token + $pageFields,
                    'required' => ['title'],
                ],
            ],
            [
                'name' => 'update_page',
                'title' => 'Update a draft page',
                'description' => 'Change a draft: send only the fields you want to alter. Replaces the whole layout if you send one. Refuses a page that is already live — duplicate it instead.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $token + ['slug' => ['type' => 'string', 'description' => 'The draft to change.']] + $pageFields,
                    'required' => ['slug'],
                ],
            ],
            [
                'name' => 'append_rows',
                'title' => 'Append rows to a draft',
                'description' => 'Add rows to the end of a draft\'s existing layout, so a long page can be built over several turns without resending what is already there.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $token + [
                        'slug' => ['type' => 'string', 'description' => 'The draft to extend.'],
                        'rows' => ['type' => 'array', 'description' => 'Rows to append, same shape as layout.', 'items' => ['type' => 'object']],
                    ],
                    'required' => ['slug', 'rows'],
                ],
            ],
            [
                'name' => 'duplicate_page',
                'title' => 'Duplicate a page',
                'description' => 'Copy any page — live or draft — into a new hidden draft. This is how you revise a page that is already published.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $token + ['slug' => ['type' => 'string', 'description' => 'The page to copy.']],
                    'required' => ['slug'],
                ],
            ],
            [
                'name' => 'delete_draft',
                'title' => 'Delete a draft',
                'description' => 'Permanently delete a hidden draft. Refuses any page that is live.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => $token + ['slug' => ['type' => 'string', 'description' => 'The draft to delete.']],
                    'required' => ['slug'],
                ],
            ],
        ];
    }

    private function callTool(array $params, Request $request): array
    {
        $name = (string) ($params['name'] ?? '');
        $args = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        $token = $this->tokens->verify($this->presentedToken($request, $args));
        if ($token === null) {
            return $this->toolError($this->authHelp());
        }

        $actor = 'mcp:'.$token->id;

        try {
            return match ($name) {
                'list_pages' => $this->toolOk(['pages' => $this->writer->list()]),

                'get_page' => $this->toolOk(['page' => $this->writer->find($this->str($args, 'slug'))]),

                'list_block_types' => $this->toolOk([
                    'block_types' => array_map(
                        fn (string $key, array $def) => [
                            'type' => $key,
                            'label' => $def['label'] ?? $key,
                            'description' => $def['description'] ?? null,
                        ],
                        array_keys($this->writer->schema()),
                        array_values($this->writer->schema()),
                    ),
                    'next_step' => 'Call get_block_schema with the types you want to use.',
                ]),

                'get_block_schema' => $this->toolOk($this->blockSchema($args)),

                'create_page' => $this->toolOk([
                    'page' => $this->writer->create($args, $actor),
                    'next_step' => 'Give the human the preview_url. They publish it from /admin/pages.',
                ]),

                'update_page' => $this->toolOk([
                    'page' => $this->writer->update($this->str($args, 'slug'), $args, $actor),
                ]),

                'append_rows' => $this->toolOk([
                    'page' => $this->writer->appendRows(
                        $this->str($args, 'slug'),
                        is_array($args['rows'] ?? null) ? $args['rows'] : [],
                        $actor,
                    ),
                ]),

                'duplicate_page' => $this->toolOk([
                    'page' => $this->writer->duplicate($this->str($args, 'slug'), $actor),
                ]),

                'delete_draft' => $this->deleteDraft($this->str($args, 'slug'), $actor),

                default => $this->toolError('Unknown tool: '.$name),
            };
        } catch (PageBuilderException $e) {
            return $this->toolError($e->getMessage(), ['error' => $e->errorCode]);
        } catch (ValidationException $e) {
            return $this->toolError(
                'That input was rejected: '.implode(' ', array_merge(...array_values($e->errors()))),
                ['errors' => $e->errors()],
            );
        }
    }

    private function blockSchema(array $args): array
    {
        $wanted = array_values(array_filter(array_map(
            fn ($t) => is_string($t) ? trim($t) : null,
            is_array($args['types'] ?? null) ? $args['types'] : [],
        )));

        if ($wanted === []) {
            return [
                'block_types' => [],
                'note' => 'Pass one or more type names in `types`. Use list_block_types to see what exists.',
            ];
        }

        $out = [];
        $unknown = [];
        foreach ($wanted as $type) {
            $schema = $this->writer->schema($type);
            if ($schema === []) {
                $unknown[] = $type;

                continue;
            }
            $out[$type] = $schema[$type];
        }

        $result = ['block_types' => $out];
        if ($unknown !== []) {
            $result['unknown_types'] = $unknown;
            $result['available_types'] = $this->writer->typeNames();
        }

        return $result;
    }

    private function deleteDraft(string $slug, string $actor): array
    {
        $this->writer->deleteDraft($slug, $actor);

        return $this->toolOk(['deleted' => $slug]);
    }

    /* ───────────────────────── Auth ───────────────────────── */

    /**
     * The token, from a request header if the connector was configured with one,
     * otherwise from the tool arguments. The argument form exists because
     * claude.ai's request-header field is still a gradual beta rollout — with an
     * authless connector, the token lives in the Project instructions and Claude
     * passes it on each call.
     */
    private function presentedToken(Request $request, array $args): ?string
    {
        $auth = trim((string) $request->header('Authorization', ''));
        if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return trim($m[1]);
        }
        if ($auth !== '' && ! str_contains($auth, ' ')) {
            return $auth; // token pasted without the "Bearer " scheme
        }

        foreach (['X-Api-Key', 'X-Auth-Token'] as $header) {
            $value = trim((string) $request->header($header, ''));
            if ($value !== '') {
                return $value;
            }
        }

        $arg = $args['token'] ?? null;

        return is_string($arg) && trim($arg) !== '' ? trim($arg) : null;
    }

    private function authHelp(): string
    {
        return $this->tokens->anyUsable()
            ? 'That access token is missing, expired or revoked. Ask the site owner for a current token from /admin/pages, then pass it as the `token` argument (or have it set as the connector\'s Authorization header).'
            : 'This site has no active Page Builder token. The site owner needs to generate one in /admin/pages first.';
    }

    private function originAllowed(string $origin): bool
    {
        $host = parse_url($origin, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $allowed = array_filter([
            'claude.ai',
            'www.claude.ai',
            'claude.com',
            config('site.canonical_host'),
            parse_url((string) config('app.url'), PHP_URL_HOST),
        ]);

        return in_array(strtolower($host), array_map('strtolower', $allowed), true);
    }

    /* ───────────────────────── Shapes ───────────────────────── */

    private function str(array $args, string $key): string
    {
        return trim((string) ($args[$key] ?? ''));
    }

    /** A successful tool result: readable JSON text plus the structured form. */
    private function toolOk(array $payload): array
    {
        return [
            'content' => [['type' => 'text', 'text' => $this->encode($payload)]],
            'structuredContent' => $payload,
            'isError' => false,
        ];
    }

    /**
     * A tool-level failure. Reported as a successful JSON-RPC response with
     * isError set, so the model sees the reason and can act on it, rather than
     * the client swallowing a protocol error.
     */
    private function toolError(string $message, array $extra = []): array
    {
        return [
            'content' => [['type' => 'text', 'text' => $this->encode(['error' => true, 'message' => $message] + $extra)]],
            'isError' => true,
        ];
    }

    private function encode(array $payload): string
    {
        return (string) json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function ok(mixed $id, mixed $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function err(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }

    private function rpcError(mixed $id, int $code, string $message, int $status): JsonResponse
    {
        return response()->json($this->err($id, $code, $message), $status);
    }
}
