<?php

declare(strict_types=1);

namespace LongAoDai\Repository;

use Illuminate\Container\Container as Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\Routing\Exception\InvalidParameterException;

/**
 * Abstract Class BaseRepository
 *
 * Provides a base implementation for repositories using Eloquent ORM.
 * Contains common CRUD operations and helper methods for query handling.
 *
 * @package LongAoDai\Repository
 */
abstract class BaseRepository
{
    /**
     * The application container instance.
     *
     * @var Application
     */
    protected readonly Application $app;

    /**
     * The current query builder instance for the model.
     *
     * @var Builder
     */
    protected Builder $query;

    /**
     * BaseRepository constructor.
     *
     * @param Application $app
     * @throws ModelNotFoundException
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->makeModel();
    }

    /**
     * Get the fully qualified model class name.
     *
     * Example:
     *   return User::class;
     *
     * @return class-string<Model>
     */
    abstract public function model(): string;

    /**
     * Initialize the model and create a new query builder instance.
     *
     * @return Builder
     * @throws ModelNotFoundException
     */
    public function makeModel(): Builder
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new ModelNotFoundException(
                "Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model"
            );
        }

        return $this->query = $model->newQuery();
    }

    /**
     * Reset the query builder.
     *
     * @return Builder
     */
    public function resetModel(): Builder
    {
        return $this->makeModel();
    }

    /**
     * Get the underlying query builder.
     *
     * @return Builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * Call a method dynamically on the query builder.
     *
     * @param string $name
     * @param mixed ...$arguments
     * @return mixed
     */
    public function method(string $name, mixed ...$arguments): mixed
    {
        return $this->query->{$name}(...$arguments);
    }

    /**
     * Get the pagination limit from params or default.
     *
     * @param RepositoryResponse $params
     * @return int
     */
    protected function getLimitPaginate(RepositoryResponse $params): int
    {
        return $params->option('limit') ?? 20;
    }

    /**
     * Retrieve all records.
     *
     * @param RepositoryResponse $params
     * @return mixed
     */
    public function all(RepositoryResponse $params): mixed
    {
        $this->resetModel();
        $this->filter($params);

        return $this->method('get');
    }

    /**
     * Count records.
     *
     * @param RepositoryResponse $params
     * @return int
     */
    public function count(RepositoryResponse $params): int
    {
        $this->resetModel();
        $this->filter($params);

        return (int)$this->method('count');
    }

    /**
     * Get paginated list of records.
     *
     * @param RepositoryResponse $params
     * @return mixed
     */
    public function getList(RepositoryResponse $params): mixed
    {
        $this->resetModel();
        $this->filter($params);

        return $this->method('paginate', $this->getLimitPaginate($params));
    }

    /**
     * Find a record by ID.
     *
     * @param RepositoryResponse $params
     * @return Model|null
     */
    public function find(RepositoryResponse $params): ?Model
    {
        return $this->method('find', $params->get('id'));
    }

    /**
     * Get the first record based on filters.
     *
     * @param RepositoryResponse $params
     * @return Model|null
     */
    public function first(RepositoryResponse $params): ?Model
    {
        $this->resetModel();
        $this->filter($params);

        return $this->method('first');
    }

    /**
     * Create a new record.
     *
     * @param RepositoryResponse $params
     * @return Model
     */
    public function create(RepositoryResponse $params): Model
    {
        return $this->method('create', $params->get());
    }

    /**
     * Insert multiple records at once.
     *
     * @param RepositoryResponse $params
     * @return bool
     */
    public function insert(RepositoryResponse $params): bool
    {
        return $this->method('insert', $params->get());
    }

    /**
     * Update existing records.
     *
     * @param RepositoryResponse $params
     * @return int Number of affected rows
     *
     * @throws InvalidParameterException
     */
    public function update(RepositoryResponse $params): int
    {
        $this->resetModel();
        $this->mask($params);

        if (empty($this->query->getQuery()->wheres)) {
            throw new InvalidParameterException(
                "Update operation requires at least one WHERE condition."
            );
        }

        return (int)$this->method('update', $params->get());
    }

    /**
     * Update an existing record or create a new one.
     *
     * @param RepositoryResponse $params
     * @return Model
     */
    public function updateOrCreate(RepositoryResponse $params): Model
    {
        return $this->method('updateOrCreate', $params->option(), $params->get());
    }

    /**
     * Upsert records (insert or update).
     *
     * @param RepositoryResponse $params
     * @return mixed
     */
    public function upsert(RepositoryResponse $params): mixed
    {
        return $this->method('upsert', $params->option(), $params->get());
    }

    /**
     * Delete records.
     *
     * @param RepositoryResponse $params
     * @return int Number of deleted rows
     *
     * @throws InvalidParameterException
     */
    public function destroy(RepositoryResponse $params): int
    {
        $this->resetModel();
        $this->filter($params);

        if (empty($this->query->getQuery()->wheres)) {
            throw new InvalidParameterException(
                "Delete operation requires at least one WHERE condition."
            );
        }

        return (int)$this->method('delete');
    }

    /**
     * Hook for filtering queries (override in child repositories).
     *
     * @param RepositoryResponse $params
     * @return static
     */
    protected function filter(RepositoryResponse $params): static
    {
        return $this;
    }

    /**
     * Hook for masking data before update (override in child repositories).
     *
     * @param RepositoryResponse $params
     * @return static
     */
    protected function mask(RepositoryResponse $params): static
    {
        return $this;
    }
}
