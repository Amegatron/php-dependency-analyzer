<?php

declare(strict_types=1);

namespace PhpDep\Cache;

class FileCache
{
    public function __construct(protected string $cacheDir)
    {
    }

    public function hasCache(string $filename): bool
    {
        $time = filemtime($filename);
        $cacheTime = 0;
        $cacheFile = $this->getCacheFilename($filename);

        if (file_exists($cacheFile)) {
            $cacheTime = filemtime($cacheFile);
        }

        return $cacheTime >= $time;
    }

    public function get(string $filename): ?string
    {
        $result = null;

        if ($this->hasCache($filename)) {
            $cacheFile = $this->getCacheFilename($filename);

            $result = file_get_contents($cacheFile);
        }

        return $result;
    }

    public function set(string $filename, string $value): void
    {
        $cacheFile = $this->getCacheFilename($filename);
        $this->ensureDirectoriesExist($cacheFile);
        file_put_contents($cacheFile, $value);
    }

    protected function ensureDirectoriesExist(string $filename): void
    {
        $parts = explode('/', $filename);
        $dir = implode('/', array_slice($parts, 0, -1));

        if (!file_exists($dir)) {
            mkdir($dir, recursive: true);
        }
    }

    protected function getCacheFilename(string $filename): string
    {
        $hash = $this->getFilenameHash($filename);

        return sprintf(
            '%s/%s/%s/%s',
            $this->cacheDir,
            substr($hash, 0, 2),
            substr($hash, 2, 2),
            substr($hash, 4),
        );
    }

    protected function getFilenameHash(string $filename): string
    {
        return md5($filename);
    }
}
