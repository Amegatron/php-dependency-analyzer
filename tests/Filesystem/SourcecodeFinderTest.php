<?php

declare(strict_types=1);

namespace PhpDep\Tests\Filesystem;

use PhpDep\Filesystem\SourcecodeFinder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpDep\Filesystem\SourcecodeFinder
 */
class SourcecodeFinderTest extends TestCase
{
    public function testFinder(): void {
        $finder = new SourcecodeFinder();
        $files = iterator_to_array($finder->getFiles([__DIR__ . '/stubfs']));
        $this->assertCount(4, $files);

        $files = array_map(
            static fn (string $path) => str_replace(__DIR__ . '/stubfs/', '', $path),
            $files
        );

        $expectedFiles = [
            'test1.php',
            'test2.php',
            'test/subtest1.php',
            'test/subtest2.php',
        ];

        foreach ($expectedFiles as $file) {
            $this->assertContains($file, $files);
        }
    }
}
