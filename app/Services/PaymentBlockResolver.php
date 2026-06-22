<?php

namespace App\Services;

use App\Support\BriefPageStore;

class PaymentBlockResolver
{
    public function __construct(private BriefPageStore $pages) {}

    public function resolve(string $pageSlug, string $blockId, int $optionIndex): ?array
    {
        $page = $this->pages->find($pageSlug);
        if ($page === null || ! ($page['visible'] ?? true)) {
            return null;
        }

        $block = $this->findBlock($page, $blockId);
        if ($block === null || ($block['type'] ?? '') !== 'payment' || ! ($block['visible'] ?? true)) {
            return null;
        }

        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        $options = array_values(array_filter(
            $data['options'] ?? [],
            fn (mixed $option): bool => is_array($option) && trim((string) ($option['label'] ?? '')) !== ''
        ));
        $option = $options[$optionIndex] ?? null;
        if (! is_array($option)) {
            return null;
        }

        $amount = self::rupeesToPaise((string) ($option['amount'] ?? ''));
        if ($amount === null) {
            return null;
        }

        $itemName = mb_substr(trim((string) ($option['label'] ?? $data['title'] ?? 'Website payment')), 0, 200);
        $description = mb_substr(trim((string) ($option['description'] ?? $data['description'] ?? '')), 0, 500);

        return [
            'page' => $page,
            'block' => $block,
            'data' => $data,
            'option' => $option,
            'option_index' => $optionIndex,
            'item_name' => $itemName,
            'description' => $description,
            'amount' => $amount,
            'currency' => 'INR',
            'theme_color' => self::safeColour((string) ($data['accent'] ?? ''), '#F05A28'),
        ];
    }

    public static function rupeesToPaise(string $value): ?int
    {
        $value = preg_replace('/[₹,\s]/u', '', trim($value)) ?? '';
        if (! preg_match('/^(\d{1,7})(?:\.(\d{1,2}))?$/', $value, $matches)) {
            return null;
        }

        $paise = ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '', 2, '0');

        return $paise >= 100 && $paise <= 1_000_000_000 ? $paise : null;
    }

    private function findBlock(array $page, string $blockId): ?array
    {
        foreach (($page['layout'] ?? []) as $row) {
            foreach (($row['cols'] ?? []) as $column) {
                foreach (($column['blocks'] ?? []) as $block) {
                    if (is_array($block) && hash_equals((string) ($block['id'] ?? ''), $blockId)) {
                        return $block;
                    }
                }
            }
        }

        foreach (($page['sections'] ?? []) as $block) {
            if (is_array($block) && hash_equals((string) ($block['id'] ?? ''), $blockId)) {
                return $block;
            }
        }

        return null;
    }

    private static function safeColour(string $value, string $fallback): string
    {
        return preg_match('/^#[0-9a-f]{6}$/i', trim($value)) ? trim($value) : $fallback;
    }
}
