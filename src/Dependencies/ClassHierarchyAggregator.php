<?php

declare(strict_types=1);

namespace PhpDep\Dependencies;

class ClassHierarchyAggregator
{
    public function aggregateHierarchy(array $classHierarchy, array $config, bool $removeMissing = true): array
    {
        print("Building internal hierarchy..." . PHP_EOL);
        $internalHierarchy = $this->buildInternalHierarchy($classHierarchy);
        print("Aggregating..." . PHP_EOL);
        $aggregatedHierarchy = $this->aggregateHierarchyInternal($internalHierarchy, $config, $config);
        print("Collapsing..." . PHP_EOL);
        $hierarchy = $this->collapseAggregatedHierarchy($aggregatedHierarchy);
        print("Removing loops..." . PHP_EOL);
        $hierarchy = $this->removeLoops($hierarchy);

        if ($removeMissing) {
            print("Removing missing elements..." . PHP_EOL);
            $hierarchy = $this->removeMissingElements($hierarchy);
        }

        return $hierarchy;
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

    protected function setArrayNamespaced(array &$array, string $key, mixed $value, string $separator = '\\'): void
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

    protected function removeLoops(array $hierarchy): array
    {
        $result = [];

        foreach ($hierarchy as $namespace => $dependencies) {
            $loopIndex = array_search($namespace, $dependencies);

            if ($loopIndex !== false) {
                unset($dependencies[$loopIndex]);
            }

            $result[$namespace] = $dependencies;
        }

        return $result;
    }

    protected function removeMissingElements(array $hierarchy): array
    {
        $result = [];

        foreach ($hierarchy as $name => $dependencies) {
            $newDependencies = [];

            foreach ($dependencies as $dependency) {
                if (isset($hierarchy[$dependency])) {
                    $newDependencies[] = $dependency;
                }
            }

            $result[$name] = $newDependencies;
        }

        return $result;
    }

    protected function collapseAggregatedHierarchy(array $hierarchy): array
    {
        $result = [];

        foreach ($hierarchy as $nsPart => $subHierarchy) {
            if (isset($subHierarchy['type'])) {
                $result[$nsPart] = $subHierarchy['dependencies'];
            } else {
                $subResult = $this->collapseAggregatedHierarchy($subHierarchy);

                foreach ($subResult as $subNsPart => $item) {
                    $result[$nsPart . '\\' . $subNsPart] = $item;
                }
            }
        }

        return $result;
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
