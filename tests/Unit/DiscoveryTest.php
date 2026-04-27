<?php

declare(strict_types=1);

namespace Tests\Unit;

use AllowDynamicProperties;
use Esoul\Cqrs\Helpers\Discovery;
use JsonSerializable;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\DiscoveryClasses\ClassA;
use Tests\Stubs\DiscoveryClasses\ClassB;
use Tests\Stubs\DiscoveryClasses\Sub\ClassC;
use Tests\Stubs\DiscoveryClasses\Sub\SubSub\ClassD;
use Tests\Stubs\DiscoveryClasses\Sub\SubSub\ClassE;

class DiscoveryTest extends TestCase
{
    private ?string $cacheDirectory = null;

    protected function tearDown(): void
    {
        Discovery::setCacheDirectory(null);

        if ($this->cacheDirectory !== null && is_dir($this->cacheDirectory)) {
            array_map('unlink', glob($this->cacheDirectory . '/*') ?: []);
            rmdir($this->cacheDirectory);
        }

        $this->cacheDirectory = null;

        parent::tearDown();
    }

    public function test_discovery_in_dir(): void
    {
        $discovery = new Discovery(
            dirname(__DIR__) . '/Stubs/DiscoveryClasses',
            '\\Tests\\Stubs\\DiscoveryClasses',
        );

        $classes = $discovery->get();

        $this->assertCount(5, $classes);
        foreach ([
            ClassA::class,
            ClassB::class,
            ClassC::class,
            ClassD::class,
            ClassE::class,
        ] as $expectedClass) {
            $this->assertContains($expectedClass, $classes);
        }
    }

    public function test_discovery_in_dir_with_interface(): void
    {
        $discovery = new Discovery(
            dirname(__DIR__) . '/Stubs/DiscoveryClasses',
            '\\Tests\\Stubs\\DiscoveryClasses',
        );

        $classes = $discovery
            ->implements(JsonSerializable::class)
            ->get();

        $this->assertCount(2, $classes);
        foreach ([
            ClassC::class,
            ClassE::class,
        ] as $expectedClass) {
            $this->assertContains($expectedClass, $classes);
        }
    }

    public function test_discovery_in_dir_with_attribute(): void
    {
        $discovery = new Discovery(
            dirname(__DIR__) . '/Stubs/DiscoveryClasses',
            '\\Tests\\Stubs\\DiscoveryClasses',
        );

        $classes = $discovery
            ->withAttribute(AllowDynamicProperties::class)
            ->get();

        $this->assertCount(2, $classes);
        foreach ([
            ClassD::class,
            ClassE::class,
        ] as $expectedClass) {
            $this->assertContains($expectedClass, $classes);
        }
    }

    public function test_discovery_in_dir_with_interface_and_attribute(): void
    {
        $discovery = new Discovery(
            dirname(__DIR__) . '/Stubs/DiscoveryClasses',
            '\\Tests\\Stubs\\DiscoveryClasses',
        );

        $classes = $discovery
            ->implements(JsonSerializable::class)
            ->withAttribute(AllowDynamicProperties::class)
            ->get();

        $this->assertCount(1, $classes);
        foreach ([
            ClassE::class,
        ] as $expectedClass) {
            $this->assertContains($expectedClass, $classes);
        }
    }

    public function test_discovery_uses_cached_classes_when_cache_is_fresh(): void
    {
        $discovery = $this->createDiscovery()
            ->implements(JsonSerializable::class);

        $cacheFile = $this->configureCacheDirectory() . '/discovery_' . md5('json-serializable') . '.php';

        $classes = $discovery->get('json-serializable');

        $this->assertEqualsCanonicalizing([ClassC::class, ClassE::class], $classes);
        $this->assertFileExists($cacheFile);

        file_put_contents($cacheFile, '<?php return ' . var_export([ClassA::class], true) . ';');

        $this->assertSame([ClassA::class], $discovery->get('json-serializable'));
    }

    public function test_discovery_rebuilds_cache_when_source_is_newer_than_cache(): void
    {
        $discovery = $this->createDiscovery()
            ->implements(JsonSerializable::class);

        $cacheFile = $this->configureCacheDirectory() . '/discovery_' . md5('json-serializable') . '.php';
        $sourceFile = dirname(__DIR__) . '/Stubs/DiscoveryClasses/Sub/ClassC.php';
        $originalSourceMTime = filemtime($sourceFile);

        $classes = $discovery->get('json-serializable');

        $this->assertEqualsCanonicalizing([ClassC::class, ClassE::class], $classes);
        $this->assertFileExists($cacheFile);

        $freshTime = time() + 5;
        touch($sourceFile, $freshTime);
        file_put_contents($cacheFile, '<?php return ' . var_export([ClassA::class], true) . ';');
        touch($cacheFile, $freshTime - 1);

        try {
            $this->assertEqualsCanonicalizing([ClassC::class, ClassE::class], $discovery->get('json-serializable'));
        } finally {
            if ($originalSourceMTime !== false) {
                touch($sourceFile, $originalSourceMTime);
            }
        }
    }

    private function createDiscovery(): Discovery
    {
        return new Discovery(
            dirname(__DIR__) . '/Stubs/DiscoveryClasses',
            '\\Tests\\Stubs\\DiscoveryClasses',
        );
    }

    private function configureCacheDirectory(): string
    {
        $this->cacheDirectory = sys_get_temp_dir() . '/discovery-test-' . bin2hex(random_bytes(8));
        Discovery::setCacheDirectory($this->cacheDirectory);

        return $this->cacheDirectory;
    }
}
