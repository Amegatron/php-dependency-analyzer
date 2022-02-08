<?php

declare(strict_types=1);

namespace PhpDep\Diagram;

class DiagramDumper
{
    public function dump(array $nodes, string $filename): void
    {
        $result = [];

        foreach ($nodes as $name => $references) {
            $this->setArrayNamespaced($result, $name, $references);
        }

        $namespaces = $this->getNamespaceDeclarations($result);
        $links = $this->getLinksDeclarations($nodes);

        $result = <<<PLANTUML
@startuml
set namespaceSeparator /

{$namespaces}

{$links}

@enduml
PLANTUML;

        file_put_contents($filename, $result);
    }

    private function getLinksDeclarations(array $flatHierarchy): string
    {
        $result = '';

        foreach ($flatHierarchy as $namespace => $references) {
            if (empty($namespace)) {
                continue;
            }

            $namespace = str_replace('\\', '/', $namespace);

            foreach ($references as $dependency) {
                if (empty($dependency)) {
                    continue;
                }

                $dependency = str_replace('\\', '/', $dependency);

                $result .= sprintf(
                    '"%s" --> "%s"' . PHP_EOL,
                    $namespace,
                    $dependency,
                );
            }
        }

        return $result;
    }

    private function getNamespaceDeclarations(array $hierarchy): string
    {
        return $this->getNamespaceDeclarationsInternal($hierarchy, 0);
    }

    private function getNamespaceDeclarationsInternal(array $hierarchy, int $level): string
    {
        $result = '';

        if (empty($hierarchy) || (isset($hierarchy[0]) && !is_array($hierarchy[0]))) {
            return $result;
        }

        foreach ($hierarchy as $namespaceKey => $subHierarchy) {
            if (empty($namespaceKey)) {
                break;
            }

            $padding = str_repeat(' ', $level * 4);
            $result .= $padding . 'namespace ' . $namespaceKey . ' {' . PHP_EOL;
            $result .= $this->getNamespaceDeclarationsInternal($subHierarchy, $level + 1);
            $result .= $padding . '}' . PHP_EOL;
        }

        return $result;
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
}
