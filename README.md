# Laravel Repository Pattern

[![Latest Version on Packagist](https://img.shields.io/packagist/v/longaodai/laravel-repository.svg?style=flat-square)](https://packagist.org/packages/longaodai/laravel-repository)
[![Total Downloads](https://img.shields.io/packagist/dt/longaodai/laravel-repository.svg?style=flat-square)](https://packagist.org/packages/longaodai/laravel-repository)
[![License](https://img.shields.io/packagist/l/longaodai/laravel-repository.svg?style=flat-square)](https://packagist.org/packages/longaodai/laravel-repository)

A comprehensive Laravel package that implements the **Repository** and **Service Layer** design patterns, providing a clean and maintainable way to interact with your Eloquent models. This package promotes separation of concerns, making your code more testable, organized, and following SOLID principles.

## Features

- **Repository Pattern Implementation** - Clean abstraction layer between your controllers and models
- **Service Layer Pattern** - Business logic separation from controllers
- **Artisan Commands** - Generate repositories and services with simple commands
- **Interface-Based** - Follows dependency inversion principle
- **Performance Optimized** - Built with Laravel best practices
- **Rich Query Methods** - Comprehensive set of query methods out of the box

## Requirements

- PHP >= 8.0
- Laravel >= 9.0

## Installation

You can install the package via Composer:

```bash
composer require longaodai/laravel-repository
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=laravel-repository
```

This will create a `config/repository.php` file where you can customize package settings.

## Quick Start

Generate a complete repository and service for your model:

```bash
php artisan make:repository User
```

### Available Options:
- `--model=ModelName`: Specify the model class name
- `--force`: Overwrite existing files

### Generated Files:
```
Repository Interface ........... App\Repositories\User\UserRepositoryInterface
Repository Implementation ...... App\Repositories\User\UserEloquentRepository  
Service Interface .............. App\Services\User\UserServiceInterface
Service Implementation ......... App\Services\User\UserService
```

### Register Service Providers

Add the generated service providers to your `bootstrap/providers.php` (only once after running the first make repository command):

```php
<?php
return [
    App\Providers\AppServiceProvider::class,
    // ... other providers
    
    // Add these lines  
    App\Providers\RepositoryServiceProvider::class,
    App\Providers\InternalServiceProvider::class,
];
```

## Usage
### Controller Integration

Inject and use the service in your controllers:

```php
<?php

namespace App\Http\Controllers;

use App\Services\User\UserServiceInterface;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Display a paginated list of users
     */
    public function index(Request $request)
    {
        // Get paginated list with optional filters
        $users = $this->userService->getList([
            'name' => $request->get('name'),
        ]);

        return response()->json($users);
    }

    /**
     * Store a new user
     */
    public function store(Request $request)
    {
        $userData = collect([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $user = $this->userService->create($userData);

        return response()->json($user, 201);
    }

    /**
     * Display the specified user
     */
    public function show($id)
    {
        $user = $this->userService->find(['id' => $id]);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, $id)
    {
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        $user = $this->userService->update($updateData, ['id' => $id]);

        return response()->json($user);
    }

    /**
     * Remove the specified user
     */
    public function destroy($id)
    {
        $deleted = $this->userService->destroy(['id' => $id]);

        return response()->json(['deleted' => $deleted]);
    }
}
```

### Custom Repository Methods

Add custom methods to your repository:

```php
<?php

namespace App\Repositories\User;

use App\Models\User;
use LongAoDai\LaravelRepository\Eloquent\BaseRepository;

class UserEloquentRepository extends BaseRepository implements UserRepositoryInterface
{
    public function model(): string
    {
        return User::class;
    }

    /**
     * Hook for filtering queries.
     *
     * @param RepositoryResponse $params
     *
     * @return static
     */
    protected function filter(RepositoryResponse $params): static
    {
        if (!empty($params->get('id'))) {
            $this->method('where', $this->table . '.id', $params->get('id'));
        }
        
        if (!empty($params->get('name'))) {
            $this->method('where', $this->table . '.name', $params->get('name'));
        }
        
        if (!empty($params->get('status'))) {
            $this->method('where', $this->table . '.status', $params->get('status'));
        }
        
        if (!empty($params->option('with_post'))) {
            $this->method('with', ['posts' => function ($query) {
                return $query->select('id', 'name');
            }]);
        }

        return parent::filter($params);
    }

    /**
     * Hook for filtering update.
     *
     * @param RepositoryResponse $params
     *
     * @return static
     */
    protected function mask(RepositoryResponse $params): static
    {
        if (!empty($params->option('id'))) {
            $this->method('where', $this->table . '.id', $params->option('id'));
        }

        return parent::mask($params);
    }
}
```

### Service Layer Business Logic

Implement complex business logic in services:

```php
<?php

namespace App\Services\User;

use App\Repositories\User\UserRepositoryInterface;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\DB;

class UserService implements UserServiceInterface
{
    public function __construct(UserRepositoryInterface $repository)
    {
        $this->userRepository = $repository;
    }

    /**
     * Create a new user with welcome notification
     */
    public function createUserWithWelcome($userData)
    {
        return DB::transaction(function () use ($userData) {
            // Create user
            $user = $this->userRepository->create($userData);

            // Send welcome notification
            $user->notify(new WelcomeNotification());

            // Log user creation
            logger('New user created', ['user_id' => $user->id]);

            return $user;
        });
    }

    /**
     * Get users with specific business logic
     */
    public function getActiveUsersWithPosts($data, $options)
    {
        return $this->userRepository->all(
            [
                'status' => $data['status'],
            ], [
                'with_post' => true,
            ]
        );
    }
}
```

## Available Methods

The base repository provides these methods out of the box:

| Method | Description                    | Example                                             |
|--------|--------------------------------|-----------------------------------------------------|
| `all()` | Get all records                | `$service->all()`                                   |
| `getList($params)` | Get paginated list with filters | `$service->getList(['per_page' => 10])`                |
| `find($conditions)` | Find by id                     | `$service->find(['id' => 1])`                       |
| `first($conditions)` | Get first record by conditions | `$service->first(['status' => 'active'])`           |
| `create($data)` | Create new record              | `$service->create(['name' => 'John'])`              |
| `update($data, $conditions)` | Update records                 | `$service->update(['name' => 'Jane'], ['id' => 1])` |
| `updateOrCreate($conditions, $data)` | Update or create record        | `$service->updateOrCreate(['name' => 'Test'], ['email' => 'test@example.com'])`    |
| `destroy($conditions)` | Delete records                 | `$service->destroy(['id' => 1])`                    |

## Method Examples

### Retrieving Data

```php
// Get all users
$users = $userService->all();

// Get paginated list with search
$users = $userService->getList([
    'per_page' => 20,
    'search' => 'john',
    'status' => 'active'
]);

// Find specific user
$user = $userService->find(['id' => 1]);

// Get first user with conditions
$activeUser = $userService->first(['status' => 'active']);
$user = $userService->first(['email' => 'user@example.com']);
```

### Creating Data

```php
// Create new user
$newUser = $userService->create(collect([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password123'),
    'status' => 'active'
]));
```

### Updating Data

```php
// Update user
$updatedUser = $userService->update([
    'name' => 'John Smith', // data to update
], ['id' => 1]); // conditions

// Update or create user
$user = $userService->updateOrCreate(
    collect(['name' => 'Jane Doe', 'status' => 'active']) // data to update/create
    collect(['email' => 'jane@example.com']), // conditions
);
```

### Deleting Data

```php
// Delete user
$deleted = $userService->destroy(['id' => 1]);

// Delete multiple users
$deleted = $userService->destroy(['status' => 'inactive']);
```

## Best Practices

### Move Business Logic to Services

```php
// ❌ Bad - Logic in controller
public function store(Request $request)
{
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
    ]);

    $user->notify(new WelcomeNotification());

    return response()->json($user);
}

// ✅ Good - Logic in service
public function store(Request $request)
{
    $user = $this->userService->createUserWithWelcome($request->validated());
    
    return response()->json($user);
}
```

### Always Use Interface Type Hinting

```php
// ✅ Good
public function __construct(UserServiceInterface $userService)
{
    $this->userService = $userService;
}

// ❌ Bad  
public function __construct(UserService $userService)
{
    $this->userService = $userService;
}
```
## Security

If you discover any security-related issues, please email vochilong.work@gmail.com instead of using the issue tracker.
[LongAoDai](https://github.com/longaodai)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

## Support

If you find this package helpful, please consider:

- Starring the repository
- Reporting any bugs you encounter
- Suggesting new features
- Improving documentation

For questions and support, please use
the [GitHub Discussions](https://github.com/longaodai/laravel-repository/discussions) or create
an [issue](https://github.com/longaodai/laravel-repository/issues).