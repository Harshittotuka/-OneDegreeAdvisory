<?php

namespace App\Support;

use App\Models\PageBuilderToken;
use Illuminate\Support\Facades\Log;

/**
 * Issues and verifies the expiring tokens that let claude.ai (or any other
 * machine client) reach the Page Builder.
 *
 * Only a SHA-256 hash is persisted. Verification is a hash lookup rather than a
 * string comparison, so there is no timing signal and no way to recover a
 * token from the database.
 */
class PageBuilderTokens
{
    /** Prefix makes a leaked token identifiable in a log or a paste. */
    public const PREFIX = 'odp_pb_';

    /** Default lifetime, in days, for a newly issued token. */
    public const DEFAULT_DAYS = 15;

    /** Lifetimes offered in the admin UI. */
    public const ALLOWED_DAYS = [7, 15, 30, 90];

    /**
     * Mint a token. The plaintext is returned once and never stored — the
     * caller must show it to the admin immediately.
     *
     * @return array{token: string, model: PageBuilderToken}
     */
    public function issue(string $label, int $days, ?string $createdBy = null): array
    {
        $days = in_array($days, self::ALLOWED_DAYS, true) ? $days : self::DEFAULT_DAYS;
        $plain = self::PREFIX.bin2hex(random_bytes(24));

        $model = PageBuilderToken::create([
            'label' => mb_substr(trim($label), 0, 120) ?: 'Untitled token',
            'token_hash' => self::hash($plain),
            'hint' => substr($plain, strlen(self::PREFIX), 6),
            'expires_at' => now()->addDays($days),
            'created_by' => $createdBy,
        ]);

        Log::channel('page_api')->info('page-api.token_issued', [
            'id' => $model->id,
            'label' => $model->label,
            'expires_at' => $model->expires_at->toIso8601String(),
            'created_by' => $createdBy,
        ]);

        return ['token' => $plain, 'model' => $model];
    }

    /**
     * The usable token matching this plaintext, or null. Touches last_used_at so
     * an admin can see whether a token is actually in use before revoking it.
     */
    public function verify(?string $plain): ?PageBuilderToken
    {
        $plain = trim((string) $plain);
        if ($plain === '') {
            return null;
        }

        $token = PageBuilderToken::where('token_hash', self::hash($plain))->first();
        if ($token === null || ! $token->isUsable()) {
            return null;
        }

        // Deliberately not a full save() — keeps a hot path to one cheap update
        // and avoids touching updated_at on every tool call.
        PageBuilderToken::whereKey($token->getKey())->update([
            'last_used_at' => now(),
            'use_count' => $token->use_count + 1,
        ]);

        return $token;
    }

    public function revoke(int $id): bool
    {
        $token = PageBuilderToken::find($id);
        if ($token === null || $token->revoked_at !== null) {
            return false;
        }

        $token->revoked_at = now();
        $token->save();

        Log::channel('page_api')->info('page-api.token_revoked', [
            'id' => $token->id,
            'label' => $token->label,
        ]);

        return true;
    }

    /** Newest first, for the admin list. */
    public function all()
    {
        return PageBuilderToken::orderByDesc('created_at')->get();
    }

    public function usableCount(): int
    {
        return PageBuilderToken::usable()->count();
    }

    /**
     * Whether any client could authenticate at all. Used to tell an admin that
     * the connector will not work until they issue a token.
     */
    public function anyUsable(): bool
    {
        return $this->usableCount() > 0;
    }

    private static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
