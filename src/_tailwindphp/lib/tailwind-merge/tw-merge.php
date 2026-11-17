<?php

declare(strict_types=1);

/**
 * Port of: https://github.com/dcastil/tailwind-merge/blob/main/packages/tailwind-merge/src/lib/tw-merge.ts
 *
 * Main entry point for tailwind-merge.
 *
 * @port-deviation:singleton Uses static variable for singleton pattern
 */

namespace TailwindPHP\Lib\TailwindMerge;

require_once __DIR__ . '/create-tailwind-merge.php';
require_once __DIR__ . '/default-config.php';

/**
 * Merge Tailwind CSS classes, removing conflicts.
 *
 * Later classes take precedence over earlier ones when there are conflicts.
 *
 * @param mixed ...$classLists Class values to merge
 * @return string Merged class string
 */
function twMerge(...$classLists): string
{
    static $merge = null;

    if ($merge === null) {
        // Keep fork aliases outside the generated upstream configuration.
        $merge = createTailwindMerge(fn () => DefaultConfig::get(), static function (array $config): array {
            $groups = &$config['classGroups'];
            foreach (['grow' => 'flex-grow', 'shrink' => 'flex-shrink'] as $modern => $legacy) {
                foreach ($groups[$modern] as $definition) {
                    if (is_array($definition) && isset($definition[$modern])) {
                        $groups[$modern][] = [$legacy => $definition[$modern]];
                    }
                }
            }
            $groups['box-decoration'][] = ['decoration' => ['slice', 'clone']];
            $groups['text-overflow'][] = 'overflow-ellipsis';
            $groups['bg-image'][] = ['bg-gradient-to' => ['t', 'tr', 'r', 'br', 'b', 'bl', 'l', 'tl']];

            return $config;
        });
    }

    return $merge(...$classLists);
}
