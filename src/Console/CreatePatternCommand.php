<?php

declare(strict_types=1);

namespace LongAoDai\Repository\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Component;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Abstract command class for creating Repository and Service pattern files.
 *
 * This command generates:
 * - Repository Interface and Implementation
 * - Service Interface and Implementation
 * - Updates corresponding Service Providers
 *
 * @package LongAoDai\Repository
 * @author  vochilong<vochilong.work@gmail.com>
 *
 * @property Application $laravel The Laravel application instance
 * @property Components $components
 */
class CreatePatternCommand extends Command
{
    /**
     * The console command signature.
     */
    protected $signature = 'make:repository {name : The name of the repository} 
                           {--model= : The model class name}
                           {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new repository and service pattern with interfaces';

    /**
     * Default path constants for services and repositories.
     */
    private const PATH_SERVICE = 'Services';
    private const PATH_REPOSITORY = 'Repositories';

    /**
     * File type constants for stub selection.
     */
    private const TYPE_CLASS = 'class';
    private const TYPE_INTERFACE = 'interface';

    /**
     * Stub file paths mapping.
     *
     * @var array
     */
    protected array $stubs = [];

    /**
     * The namespace for repository classes and interfaces.
     */
    private string $repositoryNamespace;

    /**
     * The namespace for service classes and interfaces.
     */
    private string $serviceNamespace;

    /**
     * Path to repository directory.
     */
    private string $pathDirectoryRepository;

    /**
     * Path to service directory.
     */
    private string $pathDirectoryService;
    private Filesystem $files;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files The filesystem instance for file operations
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
        parent::__construct();
    }

    /**
     * Initialize stub file paths for different pattern types.
     *
     * @return void
     */
    protected function initializeStubs(): void
    {
        $stubPath = __DIR__ . '/stubs/';

        $this->stubs = [
            'repository_interface' => $stubPath . 'interface.repository.stub',
            'repository_implement' => $stubPath . 'implement.repository.stub',
            'service_interface' => $stubPath . 'interface.service.stub',
            'service_implement' => $stubPath . 'implement.service.stub',
            'provider_repository' => $stubPath . 'provider.repository.stub',
            'provider_service' => $stubPath . 'provider.service.stub',
        ];
    }

