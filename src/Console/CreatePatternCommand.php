<?php

namespace LongAoDai\Repository\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

/**
 * Class CreatePatternCommand
 *
 * @property \Illuminate\Foundation\Application|Application $laravel
 *
 * @package LongAoDai\Repository\Console
 */
abstract class CreatePatternCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected string $signature = 'setup:repository {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected string $description = 'Create a new repository';

    /**
     * Path default service and repository
     */
    private const PATH_SERVICE = 'Services';
    private const PATH_REPOSITORY = 'Repositories';

    /**
     * Type
     */
    private const TYPE_CLASS = 'class';
    private const TYPE_INTERFACE = 'interface';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    public function __construct(
        private readonly Filesystem $files
    )
    {
        parent::__construct();
    }

    /**
     * The namespace of class and interface
     *
     * @var string
     */
    private string $namespace;

    /**
     * Path directory repository created
     *
     * @var string
     */
    private string $pathDirectoryRepository;
    private string $pathDirectoryService;

    /**
     * Path get interface repository stub
     */
    protected string $interfaceRepositoryStub;
    protected string $implementRepositoryStub;
    protected string $interfaceServiceStub;
    protected string $implementServiceStub;
    protected string $providerRepositoryStub;
    protected string $providerServiceStub;

    protected function getStub(): void
    {
        $this->interfaceRepositoryStub = __DIR__ . '/stubs/interface.repository.stub';
        $this->implementRepositoryStub = __DIR__ . '/stubs/implement.repository.stub';

        $this->interfaceServiceStub = __DIR__ . '/stubs/interface.service.stub';
        $this->implementServiceStub = __DIR__ . '/stubs/implement.service.stub';

        $this->providerRepositoryStub = __DIR__ . '/stubs/provider.repository.stub';
        $this->providerServiceStub = __DIR__ . '/stubs/provider.service.stub';
    }

    /**
     * Handle execute
     *
     * @return int
     */
    public function handle(): int
    {
        $this->getStub();
        $this->setNamespace();
        $this->setPathDirectoryRepository();
        $this->setPathDirectoryService();

        if (!$this->createPatternFile()) {
            return Command::FAILURE;
        }

        $this->createServiceProvider();
        exec('composer dump-autoload > /dev/null 2>&1');

        $this->info("Service {$this->getNameInput()} created successfully !!!");
        $this->info('Implement: ' . $this->namespace . '\\' . $this->getNameInput() . 'Service.php');
        $this->info('Interface: ' . $this->namespace . '\\' . $this->getNameInput() . 'ServiceInterface.php');

        $this->info("Repository {$this->getNameInput()} created successfully !!!");
        $this->info('Implement: ' . $this->namespace . '\\' . $this->getNameInput() . 'EloquentRepository.php');
        $this->info('Interface: ' . $this->namespace . '\\' . $this->getNameInput() . 'RepositoryInterface.php');

        return Command::SUCCESS;
    }

    /**
     * Handle create base service provider
     *
     * @return void
     */
    protected function createServiceProvider(): void
    {
        $pathRepositoryProvider = $this->laravel['path'] . '/Providers/RepositoryServiceProvider.php';
        $pathServiceProvider = $this->laravel['path'] . '/Providers/InternalServiceProvider.php';

        $repositoryStub = $this->files->exists($pathRepositoryProvider)
            ? $this->files->get($pathRepositoryProvider)
            : $this->files->get($this->providerRepositoryStub);

        $serviceStub = $this->files->exists($pathServiceProvider)
            ? $this->files->get($pathServiceProvider)
            : $this->files->get($this->providerServiceStub);

        $this->files->put($pathRepositoryProvider, $this->prepareStub($repositoryStub, self::PATH_REPOSITORY));
        $this->files->put($pathServiceProvider, $this->prepareStub($serviceStub, self::PATH_SERVICE));
    }

    /**
     * Create repository file class and interface
     *
     * @return bool
     */
    private function createPatternFile(): bool
    {
        $folderRepository = $this->makeDirectory($this->pathDirectoryRepository);
        $folderService = $this->makeDirectory($this->pathDirectoryService);

        if ($folderRepository || $folderService) {
            return false;
        }

        $fileRepositoryClass = $folderRepository . '/' . $this->getNameInput() . 'EloquentRepository.php';
        $fileRepositoryInterface = $folderRepository . '/' . $this->getNameInput() . 'RepositoryInterface.php';

        $fileServiceClass = $folderService . '/' . $this->getNameInput() . 'Service.php';
        $fileServiceInterface = $folderService . '/' . $this->getNameInput() . 'ServiceInterface.php';

        $this->files->put($fileRepositoryClass, $this->buildFileContent(self::TYPE_CLASS, self::PATH_REPOSITORY));
        $this->files->put($fileRepositoryInterface, $this->buildFileContent(self::TYPE_INTERFACE, self::PATH_REPOSITORY));

        $this->files->put($fileServiceClass, $this->buildFileContent(self::TYPE_CLASS, self::PATH_SERVICE));
        $this->files->put($fileServiceInterface, $this->buildFileContent(self::TYPE_INTERFACE, self::PATH_SERVICE));

        return true;
    }

    /**
     * Handle build content for file class and interface
     *
     * @param string $type
     * @param string $path
     *
     * @return string
     */
    private function buildFileContent(string $type, string $path): string
    {
        if ($path === self::PATH_SERVICE) {
            $pathFile = match ($type) {
                self::TYPE_INTERFACE => $this->interfaceServiceStub,
                default => $this->implementServiceStub,
            };
        } else {
            $pathFile = match ($type) {
                self::TYPE_INTERFACE => $this->interfaceRepositoryStub,
                default => $this->implementRepositoryStub,
            };
        }

        $stub = $this->files->get($pathFile);

        return $this->prepareStub($stub, $path);
    }

    /**
     * Prepare variable in stubs file
     *
     * @param string $path
     * @param string $stub
     *
     * @return string
     */
    private function prepareStub(string $stub, string $path): string
    {
        if ($path === self::PATH_REPOSITORY) {
            $interfaceName = $this->getNameInput() . 'RepositoryInterface';
            $className = $this->getNameInput() . 'EloquentRepository';
        } else {
            $interfaceName = $this->getNameInput() . 'ServiceInterface';
            $className = $this->getNameInput() . 'Service';
        }

        $interfaceNamespace = 'use ' . $this->namespace . '\\' . $interfaceName . ';' . "\n" . '#InterfaceNamespace';
        $classNamespace = 'use ' . $this->namespace . '\\' . $className . ';' . "\n" . '#ClassNamespace';

        $interfaceSingletonProvider = '$this->app->singleton(' .
            ($interfaceName . '::class') .
            ', ' .
            ($className . '::class') .
            ');' . "\n\t\t" . '#Singleton';

        $interfaceProvider = ($interfaceName . '::class') . ',' . "\n\t\t\t" . '#InterfaceProvides';

        return str_replace(
            [
                '#Namespace', '#InterfaceNamespace', '#ClassNamespace', '#RepositoryInterfaceName',
                '#RepositoryClassName', '#InterfaceProvides', '#Singleton'
            ],
            [
                $this->namespace,
                $interfaceNamespace,
                $classNamespace,
                $interfaceName,
                $className,
                $interfaceProvider,
                $interfaceSingletonProvider,
            ],
            $stub
        );
    }

    /**
     * Get name input -> Name file
     *
     * @return string
     */
    protected function getNameInput(): string
    {
        return trim((string)$this->argument('name'));
    }

    /**
     * Make directory repository
     *
     * @param string $path
     *
     * @return string|false
     */
    protected function makeDirectory(string $path): string|false
    {
        if ($this->files->exists($path)) {
            $this->error("Repository {$this->getNameInput()} already exist !");
            return false;
        }

        $this->files->makeDirectory($path, 0777, true, true);

        return $path;
    }

    /**
     * Get path directory base. Where directory created
     *
     * @return string
     */
    protected function getPathDirectoryRepositoryBase(): string
    {
        return config('pattern.path_repository', self::PATH_REPOSITORY) . '/';
    }

    /**
     * Get path directory base. Where directory created
     *
     * @return string
     */
    protected function getPathDirectoryServiceBase(): string
    {
        return config('pattern.path_service', self::PATH_SERVICE) . '/';
    }

    /**
     * Prepare name space by path
     *
     * @param string $folder
     *
     * @return string
     */
    private function prepareNamespaceByPath(string $folder): string
    {
        return implode('\\', explode('/', $folder));
    }

    /**
     * Set path directory repository
     *
     * @return void
     */
    private function setPathDirectoryRepository(): void
    {
        $this->pathDirectoryRepository = $this->laravel['path'] . '/' . $this->getPathDirectoryRepositoryBase() . $this->getNameInput();
    }

    /**
     * Set path directory service
     *
     * @return void
     */
    private function setPathDirectoryService(): void
    {
        $this->pathDirectoryService = $this->laravel['path'] . '/' . $this->getPathDirectoryServiceBase() . $this->getNameInput();
    }

    /**
     * Set namespace for file class and interface
     *
     * @return void
     */
    private function setNamespace(): void
    {
        $this->namespace = $this->laravel->getNamespace() . $this->prepareNamespaceByPath($this->getPathDirectoryRepositoryBase()) . $this->getNameInput();
    }
}
