<?php

namespace App\Services;

use App\Models\PaymentAttempt;

class LegacyWebsiteLeadImporter
{
    public function __construct(private WebsiteLeadManager $leads) {}

    /** @return array{profiles:int,newsletters:int,payments:int} */
    public function import(): array
    {
        $counts = ['profiles' => 0, 'newsletters' => 0, 'payments' => 0];
        foreach ($this->jsonRows(storage_path('app/profile-submissions.json')) as $row) {
            $id = trim((string) ($row['id'] ?? ''));
            $this->leads->capture(
                (string) ($row['source'] ?? 'website'),
                (string) ($row['source_label'] ?? 'Website'),
                isset($row['degree']) ? (string) $row['degree'] : null,
                (array) ($row['sections'] ?? []),
                (array) ($row['meta'] ?? []),
                $id !== '' ? 'legacy-profile:'.$id : null,
                $row['submitted_at'] ?? null,
            );
            $counts['profiles']++;
        }

        foreach ($this->jsonRows(storage_path('app/newsletter-subscribers.json')) as $row) {
            $email = trim((string) ($row['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $this->leads->captureNewsletter($email, (string) ($row['source'] ?? 'Newsletter'));
            $counts['newsletters']++;
        }

        PaymentAttempt::query()->whereNull('crm_lead_id')->orderBy('id')->each(function (PaymentAttempt $attempt) use (&$counts): void {
            $this->leads->capturePayment($attempt);
            if ($attempt->status === 'paid') {
                $this->leads->syncPaymentStatus($attempt->fresh());
            }
            $counts['payments']++;
        });

        return $counts;
    }

    private function jsonRows(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }
        $rows = json_decode((string) file_get_contents($path), true);

        return is_array($rows) ? $rows : [];
    }
}
