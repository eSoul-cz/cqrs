<?php

declare(strict_types=1);

namespace Esoul\Cqrs\Helpers;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use RegexIterator;
use RuntimeException;

/**
 * Simple helper class to discover classes in a given directory
 */
final class Discovery
{
    /** @var class-string[] */
    private array $attributes = [];

    /** @var class-string[] */
    private array $interfaces = [];

    private readonly string $directory;

    /** @var non-empty-string|null */
    private static ?string $cacheDirectory = null;

    public function __construct(
        string $directory,
        private readonly string $rootNamespace = '\\App',
    ) {
        if (str_starts_with($directory, '/')) {
            $this->directory = $directory; // Absolute path
        } else {
            $rootDir = dirname(__DIR__, 3);
            $this->directory = $rootDir . '/' . $directory;
        }
    }

    /**
     * Set the cache directory for discovered classes.
     *
     * If a cache key is provided when calling get(), the results will be cached in this directory and reused if the source files have not changed.
     *
     * @param  non-empty-string|null  $cacheDirectory
     */
    public static function setCacheDirectory(?string $cacheDirectory): void
    {
        if (is_string($cacheDirectory) && !is_dir($cacheDirectory) && !mkdir($cacheDirectory, 0755, true) && !is_dir($cacheDirectory)) {
            throw new RuntimeException(sprintf('Failed to create "%s" was not created', $cacheDirectory));
        }

        if ($cacheDirectory !== null) {
            // Ensure cache directory path does not end with a slash
            $cacheDirectory = rtrim($cacheDirectory, "\\/ \n\r\t\v\0");
            assert(!empty($cacheDirectory), 'Cache directory cannot be empty');
        }

        self::$cacheDirectory = $cacheDirectory;
    }

    /**
     * Filter classes containing the given attribute
     *
     * @param  class-string  $attribute
     * @return $this
     */
    public function withAttribute(string $attribute): Discovery
    {
        $this->attributes[] = $attribute;

        return $this;
    }

    /**
     * Filter classes implementing the given interface
     *
     * @param  class-string  $interface
     * @return $this
     */
    public function implements(string $interface): Discovery
    {
        $this->interfaces[] = $interface;

        return $this;
    }

    /**
     * @param  non-empty-string|null  $cacheKey
     * @return class-string[]
     *
     * @throws ReflectionException
     */
    public function get(?string $cacheKey = null): array
    {
        ['time' => $time, 'classes' => $classes] = $this->findClasses($this->directory);
        $cacheFile = null;
        if ($cacheKey !== null && self::$cacheDirectory !== null) {
            // Cache file name is hashed to avoid issues with special characters and length limits
            $cacheFile = self::$cacheDirectory . '/discovery_' . md5($cacheKey) . '.php';
            if (file_exists($cacheFile) && filemtime($cacheFile) >= $time) {
                /** @var class-string[] $cacheData */
                $cacheData = require $cacheFile; // Load cached classes

                return $cacheData;
            }
        }

        $filteredClasses = array_values(
            array_filter($classes, function ($class) {
                $reflection = new ReflectionClass($class);

                // Filter attributes
                if (array_any($this->attributes, fn ($attribute) => count($reflection->getAttributes($attribute, ReflectionAttribute::IS_INSTANCEOF)) === 0)) {
                    return false;
                }

                // Filter interfaces
                if (array_any($this->interfaces, fn ($interface) => !$reflection->implementsInterface($interface))) {
                    return false;
                }

                return true;
            })
        );

        if ($cacheFile !== null) {
            // Cache the filtered classes
            file_put_contents($cacheFile, '<?php return ' . var_export($filteredClasses, true) . ';');
        }

        return $filteredClasses;
    }

    /**
     * @param  non-empty-string  $directory
     * @return array{time: int, classes: class-string[]}
     */
    private function findClasses(string $directory): array
    {
        $directoryIterator = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
        $iteratorIterator = new RecursiveIteratorIterator($directoryIterator);
        $iterator = new RegexIterator(
            $iteratorIterator,
            '/^.+\.php$/i',
            RecursiveRegexIterator::GET_MATCH
        );

        $latestUpdate = 0;
        $classes = [];
        /** @var array{0:string} $file */
        foreach ($iterator as $file) {
            // Normalize file path to class name
            $filePath = $file[0];
            $relativePath = str_replace($this->directory . '/', '', $filePath);
            $className = ltrim($this->rootNamespace . '\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath), '\\');
            if (!class_exists($className)) {
                continue;
            }
            $classes[] = $className;
            $fileMTime = filemtime($filePath);
            if ($fileMTime !== false && $fileMTime > $latestUpdate) {
                $latestUpdate = $fileMTime;
            }
        }

        return ['time' => $latestUpdate, 'classes' => $classes];
    }
}
