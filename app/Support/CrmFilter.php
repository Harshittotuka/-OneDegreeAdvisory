<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Reads a CRM list filter that may arrive as one value or several.
 *
 * The filter bar posts arrays now (status[]=new&status[]=contacted), but plain
 * scalars still arrive from every pre-filtered link into the CRM — the dashboard
 * stat cards, the notification list, the follow-up planner's own links, and any
 * URL a counsellor bookmarked before this existed. Both shapes are read here so
 * no call site has to care which one it got.
 */
final class CrmFilter
{
    /**
     * The requested values for $key that appear in $allowed, ordered the way the
     * allow-list orders them and free of duplicates. Empty means "no filter",
     * which is what every caller does with an absent or junk value.
     *
     * @param  array<string, mixed>|list<string>  $allowed  an option map (keys are the values) or a plain list
     * @return list<string>
     */
    public static function values(Request $request, string $key, array $allowed): array
    {
        $permitted = array_is_list($allowed) ? $allowed : array_keys($allowed);
        $requested = self::raw($request, $key);

        return array_values(array_filter(
            array_map(static fn ($value): string => (string) $value, $permitted),
            static fn (string $value): bool => in_array($value, $requested, true),
        ));
    }

    /**
     * The requested values as trimmed, de-duplicated strings, with no allow-list
     * applied. For filters whose valid set is not fixed in code — source pages,
     * program names, team ids — where the query itself does the narrowing.
     *
     * @return list<string>
     */
    public static function raw(Request $request, string $key): array
    {
        $value = $request->query($key);
        $clean = [];

        foreach (is_array($value) ? $value : [$value] as $entry) {
            // A nested array is not something any filter sends; ignore it rather
            // than stringify it into a warning.
            if ($entry === null || is_array($entry)) {
                continue;
            }
            $entry = trim((string) $entry);
            if ($entry !== '' && ! in_array($entry, $clean, true)) {
                $clean[] = $entry;
            }
        }

        return $clean;
    }

    /**
     * The requested values as positive integers — team and counsellor ids.
     *
     * @return list<int>
     */
    public static function ids(Request $request, string $key): array
    {
        $ids = [];
        foreach (self::raw($request, $key) as $value) {
            if (ctype_digit($value) && (int) $value > 0 && ! in_array((int) $value, $ids, true)) {
                $ids[] = (int) $value;
            }
        }

        return $ids;
    }
}
