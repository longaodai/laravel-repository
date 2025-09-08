<?php

declare(strict_types=1);

namespace LongAoDai\Repository;

use Exception;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Abstract Class BaseService
 *
 * Provides a base layer to interact with repositories,
 * handling request/response normalization and exceptions.
 *
 * @package LongAoDai\Repository
 */
abstract class BaseService
{
    /**
     * The repository instance.
     *
     * @var object
     */
    protected readonly object $repository;

    /**
     * BaseService constructor.
     *
     * @param object $repository
     */
    public function __construct(object $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retrieve all records.
     *
     * @param Collection|array|null $data
     * @param array|null $options
     * @return mixed
     */
    public function all(Collection|array|null $data = null, ?array $options = null): mixed
    {
        return $this->repository->all(
            $this->response($data, $options)
        );
    }

    /**
     * Retrieve paginated list of records.
     *
     * @param Collection|array|null $data
     * @param array|null $options
     * @return mixed
     */
    public function getList(Collection|array|null $data = null, ?array $options = null): mixed
    {
        return $this->repository->getList(
            $this->response($data, $options)
        );
    }

    /**
     * Show a single record by ID.
     *
     * @param Collection|array|null $data
     * @param array|null $options
     * @return mixed
     *
     * @throws NotFoundHttpException
     */
    public function show(Collection|array|null $data = null, ?array $options = null): mixed
    {
        $response = $this->response($data, $options);

        if (empty($response->get('id'))) {
            throw new NotFoundHttpException("Record ID is required.");
        }

        $item = $this->repository->find($response);

        if (empty($item)) {
            throw new NotFoundHttpException("Record not found.");
        }

        return $item;
    }

    /**
     * Get the first record by conditions.
     *
     * @param Collection|array|null $data
     * @param array|null $options
     * @return mixed
     */
    public function getFirstBy(Collection|array|null $data = null, ?array $options = null): mixed
    {
        return $this->repository->first(
            $this->response($data, $options)
        );
    }

    /**
     * Create a new record.
     *
     * @param Collection|array|null $data
     * @param array|null $options
     * @return mixed
     *
     * @throws FailureHandlingException
     */
    public function store(Collection|array|null $data = null, ?array $options = null): mixed
    {
        $created = $this->repository->create(
            $this->response($data, $options)
        );

        if (empty($created)) {
            throw new FailureHandlingException("Failed to create record.");
        }

        return $created;
    }

    /**
     * Update existing record(s).
     *
     * @param Collection|array|null $data
     * @param array|null $options
     * @return mixed
     *
     * @throws FailureHandlingException
     */
    public function update(Collection|array|null $data = null, ?array $options = null): mixed
    {
        $updated = $this->repository->update(
            $this->response($data, $options)
        );

        if ($updated === 0) {
            throw new FailureHandlingException("No record updated.");
        }

        return $updated;
    }

    /**
     * Update an existing record or create a new one.
     *
     * @param Collection|array|null $data
     * @param array|null $options
     * @return mixed
     */
    public function updateOrCreate(Collection|array|null $data = null, ?array $options = null): mixed
    {
        return $this->repository->updateOrCreate(
            $this->response($data, $options)
        );
    }

    /**
     * Delete record(s).
     *
     * @param Collection|array|null $data
     * @param array|null $options
     * @return mixed
     *
     * @throws FailureHandlingException
     */
    public function destroy(Collection|array|null $data = null, ?array $options = null): mixed
    {
        $deleted = $this->repository->destroy(
            $this->response($data, $options)
        );

        if ($deleted === 0) {
            throw new FailureHandlingException("No record deleted.");
        }

        return $deleted;
    }

    /**
     * Wrap request data into a RepositoryResponse.
     *
     * @param Collection|array|null $data
     * @param array|null $options
     * @return RepositoryResponse
     */
    protected function response(Collection|array|null $data, ?array $options = null): RepositoryResponse
    {
        return new RepositoryResponse($data ?? [], $options ?? []);
    }
}
