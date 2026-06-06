<?php
declare(strict_types=1);

namespace PhpDep\Tests\Parser;

use PhpDep\Parser\ReferenceCollectingVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpDep\Parser\ReferenceCollectingVisitor
 */
class ReferenceCollectingVisitorTest extends TestCase
{
    /**
     * @dataProvider referenceTestData
     */
    public function testReferenceParsing(string $fileName, array $expectedReferences) {
        $parser = (new ParserFactory)->createForNewestSupportedVersion();
        $ast = $parser->parse(file_get_contents($fileName));

        $visitor = new ReferenceCollectingVisitor();
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        $references = $visitor->getReferences();
        $this->assertSame($expectedReferences, $references);
    }

    protected function referenceTestData(): array {
        return [
            [__DIR__ . '/stubs/stub1.stub', ['App\Services\UserService']],
            [__DIR__ . '/stubs/stub2.stub', ['App\Contracts\RepositoryInterface']],
            [__DIR__ . '/stubs/stub3.stub', ['App\Models\OrderRecord']],
            [__DIR__ . '/stubs/stub4.stub', ['App\Factories\ProductFactory']],
            [__DIR__ . '/stubs/stub5.stub', ['App\Helpers\CacheHelper']],
            [__DIR__ . '/stubs/stub6.stub', ['App\Entities\Category']],
            [__DIR__ . '/stubs/stub7.stub', ['App\Base\AbstractController', 'App\Contracts\LoggableInterface']],
            [__DIR__ . '/stubs/stub8.stub', ['App\Traits\AuthTrait']],
            [__DIR__ . '/stubs/stub9.stub', ['App\Exceptions\HttpException']],
            [__DIR__ . '/stubs/stub10.stub', ['App\Entities\Customer']],
            [__DIR__ . '/stubs/stub11.stub', ['App\Attributes\Route']],
            [__DIR__ . '/stubs/stub12.stub', ['App\Exceptions\ValidationError', 'App\Models\User']],
            [
                __DIR__ . '/stubs/stub13.stub',
                [
                    'App\Config\DatabaseConfig',
                    'App\Models\Order',
                    'App\Models\Session',
                    'App\Models\User',
                    'App\Services\UserService',
                    'Test\DocBlocks\Local\CacheManager',
                    'stdClass',
                ],
            ],
            [
                __DIR__ . '/stubs/stub14.stub',
                [
                    'App\Attributes\Route',
                    'App\Base\AbstractController',
                    'App\Contracts\LoggableInterface',
                    'App\Contracts\RepositoryInterface',
                    'App\Entities\Category',
                    'App\Entities\Customer',
                    'App\Exceptions\HttpException',
                    'App\Factories\ProductFactory',
                    'App\Helpers\CacheHelper',
                    'App\Models\OrderRecord',
                    'App\Services\UserService',
                    'App\Traits\AuthTrait',
                ],
            ],
            [__DIR__ . '/stubs/stub15.stub', []],
        ];
    }
}
