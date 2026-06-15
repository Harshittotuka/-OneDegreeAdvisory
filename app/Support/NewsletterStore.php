<?php

namespace App\Support;

/**
 * File-backed store for newsletter sign-ups. Subscribers live in a single
 * editable JSON file (storage/app/newsletter-subscribers.json) so the site can
 * collect them with no database — consistent with the rest of the CMS data.
 */
class NewsletterStore
{
    private string $path;

    public function __construct()
    {
        $this->path = storage_path('app/newsletter-subscribers.json');
    }

    /** All subscribers, newest first. */
    public function all(): array
    {
        if (! is_file($this->path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * Record a subscriber. De-duplicates case-insensitively by email — an address
     * that is already on the list is left untouched. Returns true when newly added.
     */
    public function add(string $email, string $source = ''): bool
    {
        $email = trim($email);
        $key = mb_strtolower($email);

        $rows = $this->all();
        foreach ($rows as $row) {
            if (mb_strtolower((string) ($row['email'] ?? '')) === $key) {
                return false; // already subscribed
            }
        }

        array_unshift($rows, [
            'email' => $email,
            'source' => $source,
            'date' => now()->toDateTimeString(),
        ]);

        $this->writeAll($rows);

        return true;
    }

    public function delete(string $email): void
    {
        $key = mb_strtolower(trim($email));

        $this->writeAll(array_values(array_filter(
            $this->all(),
            fn (array $r) => mb_strtolower((string) ($r['email'] ?? '')) !== $key
        )));
    }

    private function writeAll(array $rows): void
    {
        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0775, true);
        }

        file_put_contents(
            $this->path,
            json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
