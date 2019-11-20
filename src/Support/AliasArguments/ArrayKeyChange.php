<?php

namespace Rebing\GraphQL\Support\AliasArguments;

class ArrayKeyChange
{
    private $array;

    private $currentFullPath;

    private function __construct(array $array)
    {
        $this->array = $array;
    }

    public static function in(array $array): self
    {
        return new self($array);
    }

    public function modify(array $pathKeyMappings): array
    {
        /** @var array<string, string> $pathKeyMappings */
        $pathKeyMappings = $this->orderPaths($pathKeyMappings);

        foreach ($pathKeyMappings as $path => $replaceKey) {
            $this->currentFullPath = $path;
            $this->array = $this->changeKey($this->array, explode('.', $path), $replaceKey);
        }

        return $this->array;
    }

    private function orderPaths(array $paths): array
    {
        uksort($paths, function (string $a, string $b) {
            return $this->pathLevels($b) <=> $this->pathLevels($a);
        });

        return $paths;
    }

    private function pathLevels(string $path): int
    {
        return substr_count($path, '.');
    }

    private function changeKey(array &$target, array $segments, string $replaceKey): array
    {
        $segment = array_shift($segments);

        if (empty($segments)) {
            if (isset($target[$segment])) {
                $target[$replaceKey] = $target[$segment];
                unset($target[$segment]);
            }

            return $target;
        }

        if ($segment === '*') {
            foreach ($target as &$inner) {
                $this->changeKey($inner, $segments, $replaceKey);
            }

            return $target;
        }

        $this->changeKey($target[$segment], $segments, $replaceKey);

        return $target;
    }
}
