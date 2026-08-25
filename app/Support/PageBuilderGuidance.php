<?php

namespace App\Support;

/**
 * The working rules an assistant needs before it builds a page here, in one
 * place so the MCP server's own `instructions` and the copy-paste block on
 * /admin/pages/tokens cannot drift apart.
 *
 * The single-file rule is the one that is easy to get wrong: a block stores ONE
 * code field, so an assistant that reaches for its usual habit of emitting
 * separate .html, .css and .js files produces something this CMS cannot hold.
 */
class PageBuilderGuidance
{
    /** Shown once by the client at connection time. */
    public static function serverInstructions(): string
    {
        return <<<'TXT'
        This server builds pages on the One Degree Advisory website.

        AUTHENTICATION
        Every tool needs an access token. If the connector was not set up with an
        Authorization header, pass the token as the `token` argument on each call.

        ONE PAGE IS ONE RECORD, NOT A SET OF FILES
        There is no filesystem here and no way to attach separate .css or .js
        files. If you write custom markup, put all of it in a single `embed`
        block: the HTML, a <style> tag for the CSS, and a <script> tag for the
        JS, all inside that one field. Never split a page across several embed
        blocks that depend on each other, and never split one request into
        several pages.

        BEFORE COMPOSING
        Call list_block_types, then get_block_schema for the types you intend to
        use. Only the field keys that schema lists are stored — anything else is
        dropped in silence, so read the layout back and check it saved.

        AFTER WRITING
        Pages are always created hidden and you cannot publish. You also cannot
        edit a page that is already live — duplicate it into a draft instead.
        Give the person the preview_url and let them publish from /admin/pages.
        TXT;
    }

    /**
     * The block a human pastes into a Claude or ChatGPT project. Deliberately
     * plainer than the server instructions: it has to read well to a person
     * skimming it as much as to the model following it.
     */
    public static function projectInstructions(): string
    {
        return <<<'TXT'
        The One Degree Advisory Page Builder connector is available.

        Page Builder token: PASTE_YOUR_TOKEN_HERE
        Pass this token as the `token` argument on every Page Builder tool call.

        Build one page per request, as a single record. There is no filesystem:
        if a page needs custom code, put the HTML, the CSS in a <style> tag and
        the JS in a <script> tag all inside ONE `embed` block. Do not produce
        separate .css or .js files, and do not spread one page across several
        blocks that depend on each other.

        Before composing a page, call list_block_types, then get_block_schema
        for the block types you plan to use. Only the field keys that schema
        lists are saved, so read the page back and confirm what was stored.

        Every page is created hidden. You cannot publish and you cannot edit a
        page that is already live. When you are done, give me the preview_url
        and stop.
        TXT;
    }
}
