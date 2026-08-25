<?php

return [
    /* Drafts-only is the safety model for every machine caller: the Page Builder
       can be driven from outside the studio to create and edit pages, but never
       to publish one, and never to touch a page that is already live. Publishing
       stays a human action in /admin/pages. Only set this false if you genuinely
       want unreviewed pages going live. */
    'drafts_only' => (bool) env('PAGE_API_DRAFTS_ONLY', true),

    /* The MCP server at /mcp — what a claude.ai Project connects to. It needs no
       server-wide credential: it authenticates with the expiring tokens issued
       in /admin/pages → Claude access, so the endpoint is inert while none are
       outstanding (every tool call simply refuses). Set PAGE_MCP_ENABLED=false
       to take it down entirely. */
    'mcp' => [
        'enabled' => (bool) env('PAGE_MCP_ENABLED', true),
        'rate_limit' => (int) env('PAGE_MCP_RATE_LIMIT', 120),
    ],
];
