<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\Plugin\Util;

use Composer\Autoload\ClassLoader;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Shopware\Administration\Administration as ShopwareAdministration;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\Adapter\Filesystem\MemoryFilesystemAdapter;
use Shopware\Core\Framework\App\Lifecycle\AbstractAppLoader;
use Shopware\Core\Framework\Plugin\Exception\PluginNotFoundException;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\KernelPluginLoader;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Shopware\Core\Framework\Plugin\Util\AssetService;
use Shopware\Tests\Unit\Core\Framework\Plugin\_fixtures\ExampleBundle\ExampleBundle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 *
 * @covers \Shopware\Core\Framework\Plugin\Util\AssetService
 */
class AssetServiceTest extends TestCase
{
    public function testCopyAssetsFromBundlePluginDoesNotExists(): void
    {
        $kernelMock = $this->createMock(KernelInterface::class);
        $kernelMock->expects(static::once())
            ->method('getBundle')
            ->with('bundleName')
            ->willThrowException(new \InvalidArgumentException());

        $assetService = new AssetService(
            new Filesystem(new MemoryFilesystemAdapter()),
            $kernelMock,
            new StaticKernelPluginLoader($this->createMock(ClassLoader::class)),
            $this->createMock(CacheInvalidator::class),
            $this->createMock(AbstractAppLoader::class),
            new ParameterBag()
        );

        static::expectException(PluginNotFoundException::class);
        $assetService->copyAssetsFromBundle('bundleName');
    }

    public function testCopyAssetsFromBundlePlugin(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel
            ->method('getBundle')
            ->with('ExampleBundle')
            ->willReturn($this->getBundle());

        $filesystem = new Filesystem(new MemoryFilesystemAdapter());
        $assetService = new AssetService(
            $filesystem,
            $kernel,
            new StaticKernelPluginLoader($this->createMock(ClassLoader::class)),
            $this->createMock(CacheInvalidator::class),
            $this->createMock(AbstractAppLoader::class),
            new ParameterBag()
        );

        $assetService->copyAssetsFromBundle('ExampleBundle');

        static::assertTrue($filesystem->has('bundles/example'));
        static::assertTrue($filesystem->has('bundles/example/test.txt'));
        static::assertSame('TEST', trim($filesystem->read('bundles/example/test.txt')));
        static::assertTrue($filesystem->has('bundles/featurea'));
    }

    public function testCopyAssetsFromBundlePluginInactivePlugin(): void
    {
        $filesystem = new Filesystem(new MemoryFilesystemAdapter());

        $classLoader = $this->createMock(ClassLoader::class);
        $classLoader->method('findFile')->willReturn(__FILE__);
        $pluginLoader = new StaticKernelPluginLoader(
            $classLoader,
            null,
            [
                [
                    'name' => 'ExampleBundle',
                    'baseClass' => ExampleBundle::class,
                    'path' => __DIR__ . '/_fixtures/ExampleBundle',
                    'active' => true,
                    'managedByComposer' => false,
                    'autoload' => [
                        'psr-4' => [
                            'ExampleBundle' => '',
                        ],
                    ],
                ],
            ]
        );

        $pluginLoader->initializePlugins(__DIR__);

        $kernel = $this->createMock(KernelInterface::class);
        $kernel
            ->method('getBundle')
            ->willThrowException(new \InvalidArgumentException('asd'));

        $assetService = new AssetService(
            $filesystem,
            $kernel,
            $pluginLoader,
            $this->createMock(CacheInvalidator::class),
            $this->createMock(AbstractAppLoader::class),
            new ParameterBag()
        );

        $assetService->copyAssetsFromBundle(ExampleBundle::class);

        static::assertTrue($filesystem->has('bundles/example'));
        static::assertTrue($filesystem->has('bundles/example/test.txt'));
        static::assertSame('TEST', trim($filesystem->read('bundles/example/test.txt')));
    }

