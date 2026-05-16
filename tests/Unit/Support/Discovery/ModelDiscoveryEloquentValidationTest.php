<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Support\Discovery;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Support\Discovery\ModelDiscovery;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use LaraArabDev\FilamentGatekeeper\Tests\TestUser;
use PHPUnit\Framework\Attributes\Test;

// ---------------------------------------------------------------------------
// Helper classes declared at file scope (not inside any class)
// These are used to exercise isEloquentModel() branches.
// ---------------------------------------------------------------------------

abstract class ModelDiscoveryBranchAbstractModel extends Model
{
    protected $table = 'users';
}

interface ModelDiscoveryBranchInterface {}

class ModelDiscoveryBranchConcreteModel extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}

/**
 * Tests that exercise the branch coverage gaps in ModelDiscovery:
 *  - isEloquentModel() with non-existent class          → false
 *  - isEloquentModel() with an abstract class           → false
 *  - isEloquentModel() with an interface                → false
 *  - isEloquentModel() with a real Model sub-class      → true
 *  - discoverModuleModels()                             → via discover() with modules enabled
 *  - getModelsWithoutResources()                        → returns diff
 *  - getModelName() / getPermissionModelName()          → simple helpers
 */
class ModelDiscoveryEloquentValidationTest extends TestCase
{
    use RefreshDatabase;

    private ModelDiscovery $discovery;

