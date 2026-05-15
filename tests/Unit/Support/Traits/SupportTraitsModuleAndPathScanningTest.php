<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Traits;

use LaraArabDev\FilamentGatekeeper\Support\Traits\InteractsWithExclusions;
use LaraArabDev\FilamentGatekeeper\Support\Traits\InteractsWithModuleDiscovery;
use LaraArabDev\FilamentGatekeeper\Support\Traits\InteractsWithPathScanning;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

// ---------------------------------------------------------------------------
// Concrete helper classes for InteractsWithModuleDiscovery
// ---------------------------------------------------------------------------

/**
 * Concrete implementation that exercises InteractsWithModuleDiscovery.
 * Returns file basenames from scanned directories.
 */
class BranchModuleDiscoverer
{
    use InteractsWithModuleDiscovery;

    public function runDiscoverFromModules(string $pattern): array
    {
        return $this->discoverFromModules($pattern);
    }

    public function runIsModuleDiscoveryEnabled(): bool
    {
        return $this->isModuleDiscoveryEnabled();
    }

    protected function scanDirectory(string $directory, string $pathPattern): array
    {
        $results = [];

        foreach (glob($directory . '/*') ?: [] as $file) {
            if (is_file($file)) {
                $results[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }

        return $results;
    }
}

// ---------------------------------------------------------------------------
// Concrete helper classes for InteractsWithExclusions
// ---------------------------------------------------------------------------

/**
 * Concrete implementation that exercises InteractsWithExclusions with exclusions set.
 */
class BranchExclusionClass
{
    use InteractsWithExclusions;

    private array $exclusionList;

    public function __construct(array $exclusionList = [])
    {
        $this->exclusionList = $exclusionList;
    }

    public function runFilterExclusions(array $items, array $exclusions): array
    {
        return array_values($this->filterExclusions($items, $exclusions));
    }

    protected function getExclusionList(): array
    {
        return $this->exclusionList;
    }
}

// ---------------------------------------------------------------------------
// Concrete helper classes for InteractsWithPathScanning
// ---------------------------------------------------------------------------

/**
 * Concrete implementation that exercises InteractsWithPathScanning.
 * Uses glob to find PHP files inside a directory.
 */
class BranchPathScanner
{
    use InteractsWithPathScanning;

    public function runScanPath(string $pattern): array
    {
        return $this->scanPath($pattern);
    }

    public function runGetClassFromFile(string $filePath, string $pattern): ?string
    {
        return $this->getClassFromFile($filePath, $pattern);
    }

    protected function scanDirectory(string $directory, string $pathPattern): array
    {
        $results = [];

        foreach (glob($directory . '/*.php') ?: [] as $file) {
            $class = $this->getClassFromFile($file, $pathPattern);

            if ($class !== null) {
                $results[] = $class;
            }
        }

        return $results;
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

/**
 * Branch coverage tests for the Support/Traits:
 *  - InteractsWithModuleDiscovery: discoverFromModules() with actual dirs, isModuleDiscoveryEnabled()
 *  - InteractsWithExclusions: filterExclusions() with actual exclusions
 *  - InteractsWithPathScanning: scanPath() with glob patterns, getClassFromFile() edge cases
 */
class SupportTraitsModuleAndPathScanningTest extends TestCase
{
    private string $tempDir = '';

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        if ($this->tempDir !== '' && is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------
    // InteractsWithModuleDiscovery – discoverFromModules()
    // ---------------------------------------------------------------------------

    /** @test */
    public function module_discovery_returns_empty_when_modules_path_missing(): void
    {
        config()->set('gatekeeper.modules.path', '/totally/non/existent/path_' . uniqid());

        $discoverer = new BranchModuleDiscoverer();
        $result = $discoverer->runDiscoverFromModules('{module}/Models');

        $this->assertSame([], $result);
    }

    /** @test */
    public function module_discovery_discovers_files_from_module_subdirectory(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/gatekeeper_traits_branch_' . uniqid();
        $modelsDir = $this->tempDir . '/Blog/Models';
        mkdir($modelsDir, 0755, true);
        file_put_contents($modelsDir . '/Post.php', '<?php class Post {}');
        file_put_contents($modelsDir . '/Tag.php', '<?php class Tag {}');

        config()->set('gatekeeper.modules.path', $this->tempDir);

        $discoverer = new BranchModuleDiscoverer();
        $result = $discoverer->runDiscoverFromModules('{module}/Models');

        $this->assertContains('Post', $result);
        $this->assertContains('Tag', $result);
    }

    /** @test */
    public function module_discovery_skips_modules_without_matching_subdirectory(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/gatekeeper_traits_branch2_' . uniqid();
        mkdir($this->tempDir . '/Blog', 0755, true); // no 'Models' sub-dir

        config()->set('gatekeeper.modules.path', $this->tempDir);

        $discoverer = new BranchModuleDiscoverer();
        $result = $discoverer->runDiscoverFromModules('{module}/Models');

        $this->assertSame([], $result);
    }

    /** @test */
    public function is_module_discovery_enabled_returns_false_by_default(): void
    {
        config()->set('gatekeeper.modules.enabled', false);

        $discoverer = new BranchModuleDiscoverer();

        $this->assertFalse($discoverer->runIsModuleDiscoveryEnabled());
    }

    /** @test */
    public function is_module_discovery_enabled_returns_true_when_set(): void
    {
        config()->set('gatekeeper.modules.enabled', true);

        $discoverer = new BranchModuleDiscoverer();

        $this->assertTrue($discoverer->runIsModuleDiscoveryEnabled());
    }

    // ---------------------------------------------------------------------------
    // InteractsWithExclusions – filterExclusions() with actual exclusions
    // ---------------------------------------------------------------------------

    /** @test */
    public function filter_exclusions_removes_matching_items(): void
    {
        $obj = new BranchExclusionClass();

        $items = ['UserResource', 'PostResource', 'CommentResource'];
        $exclusions = ['App\\Filament\\Resources\\PostResource'];

        $result = $obj->runFilterExclusions($items, $exclusions);

        $this->assertContains('UserResource', $result);
        $this->assertNotContains('PostResource', $result);
        $this->assertContains('CommentResource', $result);
    }

    /** @test */
    public function filter_exclusions_returns_all_when_empty_exclusions(): void
    {
        $obj = new BranchExclusionClass();

        $items = ['UserResource', 'PostResource'];
        $result = $obj->runFilterExclusions($items, []);

        $this->assertSame($items, $result);
    }

    /** @test */
    public function filter_exclusions_removes_multiple_exclusions(): void
    {
        $obj = new BranchExclusionClass();

        $items = ['Alpha', 'Beta', 'Gamma'];
        $exclusions = ['SomeNs\\Beta', 'SomeNs\\Gamma'];

        $result = $obj->runFilterExclusions($items, $exclusions);

        $this->assertSame(['Alpha'], $result);
    }

    /** @test */
    public function filter_exclusions_returns_empty_when_all_excluded(): void
    {
        $obj = new BranchExclusionClass();

        $items = ['Alpha'];
        $exclusions = ['Ns\\Alpha'];

        $result = $obj->runFilterExclusions($items, $exclusions);

        $this->assertEmpty($result);
    }

    /** @test */
    public function filter_exclusions_returns_empty_for_empty_items(): void
    {
        $obj = new BranchExclusionClass();

        $result = $obj->runFilterExclusions([], ['SomeClass']);

        $this->assertEmpty($result);
    }

    // ---------------------------------------------------------------------------
    // InteractsWithPathScanning – scanPath() with glob patterns
    // ---------------------------------------------------------------------------

    /** @test */
    public function scan_path_returns_empty_for_non_existent_directory(): void
    {
        $scanner = new BranchPathScanner();

        $result = $scanner->runScanPath('totally/non/existent/path_xyz_' . uniqid());

        $this->assertSame([], $result);
    }

    /** @test */
    public function scan_path_scans_an_existing_directory(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/gatekeeper_scan_branch_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        file_put_contents($this->tempDir . '/MyClass.php', '<?php' . PHP_EOL . 'namespace App\\Models;' . PHP_EOL . 'class MyClass {}');

        $scanner = new class($this->tempDir) extends BranchPathScanner {
            public function __construct(private string $dir) {}

            public function runScanDirectory(string $pattern): array
            {
                return $this->scanDirectory($this->dir, $pattern);
            }
        };

        $result = $scanner->runScanDirectory('app/Models');

        $this->assertContains('App\\Models\\MyClass', $result);
    }

    /** @test */
    public function scan_path_with_glob_pattern_finds_directories(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/gatekeeper_glob_branch_' . uniqid();
        $moduleDir = $this->tempDir . '/modules/Blog/Filament/Resources';
        mkdir($moduleDir, 0755, true);
        file_put_contents($moduleDir . '/PostResource.php', '<?php' . PHP_EOL . 'namespace Blog;' . PHP_EOL . 'class PostResource {}');

        // Use a path-relative approach: scanPath uses base_path(), so we test via scanDirectory directly
        $scanner = new class($moduleDir) extends BranchPathScanner {
            public function __construct(private string $dir) {}

            public function runScan(): array
            {
                return $this->scanDirectory($this->dir, '');
            }
        };

        $result = $scanner->runScan();

        $this->assertContains('Blog\\PostResource', $result);
    }

    // ---------------------------------------------------------------------------
    // InteractsWithPathScanning – getClassFromFile() edge cases
    // ---------------------------------------------------------------------------

    /** @test */
    public function get_class_from_file_returns_null_for_empty_file(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/gatekeeper_class_branch_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $file = $this->tempDir . '/empty.php';
        file_put_contents($file, '');

        $scanner = new BranchPathScanner();
        $result = $scanner->runGetClassFromFile($file, '');

        $this->assertNull($result);
    }

    /** @test */
    public function get_class_from_file_returns_null_when_no_class_declaration(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/gatekeeper_class_branch2_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $file = $this->tempDir . '/helpers.php';
        file_put_contents($file, '<?php function myHelper() { return true; }');

        $scanner = new BranchPathScanner();
        $result = $scanner->runGetClassFromFile($file, '');

        $this->assertNull($result);
    }

    /** @test */
    public function get_class_from_file_returns_class_without_namespace(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/gatekeeper_class_branch3_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $file = $this->tempDir . '/Standalone.php';
        file_put_contents($file, '<?php' . PHP_EOL . 'class Standalone {}');

        $scanner = new BranchPathScanner();
        $result = $scanner->runGetClassFromFile($file, '');

        $this->assertSame('Standalone', $result);
    }

    /** @test */
    public function get_class_from_file_returns_fully_qualified_class_with_namespace(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/gatekeeper_class_branch4_' . uniqid();
        mkdir($this->tempDir, 0755, true);
        $file = $this->tempDir . '/MyModel.php';
        file_put_contents($file, '<?php' . PHP_EOL . 'namespace App\\Models;' . PHP_EOL . 'class MyModel {}');

        $scanner = new BranchPathScanner();
        $result = $scanner->runGetClassFromFile($file, 'app/Models');

        $this->assertSame('App\\Models\\MyModel', $result);
    }

    // ---------------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------------

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;

            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
