<?php

declare(strict_types=1);

namespace PhpDep\Dependencies;

use PhpDep\Dto\ClassReferencesInfo;

class ClassHierarchyAggregator
{
    /**
     *
     *
     * @param array $classHierarchy
     * @param array $config
     *
     * @return array
     */
    public function aggregateHierarchy(array $classHierarchy, array $config): array
    {
        $internalHierarchy = $this->buildInternalHierarchy($classHierarchy);

        return $this->aggregateHierarchyInternal($internalHierarchy, $config, $config);
    }

    protected function buildInternalHierarchy(array $classHierarchy): array
    {
        $hierarchy = [];

        foreach ($classHierarchy as $className => $references) {
            $node = [
                'type' => 'class',
                'dependencies' => $references,
            ];

            $this->setArrayNamespaced($hierarchy, $className, $node);
        }

        return $hierarchy;
    }

    private function setArrayNamespaced(array &$array, string $key, mixed $value, string $separator = '\\'): void
    {
        $keys = explode($separator, $key);

        for ($i = 0; $i < count($keys) - 1; $i++) {
            $subKey = $keys[$i];

            if (!isset($array[$subKey]) || !is_array($array[$subKey])) {
                $array[$subKey] = [];
            }

            $array = &$array[$subKey];
        }

        $array[array_pop($keys)] = $value;
    }

    protected function aggregateHierarchyInternal(array $hierarchy, array $config, ?array $subConfig): array
    {
        if (isset($hierarchy['type'])) {
            $dependencies = array_map(
                function (string $name) use ($config): string {
                    return $this->collapseClassName($name, $config);
                },
                $hierarchy['dependencies'],
            );

            return [
                'type' => 'agg',
                'dependencies' => array_unique($dependencies),
            ];
        }

        $aggregatedHierarchy = [];
        $individualChildren = is_array($subConfig);

        foreach ($hierarchy as $namespaceKey => $subHierarchy) {
            if ($individualChildren) {
                $aggregatedHierarchy[$namespaceKey] = $this->aggregateHierarchyInternal(
                    $subHierarchy,
                    $config,
                    $subConfig[$namespaceKey] ?? null,
                );
            } else {
                $aggregatedHierarchy[] = $this->aggregateHierarchyInternal($subHierarchy, $config, null);
            }
        }

        if (!$individualChildren) {
            $dependencies = array_reduce(
                $aggregatedHierarchy,
                static function (array $carry, array $item): array {
                    return array_merge($carry, $item['dependencies'] ?? []);
                },
                []
            );

            $dependencies = array_unique($dependencies);
            $aggregatedHierarchy = [
                'type' => 'agg',
                'dependencies' => $dependencies,
            ];
        }

        return $aggregatedHierarchy;

    }

    protected function collapseClassName(string $name, array $aggregatorConfig): string
    {
        $subConfig = $aggregatorConfig;
        $parts = explode('\\', $name);
        $accumulatedParts = [];

        foreach ($parts as $part) {
            $accumulatedParts[] = $part;

            if (isset($subConfig[$part])) {
                $subConfig = $subConfig[$part];
            } else {
                break;
            }
        }

        return implode('\\', $accumulatedParts);
    }
}
