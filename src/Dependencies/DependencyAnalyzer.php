<?php

declare(strict_types=1);

namespace PhpDep\Dependencies;

class DependencyAnalyzer
{
    public function dependenciesOfWithout(string $module, array $exclude, array $hierarchy): array
    {
        if (!isset($hierarchy[$module])) {
            return [];
        }

        foreach ($exclude as $name => $excludeDependencies) {
            if (!isset($hierarchy[$name])) {
                continue;
            }

            $hierarchy[$name] = array_diff($hierarchy[$name], $excludeDependencies);
        }

        return $this->dependenciesOf($module, $hierarchy);
    }

    public function dependenciesOf(string $module, array $hierarchy): array
    {
        if (!isset($hierarchy[$module])) {
            return [];
        }

        $result = [$module => $hierarchy[$module]];
        $queue = $hierarchy[$module];
        $i = 0;

        while ($i < count($queue)) {
            $node = $queue[$i];
            $i++;

            if (!isset($hierarchy[$node])) {
                continue;
            }

            if (isset($result[$node])) {
                continue;
            }

            $result[$node] = $hierarchy[$node];
            $queue = array_merge($queue, $hierarchy[$node]);
        }

        return $result;
    }
}