    /**
     * Execute the console command.
     *
     * @return int Command exit code (0 for success, 1 for failure)
     */
    public function handle(): int
    {
        try {
            $this->initializeStubs();
            $this->validateInput();
            $this->setupDirectoriesAndNamespaces();

            if (!$this->createPatternFiles()) {
                return Command::FAILURE;
            }

            $this->updateServiceProviders();
            $this->runComposerDumpAutoload();
            $this->displaySuccessMessages();

            return Command::SUCCESS;

        } catch (InvalidArgumentException $e) {
            $this->error("Validation Error: {$e->getMessage()}");
            return Command::FAILURE;
        } catch (Exception $e) {
            $this->error("Unexpected Error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Validate user input for repository name format.
     *
     * @return void
     * @throws InvalidArgumentException When input validation fails
     */
    private function validateInput(): void
    {
        $name = $this->getNameInput();

        if (empty($name)) {
            throw new InvalidArgumentException('Repository name cannot be empty');
        }

        if (!preg_match('/^[A-Z][A-Za-z0-9_]*$/', $name)) {
            throw new InvalidArgumentException(
                'Repository name must be a valid class name starting with uppercase letter'
            );
        }

        // Validate model name if provided
        if ($modelName = $this->option('model')) {
            if (!preg_match('/^[A-Z][A-Za-z0-9_]*$/', $modelName)) {
                throw new InvalidArgumentException(
                    'Model name must be a valid class name starting with uppercase letter'
                );
            }
        }
    }

    /**
     * Setup directory paths and namespaces for both repositories and services.
     *
     * @return void
     */
    private function setupDirectoriesAndNamespaces(): void
    {
        $this->setNamespaces();
        $this->setDirectoryPaths();
    }

    /**
     * Set separate namespaces for repository and service patterns.
     *
     * @return void
     */
    private function setNamespaces(): void
    {
        $baseNamespace = $this->laravel->getNamespace();
        $name = $this->getNameInput();

        // Repository namespace: App\Repositories\User
        $this->repositoryNamespace = $baseNamespace
            . $this->prepareNamespaceByPath($this->getPathDirectoryRepositoryBase())
            . $name;

        // Service namespace: App\Services\User
        $this->serviceNamespace = $baseNamespace
            . $this->prepareNamespaceByPath($this->getPathDirectoryServiceBase())
            . $name;
    }

    /**
     * Set directory paths for repository and service files.
     *
     * @return void
     */
    private function setDirectoryPaths(): void
    {
        $basePath = $this->laravel['path'];
        $name = $this->getNameInput();

        $this->pathDirectoryRepository = $basePath . '/'
            . $this->getPathDirectoryRepositoryBase()
            . $name;

        $this->pathDirectoryService = $basePath . '/'
            . $this->getPathDirectoryServiceBase()
            . $name;
    }

    /**
     * Update repository and service provider files with new bindings.
     *
     * @return void
     */
    protected function updateServiceProviders(): void
    {
        $providers = [
            'RepositoryServiceProvider' => self::PATH_REPOSITORY,
            'InternalServiceProvider' => self::PATH_SERVICE
        ];

        foreach ($providers as $providerName => $type) {
            $this->updateServiceProvider($providerName, $type);
        }
    }

    /**
     * Update a specific service provider file.
     *
     * @param string $providerName Name of the provider file
     * @param string $type Type of provider (repository or service)
     * @return void
     */
    private function updateServiceProvider(string $providerName, string $type): void
    {
        $providerPath = $this->laravel['path'] . "/Providers/{$providerName}.php";
        $stubKey = $type === self::PATH_REPOSITORY ? 'provider_repository' : 'provider_service';

        $content = $this->files->exists($providerPath)
            ? $this->files->get($providerPath)
            : $this->files->get($this->stubs[$stubKey]);

        $processedContent = $this->prepareStub($content, $type);
        $this->files->put($providerPath, $processedContent);
    }

    /**
     * Create all repository and service pattern files.
     *
     * @return bool True if all files created successfully, false otherwise
     */
    private function createPatternFiles(): bool
    {
        if (!$this->createDirectories()) {
            return false;
        }

        $name = $this->getNameInput();

        $filesToCreate = [
            // Repository files
            [
                'path' => $this->pathDirectoryRepository . "/{$name}EloquentRepository.php",
                'content' => $this->buildFileContent(self::TYPE_CLASS, self::PATH_REPOSITORY)
            ],
            [
                'path' => $this->pathDirectoryRepository . "/{$name}RepositoryInterface.php",
                'content' => $this->buildFileContent(self::TYPE_INTERFACE, self::PATH_REPOSITORY)
            ],
            // Service files
            [
                'path' => $this->pathDirectoryService . "/{$name}Service.php",
                'content' => $this->buildFileContent(self::TYPE_CLASS, self::PATH_SERVICE)
            ],
            [
                'path' => $this->pathDirectoryService . "/{$name}ServiceInterface.php",
                'content' => $this->buildFileContent(self::TYPE_INTERFACE, self::PATH_SERVICE)
            ]
        ];

        foreach ($filesToCreate as $file) {
            $this->files->put($file['path'], $file['content']);
        }

        return true;
    }

    /**
     * Create necessary directories for repositories and services.
     *
     * @return bool True if directories created successfully, false otherwise
     */
    private function createDirectories(): bool
    {
        $directories = [
            $this->pathDirectoryRepository,
            $this->pathDirectoryService
        ];

        foreach ($directories as $directory) {
            if (!$this->makeDirectory($directory)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build file content from stub templates.
     *
     * @param string $type File type (class or interface)
     * @param string $path Pattern path (repository or service)
     * @return string Generated file content
     */
    private function buildFileContent(string $type, string $path): string
    {
        $stubKey = $this->getStubKey($type, $path);
        $stub = $this->files->get($this->stubs[$stubKey]);

        return $this->prepareStub($stub, $path);
    }

    /**
     * Get the appropriate stub key for file generation.
     *
     * @param string $type File type (class or interface)
     * @param string $path Pattern path (repository or service)
     * @return string Stub key for file lookup
     */
    private function getStubKey(string $type, string $path): string
    {
        $prefix = $path === self::PATH_SERVICE ? 'service' : 'repository';
        $suffix = $type === self::TYPE_INTERFACE ? 'interface' : 'implement';

        return "{$prefix}_{$suffix}";
    }

    /**
     * Prepare stub content by replacing placeholders with actual values.
     *
     * @param string $stub Raw stub content
     * @param string $path Pattern path (repository or service)
     * @return string Processed stub content
     */
    private function prepareStub(string $stub, string $path): string
    {
        $replacements = $this->getStubReplacements($path);

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );
    }

    /**
     * Get all placeholder replacements for stub processing.
     *
     * @param string $path Pattern path (repository or service)
     * @return array<string, string> Replacement mappings
     */
    private function getStubReplacements(string $path): array
    {
        $name = $this->getNameInput();

        if ($path === self::PATH_REPOSITORY) {
            $namespace = $this->repositoryNamespace;
            $interfaceName = $name . 'RepositoryInterface';
            $className = $name . 'EloquentRepository';
        } else {
            $namespace = $this->serviceNamespace;
            $interfaceName = $name . 'ServiceInterface';
            $className = $name . 'Service';
        }

        $repositoryInterfaceName = $name . 'RepositoryInterface';

        return [
            '#Namespace' => $namespace,
            '#InterfaceName' => $interfaceName,
            '#ClassName' => $className,

            '#InterfaceProvides' => "{$interfaceName}::class,\n\t\t\t#InterfaceProvides",
            '#Singleton' => "\$this->app->singleton({$interfaceName}::class, {$className}::class);\n\t\t#Singleton",

            '#UseRepositoryInterface' => "use {$this->repositoryNamespace}\\{$repositoryInterfaceName}",
            '#NameRepositoryInterface' => $repositoryInterfaceName,

            '#UserModelNamespace' => $this->getModelNamespace(),
            '#ModelName' => $this->getModelName(),

            '#InterfaceUseNamespace' => "use {$namespace}\\{$interfaceName};\n#InterfaceUseNamespace",
            '#ClassUseNamespace' => "use {$namespace}\\{$className};\n#ClassUseNamespace",
        ];
    }

    /**
     * Get model class name from option or derive from repository name.
     *
     * @return string Model class name with ::class suffix
     */
    private function getModelName(): string
    {
        $modelOption = $this->option('model');

        if ($modelOption) {
            return $modelOption;
        }

        // Remove "Repository" suffix if exists and return as model name
        $name = $this->getNameInput();
        $modelName = Str::endsWith($name, 'Repository')
            ? Str::replaceLast('Repository', '', $name)
            : $name;

        return $modelName;
    }

    /**
     * Get full model namespace for import statements.
     *
     * @return string Full model namespace path
     */
    private function getModelNamespace(): string
    {
        $modelOption = $this->option('model');

        if ($modelOption) {
            return "use App\\Models\\{$modelOption}";
        }

        $name = $this->getNameInput();
        $modelName = Str::endsWith($name, 'Repository')
            ? Str::replaceLast('Repository', '', $name)
            : $name;

        return "use App\\Models\\{$modelName}";
    }

    /**
     * Get sanitized and formatted repository name input.
     *
     * @return string Formatted class name in StudlyCase
     */
    protected function getNameInput(): string
    {
        return Str::studly(trim((string)$this->argument('name')));
    }

    /**
     * Create a directory with proper permissions and error handling.
     *
     * @param string $path Directory path to create
     * @return bool True if directory created or exists, false on error
     */
    protected function makeDirectory(string $path): bool
    {
        if ($this->files->exists($path) && !$this->option('force')) {
            $this->error("Directory {$path} already exists! Use --force to overwrite.");
            return false;
        }

        $this->files->makeDirectory($path, 0755, true, true);
        return true;
    }

    /**
     * Get the base directory path for repositories from config.
     *
     * @return string Repository base path with trailing slash
     */
    protected function getPathDirectoryRepositoryBase(): string
    {
        return config('repository.path_repository', self::PATH_REPOSITORY) . '/';
    }

    /**
     * Get the base directory path for services from config.
     *
     * @return string Service base path with trailing slash
     */
    protected function getPathDirectoryServiceBase(): string
    {
        return config('repository.path_service', self::PATH_SERVICE) . '/';
    }

    /**
     * Convert file path to namespace format.
     *
     * @param string $folder Folder path with forward slashes
     * @return string Namespace path with backslashes
     */
    private function prepareNamespaceByPath(string $folder): string
    {
        return str_replace('/', '\\', trim($folder));
    }

    /**
     * Run composer dump-autoload command with user confirmation.
     *
     * @return void
     */
    private function runComposerDumpAutoload(): void
    {
        if (
            config('repository.dump_auto_load', false) ||
            (config('repository.ask_dump_auto_load', true) && $this->confirmComposerDump())
        ) {
            $this->newLine();
            $this->components->task('Running composer dump-autoload to update class mappings', function () {
                exec('composer dump-autoload > /dev/null 2>&1');
                return true;
            });
        }
    }

    /**
     * Ask user to confirm composer dump-autoload execution.
     *
     * @return bool User confirmation result
     */
    private function confirmComposerDump(): bool
    {
        return $this->components->confirm('Run composer dump-autoload to update class mappings?', true);
    }

    /**
     * Display formatted success messages with file locations.
     *
     * @return void
     */
    private function displaySuccessMessages(): void
    {
        $name = $this->getNameInput();

        $this->components->info("Repository and Service patterns created successfully!");

        // Repository files table
        $this->components->twoColumnDetail('Repository Interface',
            "<fg=gray>{$this->repositoryNamespace}\\{$name}RepositoryInterface</>");
        $this->components->twoColumnDetail('Repository Implementation',
            "<fg=gray>{$this->repositoryNamespace}\\{$name}EloquentRepository</>");

        $this->newLine();

        // Service files table
        $this->components->twoColumnDetail('Service Interface',
            "<fg=gray>{$this->serviceNamespace}\\{$name}ServiceInterface</>");
        $this->components->twoColumnDetail('Service Implementation',
            "<fg=gray>{$this->serviceNamespace}\\{$name}Service</>");

        $this->newLine();

        if ($this->option('model')) {
            $this->components->twoColumnDetail('Model Binding',
                "<fg=gray>{$this->getModelNamespace()}</>");
        }
    }
}