    public function testBundleDeletion(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel
            ->method('getBundle')
            ->with('ExampleBundle')
            ->willReturn($this->getBundle());

        $filesystem = new Filesystem(new MemoryFilesystemAdapter());
        $assetService = new AssetService(
            $filesystem,
            $kernel,
            new StaticKernelPluginLoader($this->createMock(ClassLoader::class)),
            $this->createMock(CacheInvalidator::class),
            $this->createMock(AbstractAppLoader::class),
            new ParameterBag()
        );

        $filesystem->write('bundles/example/test.txt', 'TEST');
        $filesystem->write('bundles/featurea/test.txt', 'TEST');

        $assetService->removeAssetsOfBundle('ExampleBundle');

        static::assertFalse($filesystem->has('bundles/example'));
        static::assertFalse($filesystem->has('bundles/example/test.txt'));
        static::assertFalse($filesystem->has('bundles/featurea'));
    }

    public function testCopyAssetsClosesStreamItself(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel
            ->method('getBundle')
            ->with('ExampleBundle')
            ->willReturn($this->getBundle());

        $filesystem = $this->createMock(Filesystem::class);
        $assetService = new AssetService(
            $filesystem,
            $kernel,
            new StaticKernelPluginLoader($this->createMock(ClassLoader::class)),
            $this->createMock(CacheInvalidator::class),
            $this->createMock(AbstractAppLoader::class),
            new ParameterBag()
        );

        $filesystem->method('writeStream')
            ->willReturnCallback(function (string $path, $stream) {
                static::assertIsResource($stream);
                // Some flysystem adapters automatically close the stream e.g. google adapter
                fclose($stream);

                return true;
            });

        $assetService->copyAssetsFromBundle('ExampleBundle');
    }

    public function testCopyAssetsWithoutApp(): void
    {
        $filesystem = new Filesystem(new MemoryFilesystemAdapter());
        $assetService = new AssetService(
            $filesystem,
            $this->createMock(KernelInterface::class),
            $this->createMock(KernelPluginLoader::class),
            $this->createMock(CacheInvalidator::class),
            $this->createMock(AbstractAppLoader::class),
            new ParameterBag()
        );

        $assetService->copyAssetsFromApp('TestApp', __DIR__ . '/foo');

        static::assertEmpty($filesystem->listContents('bundles')->toArray());
    }

    public function testCopyAssetsWithApp(): void
    {
        $filesystem = new Filesystem(new MemoryFilesystemAdapter());

        $appLoader = $this->createMock(AbstractAppLoader::class);
        $appLoader
            ->method('locatePath')
            ->with(__DIR__ . '/_fixtures/ExampleBundle', 'Resources/public')
            ->willReturn(__DIR__ . '/../_fixtures/ExampleBundle/Resources/public');

        $assetService = new AssetService(
            $filesystem,
            $this->createMock(KernelInterface::class),
            $this->createMock(KernelPluginLoader::class),
            $this->createMock(CacheInvalidator::class),
            $appLoader,
            new ParameterBag()
        );

        $assetService->copyAssetsFromApp('ExampleBundle', __DIR__ . '/_fixtures/ExampleBundle');

        static::assertTrue($filesystem->has('bundles/example'));
        static::assertTrue($filesystem->has('bundles/example/test.txt'));
        static::assertSame('TEST', trim($filesystem->read('bundles/example/test.txt')));
    }

