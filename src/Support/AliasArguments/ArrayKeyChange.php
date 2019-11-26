<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support\AliasArguments;

class ArrayKeyChange
{
    public function modify(array $array, array $pathKeyMappings): array
    {
        $pathKeyMappings = $this->orderPaths($pathKeyMappings);

        foreach ($pathKeyMappings as $path => $replaceKey) {
            $array = $this->changeKey($array, explode('.', $path), $replaceKey);
        }

        return $array;
    }

    /**
     * @return array<string, string>
     */
    private function orderPaths(array $paths): array
    {
        uksort($paths, function (string $a, string $b): int {
            return $this->pathLevels($b) <=> $this->pathLevels($a);
        });

        return $paths;
    }

    private function pathLevels(string $path): int
    {
        return substr_count($path, '.');
    }

    private function changeKey(array $target, array $segments, string $replaceKey): array
    {
        $segment = array_shift($segments);

        if (empty($segments)) {
            if (array_key_exists($segment, $target) && $replaceKey !== $segment) {
                $target[$replaceKey] = $target[$segment];
                unset($target[$segment]);
            }

            return $target;
        }

        if ('*' === $segment) {
            foreach ($target as $index => $inner) {
                if ($inner) {
                    $target[$index] = $this->changeKey($inner, $segments, $replaceKey);
                }
            }

            return $target;
        }

        if (array_key_exists($segment, $target) && is_array($target[$segment])) {
            $target[$segment] = $this->changeKey($target[$segment], $segments, $replaceKey);
        }

        return $target;
    }
}
