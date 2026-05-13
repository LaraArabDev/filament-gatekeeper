<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Traits;

use LaraArabDev\FilamentGatekeeper\Support\Traits\InteractsWithPathScanning;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class ConcretePathScanner
{
    use InteractsWithPathScanning;

    public function scan(string $pattern): array
    {
        return $this->scanPath($pattern);
    }

    public function extractClass(string $filePath, string $pattern): ?string
    {
        return $this->getClassFromFile($filePath, $pattern);
    }

    protected function scanDirectory(string $directory, string $pathPattern): array
    {
        $results = [];
        foreach (glob($directory . '/*.php') ?: [] as $file) {
            $class = $this->getClassFromFile($file, $pathPattern);
            if ($class) {
                $results[] = $class;
            }
        }
        return $results;
    }
}

class InteractsWithPathScanningTest extends TestCase
{
    private ConcretePathScanner $scanner;
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new ConcretePathScanner();
        $this->tmpDir = sys_get_temp_dir() . '/gatekeeper_scan_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        array_map('unlink', glob($this->tmpDir . '/*.php') ?: []);
        if (is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
    }

    /** @test */
    public function it_extracts_class_with_namespace_from_file(): void
    {
        $file = $this->tmpDir . '/TestClass.php';
        file_put_contents($file, '<?php' . PHP_EOL . 'namespace App\Models;' . PHP_EOL . 'class TestClass {}');

        $result = $this->scanner->extractClass($file, 'app/Models');

        $this->assertSame('App\Models\TestClass', $result);
    }

    /** @test */
    public function it_extracts_class_without_namespace_from_file(): void
    {
        $file = $this->tmpDir . '/Standalone.php';
        file_put_contents($file, '<?php' . PHP_EOL . 'class Standalone {}');

        $result = $this->scanner->extractClass($file, '');

        $this->assertSame('Standalone', $result);
    }

    /** @test */
    public function it_returns_null_for_file_with_no_class(): void
    {
        $file = $this->tmpDir . '/helpers.php';
        file_put_contents($file, '<?php' . PHP_EOL . 'function helper() {}');

        $result = $this->scanner->extractClass($file, '');

        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_for_empty_file(): void
    {
        $file = $this->tmpDir . '/empty.php';
        file_put_contents($file, '');

        $result = $this->scanner->extractClass($file, '');

        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_empty_for_non_existent_directory(): void
    {
        $result = $this->scanner->scan('non/existent/path/that/does/not/exist');
        $this->assertSame([], $result);
    }

    /** @test */
    public function it_scans_directory_and_finds_classes(): void
    {
        file_put_contents(
            $this->tmpDir . '/MyModel.php',
            '<?php' . PHP_EOL . 'namespace App\Models;' . PHP_EOL . 'class MyModel {}'
        );

        // Use the scanDirectory indirectly via scanPath by pointing to the tmp dir
        $scanner = new class($this->tmpDir) extends ConcretePathScanner {
            public function __construct(private string $dir) {}
            public function scan(string $pattern): array
            {
                return $this->scanDirectory($this->dir, $pattern);
            }
        };

        $result = $scanner->scan('app/Models');

        $this->assertContains('App\Models\MyModel', $result);
    }
}