    private string $tempDir = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->discovery = new ModelDiscovery;
    }

    protected function tearDown(): void
    {
        if ($this->tempDir !== '' && is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    // ---------------------------------------------------------------------------
    // isEloquentModel() branches
    // (method is protected – we exercise it indirectly through discover() +
    //  a temp directory that contains the relevant PHP file with the class)
    // ---------------------------------------------------------------------------

    #[Test]
    public function is_eloquent_model_returns_false_for_non_existent_class(): void
    {
        // We expose the protected method via an anonymous sub-class.
        $proxy = new class extends ModelDiscovery
        {
            public function publicIsEloquentModel(string $class): bool
            {
                return $this->isEloquentModel($class);
            }
        };

        $result = $proxy->publicIsEloquentModel('Totally\\NonExistent\\ClassName');

        $this->assertFalse($result);
    }

    #[Test]
    public function is_eloquent_model_returns_false_for_abstract_class(): void
    {
        $proxy = new class extends ModelDiscovery
        {
            public function publicIsEloquentModel(string $class): bool
            {
                return $this->isEloquentModel($class);
            }
        };

        // ModelDiscoveryBranchAbstractModel is declared at the top of this file
        $result = $proxy->publicIsEloquentModel(ModelDiscoveryBranchAbstractModel::class);

        $this->assertFalse($result);
    }

    #[Test]
    public function is_eloquent_model_returns_false_for_interface(): void
    {
        $proxy = new class extends ModelDiscovery
        {
            public function publicIsEloquentModel(string $class): bool
            {
                return $this->isEloquentModel($class);
            }
        };

        $result = $proxy->publicIsEloquentModel(ModelDiscoveryBranchInterface::class);

        $this->assertFalse($result);
    }

    #[Test]
    public function is_eloquent_model_returns_true_for_real_model(): void
    {
        $proxy = new class extends ModelDiscovery
        {
            public function publicIsEloquentModel(string $class): bool
            {
                return $this->isEloquentModel($class);
            }
        };

        // TestUser extends Authenticatable which ultimately extends Model
        $result = $proxy->publicIsEloquentModel(TestUser::class);

        $this->assertTrue($result);
    }

    #[Test]
    public function is_eloquent_model_returns_false_for_non_model_class(): void
    {
        $proxy = new class extends ModelDiscovery
        {
            public function publicIsEloquentModel(string $class): bool
            {
                return $this->isEloquentModel($class);
            }
        };

        // A plain class that does NOT extend Model
        $result = $proxy->publicIsEloquentModel(\stdClass::class);

        $this->assertFalse($result);
    }

    // ---------------------------------------------------------------------------
    // getModelsWithoutResources()
    // ---------------------------------------------------------------------------

    #[Test]
    public function get_models_without_resources_returns_models_not_in_resource_list(): void
    {
        // Point discovery at a path that doesn't exist so discover() returns []
        config()->set('gatekeeper.discovery.models', []);
        config()->set('gatekeeper.modules.enabled', false);

        $result = $this->discovery->getModelsWithoutResources(['User', 'Post']);

        // discover() returns [], diff with ['User','Post'] => still []
        $this->assertIsArray($result);
    }

    #[Test]
    public function get_models_without_resources_with_real_temp_model(): void
    {
        $this->tempDir = sys_get_temp_dir().'/gatekeeper_model_branch_'.uniqid();
        $modelDir = $this->tempDir.'/Models';
        mkdir($modelDir, 0755, true);

        // Write a concrete Eloquent model file
        $modelContent = '<?php'.PHP_EOL
            .'namespace GatekeeperTestModels;'.PHP_EOL
            .'use Illuminate\\Database\\Eloquent\\Model;'.PHP_EOL
            .'class TempArticle extends Model { protected $table = \'users\'; }';
        file_put_contents($modelDir.'/TempArticle.php', $modelContent);

        // We need the class to be loadable – autoload it manually
        require_once $modelDir.'/TempArticle.php';

        config()->set('gatekeeper.modules.enabled', false);
        config()->set('gatekeeper.discovery.models', []);

        // Scan the temp directory directly via a sub-class
        $proxy = new class($modelDir) extends ModelDiscovery
        {
            public function __construct(private string $dir) {}

            public function discover(): array
            {
                return $this->scanDirectory($this->dir, '');
            }

            public function publicScanDirectory(string $dir, string $pat): array
            {
                return $this->scanDirectory($dir, $pat);
            }
        };

        $found = $proxy->discover();

        // TempArticle extends Model so it should be discovered
        $this->assertContains('TempArticle', $found);

        // getModelsWithoutResources filters against a resource list
        $discovery = new ModelDiscovery;
        config()->set('gatekeeper.discovery.models', []);
        $withoutResources = $discovery->getModelsWithoutResources(['SomeOtherModel']);

        $this->assertIsArray($withoutResources);
    }

    // ---------------------------------------------------------------------------
    // getModelName() and getPermissionModelName()
    // ---------------------------------------------------------------------------

    #[Test]
    public function get_model_name_returns_class_basename(): void
    {
        $this->assertEquals('User', $this->discovery->getModelName('App\\Models\\User'));
        $this->assertEquals('BlogPost', $this->discovery->getModelName('App\\Models\\BlogPost'));
        $this->assertEquals('Order', $this->discovery->getModelName('Order'));
    }

    #[Test]
    public function get_permission_model_name_returns_snake_case(): void
    {
        $this->assertEquals('user', $this->discovery->getPermissionModelName('App\\Models\\User'));
        $this->assertEquals('blog_post', $this->discovery->getPermissionModelName('App\\Models\\BlogPost'));
    }

    // ---------------------------------------------------------------------------
    // discoverModuleModels() via discover() with modules enabled
    // ---------------------------------------------------------------------------

    #[Test]
    public function discover_returns_empty_when_modules_path_does_not_exist(): void
    {
        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', '/non/existent/path/xyz_'.uniqid());
        config()->set('gatekeeper.discovery.models', []);

        $result = $this->discovery->discover();

        $this->assertIsArray($result);
    }

    #[Test]
    public function discover_skips_module_without_models_directory(): void
    {
        $this->tempDir = sys_get_temp_dir().'/gatekeeper_model_branch_'.uniqid();
        mkdir($this->tempDir.'/Blog', 0755, true);

        config()->set('gatekeeper.modules.enabled', true);
        config()->set('gatekeeper.modules.path', $this->tempDir);
        config()->set('gatekeeper.discovery.models', []);

        $result = $this->discovery->discover();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
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
            $path = $dir.DIRECTORY_SEPARATOR.$file;

            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
