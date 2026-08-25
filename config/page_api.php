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

        /* Hosts accepted in an Origin header. The Streamable HTTP spec wants an
           Origin check to blunt DNS rebinding, but the check must not be tied to
           one vendor: a server-to-server caller sends no Origin at all, and a
           browser-based client sends its own. Listed here so adding a client is
           a config change rather than a code change. The site's own host is
           always allowed, and the token remains the real access control. */
        'allowed_origins' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env(
                'PAGE_MCP_ALLOWED_ORIGINS',
                'claude.ai,www.claude.ai,claude.com,chatgpt.com,www.chatgpt.com,chat.openai.com,openai.com',
            )),
        ))),
    ],
];
