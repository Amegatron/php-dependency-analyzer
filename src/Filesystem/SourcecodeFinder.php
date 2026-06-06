<?php

declare(strict_types=1);

namespace PhpDep\Filesystem;

use Closure;
use FilesystemIterator;
use Generator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SourcecodeFinder
{
    /**
     * @param array<string> $rootDirectories
     * @param Closure|null $shouldVisitCallback
     * @return Generator<int, string, mixed, void>
     */
    public function getFiles(
        array $rootDirectories,
        ?Closure $shouldVisitCallback = null,
    ): Generator {
        if (!$shouldVisitCallback) {
            $shouldVisitCallback = $this->getExtensionBasedVisitorFilter(['php']);
        }

        $rootDirectories = $this->expandDirectories($rootDirectories);

        foreach ($rootDirectories as $directory) {
            yield from $this->getFilesFromDirectory($directory, $shouldVisitCallback);
        }
    }

    /**
     * @param string $directory
     * @param Closure $shouldVisitCallback
     * @return Generator<int, string, mixed, void>
     */
    protected function getFilesFromDirectory(
        string $directory,
        Closure $shouldVisitCallback,
    ): Generator {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::CURRENT_AS_PATHNAME)
        );

        foreach ($iterator as $filename) {
            if ($shouldVisitCallback($filename)) {
                yield $filename;
            }
        }
    }

    /**
     * @param array<string> $extensions
     */
    protected function getExtensionBasedVisitorFilter(array $extensions): Closure
    {
        return static function (string $filename) use ($extensions): bool {
            $dotPos = strrpos($filename, '.');

            if ($dotPos === false) {
                return false;
            }

            $extension = substr($filename, $dotPos + 1);

            return in_array($extension, $extensions);
        };
    }

    /**
     * @param array<string> $directories
     * @return array<string>
     */
    protected function expandDirectories(array $directories): array
    {
        $result = [];

        foreach ($directories as $directory) {
            $asteriskPos = strpos($directory, '*');

            if ($asteriskPos === false) {
                $result[] = $directory;

                continue;
            }

            $expandedDirectories = glob($directory, GLOB_ONLYDIR);
            $result = array_merge($result, $expandedDirectories);
        }

        return $result;
    }
}
