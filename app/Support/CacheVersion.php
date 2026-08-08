<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class CacheVersion
{
    public static function key(string $group, string $cacheKey): string
    {
        return self::taggedKey([$group], $cacheKey);
    }

    /**
     * @param  array<int, string>  $groups
     */
    public static function taggedKey(array $groups, string $cacheKey): string
    {
        $versions = array_map(static fn (string $group) => self::current($group), $groups);

        return $cacheKey.':v'.implode('_', $versions);
    }

    public static function bump(string ...$groups): void
    {
        foreach ($groups as $group) {
            Cache::forever(self::versionKey($group), self::current($group) + 1);
        }
    }

    private static function current(string $group): int
    {
        return (int) Cache::get(self::versionKey($group), 1);
    }

    private static function versionKey(string $group): string
    {
        return "cache_version:{$group}";
    }
}