    /**
     * @return array<string, array{manifest: array<string, string>, expected-writes: array<string, string>, expected-deletes: array<string>}>
     */
    public static function adminFilesProvider(): array
    {
        return [
            'destination-empty' => [
                'manifest' => [],
                'expected-writes' => [
                    'bundles/administration/static/js/app.js' => 'AdminBundle/Resources/public/static/js/app.js',
                    'bundles/administration/one.js' => 'AdminBundle/Resources/public/one.js',
                    'bundles/administration/two.js' => 'AdminBundle/Resources/public/two.js',
                    'bundles/administration/three.js' => 'AdminBundle/Resources/public/three.js',
                ],
                'expected-deletes' => [],
            ],
            'destination-nothing-changed' => [
                'manifest' => [
                    'static/js/app.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
                    'one.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
                    'two.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
                    'three.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
                ],
                'expected-writes' => [],
                'expected-deletes' => [],
            ],
            'destination-new-and-removed' => [
                'manifest' => [
                    'static/js/app.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
                    'one.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
                    'two.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
                    'four.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
                ],
                'expected-writes' => [
                    'bundles/administration/three.js' => 'AdminBundle/Resources/public/three.js',
                ],
                'expected-deletes' => [
                    'bundles/administration/four.js',
                ],
            ],
            'destination-content-changed' => [
                'manifest' => [
                    'static/js/app.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
                    'one.js' => 'xxx13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b', // incorrect hash to simulate content change
                    'two.js' => 'xxx13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b', // incorrect hash to simulate content change
                    'three.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
                ],
                'expected-writes' => [
                    'bundles/administration/one.js' => 'AdminBundle/Resources/public/one.js',
                    'bundles/administration/two.js' => 'AdminBundle/Resources/public/two.js',
                ],
                'expected-deletes' => [],
            ],
        ];
    }

    /**
     * @dataProvider adminFilesProvider
     *
     * @param array<string, string> $manifest
     * @param array<string, string> $expectedWrites
     * @param array<string> $expectedDeletes
     */
    public function testCopyAssetsFromAdminBundle(array $manifest, array $expectedWrites, array $expectedDeletes): void
    {
        ksort($manifest);
        $bundle = new Administration();

        $kernel = $this->createMock(KernelInterface::class);
        $kernel
            ->method('getBundle')
            ->with('AdministrationBundle')
            ->willReturn($bundle);

        $filesystem = $this->createMock(FilesystemOperator::class);
        $assetService = new AssetService(
            $filesystem,
            $kernel,
            new StaticKernelPluginLoader($this->createMock(ClassLoader::class)),
            $this->createMock(CacheInvalidator::class),
            $this->createMock(AbstractAppLoader::class),
            new ParameterBag()
        );

        $adapter = new MemoryFilesystemAdapter();
        $config = new Config();

        $adapter->write('asset-manifest.json', (string) json_encode(['administration' => $manifest]), $config);

        $filesystem
            ->expects(static::once())
            ->method('fileExists')
            ->with('asset-manifest.json')
            ->willReturn(true);

        $filesystem
            ->expects(static::once())
            ->method('read')
            ->with('asset-manifest.json')
            ->willReturn($adapter->read('asset-manifest.json'));

        $filesystem
            ->expects(static::exactly(\count($expectedWrites)))
            ->method('writeStream')
            ->willReturnCallback(function (string $path, $stream) use ($expectedWrites) {
                static::assertIsResource($stream);
                $meta = stream_get_meta_data($stream);

                $local = $expectedWrites[$path];
                unset($expectedWrites[$path]);

                static::assertEquals(__DIR__ . '/../_fixtures/' . $local, $meta['uri']);

                return true;
            });

        $filesystem
            ->expects(static::exactly(\count($expectedDeletes)))
            ->method('delete')
            ->with(static::callback(function (string $path) use ($expectedDeletes) {
                return $path === array_pop($expectedDeletes);
            }));

        $expectedManifestFiles = [
            'one.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
            'static/js/app.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
            'three.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
            'two.js' => '13b896d551a100401b0d3982e0729efc2e8d7aeb09a36c0a51e48ec2bd15ea8b',
        ];
        ksort($expectedManifestFiles);

        $filesystem
            ->expects(empty($expectedWrites) && empty($expectedDeletes) ? static::never() : static::once())
            ->method('write')
            ->with(
                'asset-manifest.json',
                json_encode(['administration' => $expectedManifestFiles], \JSON_PRETTY_PRINT)
            );

        $assetService->copyAssetsFromBundle('AdministrationBundle');
    }

    private function getBundle(): ExampleBundle
    {
        return new ExampleBundle(true, __DIR__ . '/_fixtures/ExampleBundle');
    }
}

/**
 * @internal
 */
class Administration extends ShopwareAdministration
{
    public function getPath(): string
    {
        return __DIR__ . '/../_fixtures/AdminBundle';
    }
}
