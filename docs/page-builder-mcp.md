# Page Builder MCP connector

Lets a **claude.ai Project** build pages on this site. You generate a token in
`/admin/pages`, paste it into Claude once, and then ask for pages in plain
English. Claude writes hidden drafts; you publish.

No Anthropic API key is involved — this rides your normal Claude subscription.

## Setting it up

### 1. Generate a token

`/admin/pages` → **Claude access** (super-admin only) → name it, choose a
lifetime (7 / 15 / 30 / 90 days, default 15), **Generate**.

The token is shown **once**. Only a SHA-256 hash is stored, so it cannot be
recovered later — if you lose it, revoke it and issue another.

### 2. Add the connector in claude.ai

**Settings → Connectors → Add custom connector**, and enter:

```
https://onedegreeadvisory.com/mcp
```

### 3. Give Claude the token

Two paths, because claude.ai's header field is still a gradual beta rollout.
The server accepts either, so use whichever your account shows.

**A. Request headers** (tidier, if the field is there)

In the same Add-connector dialog, open **Request headers**:

| Field | Value |
| --- | --- |
| Header | `Authorization` |
| Value | `Bearer odp_pb_…` |

Claude sends the value verbatim and adds no scheme of its own, so the word
`Bearer` and the space after it must be part of what you type.

**B. Project instructions** (works today, no beta needed)

Add the connector with the URL alone, then put this in your Claude Project's
instructions:

```
Page Builder token: odp_pb_…
Pass it as the `token` argument on every Page Builder tool call.
```

Every tool takes an optional `token` argument for exactly this case. Note the
trade-off: the token sits in the project's instructions, so anyone with access to
that project can read it. Keep the lifetime short and revoke it when the work is
done.

### 4. Ask for a page

> "Create a page about studying in Ireland at /study-in-ireland — a hero, two
> callouts side by side for intakes and stay-back, a costs table, and a CTA.
> Write the meta description too."

Claude reads the block schema, composes the layout, and hands you a preview
link. You open it (signed in at `/admin`), then publish from `/admin/pages`.

## What Claude can and cannot do

| Can | Cannot |
| --- | --- |
| Create pages (always hidden) | Publish anything |
| Edit any draft | Edit a page that is already live |
| Duplicate a live page into a draft | Delete a live page |
| Set the URL path, `page_title`, meta description | Add a payment section |
| Use all 27 block types, with tags inside blocks | Reach any other part of the site |

Those limits are enforced in `App\Support\PageBuilderWriter`, which shares its
sanitizer and SEO helpers with the visual studio — so the connector cannot store
anything the studio itself would refuse.

## Tools

| Tool | Does |
| --- | --- |
| `list_pages` | Every page: slug, title, path, live or not, whether it is editable |
| `get_page` | One page in full, including its layout |
| `list_block_types` | The 27 block types with one-line descriptions |
| `get_block_schema` | Exact field keys, types and options for named types |
| `create_page` | New hidden draft |
| `update_page` | Change a draft; send only the fields you want altered |
| `append_rows` | Add rows to a draft without resending the existing layout |
| `duplicate_page` | Copy any page into a hidden draft |
| `delete_draft` | Delete a hidden draft |

`payment` is absent from `list_block_types` and refused by the writers, so the
studio's OTP authorization flow stays the only route to a live payment gateway.

## Layout shape

Useful when reading what Claude produced, or writing a layout by hand.

```json
{
  "layout": [
    {
      "width": "",
      "cols": [
        {
          "span": 12,
          "blocks": [
            { "type": "hero", "visible": true, "data": { "title": "..." } }
          ]
        }
      ]
    }
  ]
}
```

- `width`: `""` (contained) or `"full"` (edge-to-edge)
- `span`: 1-12; the spans in a row should sum to 12
- `data`: exactly the field keys `get_block_schema` lists for that `type`.
  Anything else is dropped, so a wrong key fails quietly — check the layout that
  comes back to confirm what was actually stored.

Tags live inside block data (each card in `brief_cards` has its own `tags`
repeater, for instance), not on the page itself.

### Writable page fields

| Field | Notes |
| --- | --- |
| `title` | Required on create. Also seeds the URL slug. |
| `path` | Public URL, e.g. `/study-in-ireland`. Defaults to `/briefs/{slug}`. Reserved prefixes (`/admin`, `/mcp`, `/api`, `/storage`, `/login`, `/logout`) and paths already in use fall back to the current path. |
| `page_title` | SEO `<title>`, capped at 90. Defaults to `{title} | One Degree Advisory`. |
| `meta_description` | SEO description, capped at 170. |
| `layout` | The page body, as above. |

`visible` is deliberately not writable.

## Reviewing a draft

A hidden page 404s for the public but renders normally for a **super-admin**
signed in at `/admin`. So: open the `preview_url` Claude gives you in the browser
where you are already signed in, then publish from `/admin/pages`.

## Managing tokens

The **Claude access** screen lists every token with its status, expiry, last-used
time and use count, so you can tell an idle token from a working one before
revoking it. Revoking takes effect on the next call.

Tokens expire on their own — that is the point of the short lifetime. An expired
or revoked token produces a readable error in Claude rather than a silent
failure.

Every write is logged to `storage/logs/page-api-*.log` with the token id, page
slug, block count and caller IP.

## Implementation notes

Worth knowing if you ever touch this code.

**Never answer 401.** Claude treats a 401 as "begin OAuth discovery". This site
has no authorization server, so a 401 makes the connector fail with *"Couldn't
reach the MCP server"* — which looks like a network problem and hides the real
cause. Instead `initialize` and `tools/list` are open (they expose no data), and
the token is checked inside `tools/call`, where a failure comes back as a tool
error the model can read out. `PageBuilderMcpTest` pins this down.

**Stateless Streamable HTTP.** One endpoint; POST carries JSON-RPC and is
answered with a single `application/json` object rather than an SSE stream, which
the spec permits. No `Mcp-Session-Id` is issued, so nothing needs to survive
between requests — which suits shared hosting. `GET /mcp` answers 405, the
spec's way of saying "no server-initiated stream here".

**Registered outside the `web` group** (`bootstrap/app.php` → `then:`), because
an MCP client holds no session and no CSRF token.

**Protocol versions**: `2025-11-25`, `2025-06-18`, `2025-03-26`. An unknown
version in the `MCP-Protocol-Version` header is a 400, per spec; an unknown one
in `initialize` params gets our newest back.

**Anthropic's egress range** is `160.79.104.0/21`, if you ever want to restrict
the endpoint at the firewall. Not currently applied — the token is the control.

| Env var | Default | Meaning |
| --- | --- | --- |
| `PAGE_MCP_ENABLED` | `true` | Set false to take `/mcp` down entirely |
| `PAGE_MCP_RATE_LIMIT` | `120` | Requests per minute per IP |
| `PAGE_MCP_ALLOWED_ORIGINS` | Claude + ChatGPT hosts | Hosts accepted in an `Origin` header, comma separated |
| `PAGE_API_DRAFTS_ONLY` | `true` | Blocks publishing and edits to live pages |

There is no server-wide credential: the endpoint authenticates with the
expiring tokens above, so it is inert while none are outstanding.

## Tests

- `tests/Feature/PageBuilderMcpTest.php` — handshake, the never-401 rule, token
  expiry and revocation, every tool, and that an MCP-authored page renders.
- `tests/Feature/PageBuilderTokenAdminTest.php` — issuing, the show-once
  behaviour, super-admin gating, revocation.
