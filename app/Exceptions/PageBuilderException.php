<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A Page Builder write that was refused for a business reason rather than a
 * validation problem. Carries a stable machine code and an HTTP status so a
 * caller can report the refusal without re-deriving the rule — the MCP server
 * turns these into readable tool errors.
 */
class PageBuilderException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 409,
    ) {
        parent::__construct($message);
    }

    public static function notFound(): self
    {
        return new self(
            'page_not_found',
            'No page with that slug. List the pages first to see the available slugs.',
            404,
        );
    }

    public static function published(string $slug): self
    {
        return new self(
            'page_is_published',
            sprintf(
                'The page "%s" is live, so it cannot be edited directly. Duplicate it to get a hidden draft copy, change that, and publish it from /admin/pages when it looks right.',
                $slug,
            ),
            409,
        );
    }

    public static function publishedDelete(string $slug): self
    {
        return new self(
            'page_is_published',
            sprintf('The page "%s" is live. Unpublish it in /admin/pages first if you really mean to delete it.', $slug),
            409,
        );
    }
}
