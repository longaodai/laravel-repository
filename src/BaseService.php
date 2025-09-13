<?php

declare(strict_types=1);

namespace LongAoDai\Repository;

use Illuminate\Support\Collection;
use LongAoDai\Repository\Exceptions\RepositoryFailureHandlingException;

/**
 * Abstract Class BaseService
 *
 * Provides a base layer to interact with repositories,
 * handling request/response normalization and exceptions.
 *
 * @package LongAoDai\Repository
 * @author  vochilong<vochilong.work@gmail.com>
 */
abstract class BaseService
{
    /**
     * The repository instance.
     *
     * @var object
     */
    protected object $repository;

    /**
     * Retrieve all records.
     *
     * @param Collection|array $data
     * @param Collection|array $options
     * @return mixed
     */
    public function all(Collection|array $data = [], Collection|array $options = []): mixed
    {
        return $this->repository->all(
            $this->response($data, $options)
        );
    }

    /**
     * Retrieve paginated list of records.
     *
     * @param Collection|array $data
     * @param Collection|array $options
     * @return mixed
     */
    public function getList(Collection|array $data = [], Collection|array $options = []): mixed
    {
        return $this->repository->getList(
            $this->response($data, $options)
        );
    }

    /**
     * Show a single record by ID.
     *
     * @param Collection|array $data
     * @param Collection|array $options
     * @return mixed
     *
     * @throws RepositoryFailureHandlingException
     */
    public function show(Collection|array $data = [], Collection|array $options = []): mixed
    {
        $response = $this->response($data, $options);

        if (empty($response->get('id'))) {
            throw new RepositoryFailureHandlingException("Record ID is required.");
        }

        $item = $this->repository->find($response);

        if (empty($item)) {
            throw new RepositoryFailureHandlingException("Record not found.");
        }

        return $item;
    }

    /**
     * Get the first record by conditions.
     *
     * @param Collection|array $data
     * @param Collection|array $options
     * @return mixed
     */
    public function getFirstBy(Collection|array $data = [], Collection|array $options = []): mixed
    {
        return $this->repository->first(
            $this->response($data, $options)
        );
    }

    /**
     * Create a new record.
     *
     * @param Collection|array $data
     * @param Collection|array $options
     * @return mixed
     *
     * @throws RepositoryFailureHandlingException
     */
    public function store(Collection|array $data = [], Collection|array $options = []): mixed
    {
        $created = $this->repository->create(
            $this->response($data, $options)
        );

        if (empty($created)) {
            throw new RepositoryFailureHandlingException("Failed to create record.");
        }

        return $created;
    }

    /**
     * Update existing record(s).
     *
     * @param Collection|array $data
     * @param Collection|array $options
     * @return mixed
     *
     * @throws RepositoryFailureHandlingException
     */
    public function update(Collection|array $data = [], Collection|array $options = []): mixed
    {
        $updated = $this->repository->update(
            $this->response($data, $options)
        );

        if ($updated === 0) {
            throw new RepositoryFailureHandlingException("No record updated.");
        }

        return $updated;
    }

    /**
     * Update an existing record or create a new one.
     *
     * @param Collection|array $data
     * @param Collection|array $options
     * @return mixed
     */
    public function updateOrCreate(Collection|array $data = [], Collection|array $options = []): mixed
    {
        return $this->repository->updateOrCreate(
            $this->response($data, $options)
        );
    }

    /**
     * Delete record(s).
     *
     * @param Collection|array $data
     * @param Collection|array $options
     * @return mixed
     *
     * @throws RepositoryFailureHandlingException
     */
    public function destroy(Collection|array $data = [], Collection|array $options = []): mixed
    {
        $deleted = $this->repository->destroy(
            $this->response($data, $options)
        );

        if ($deleted === 0) {
            throw new RepositoryFailureHandlingException("No record deleted.");
        }

        return $deleted;
    }

    /**
     * Wrap request data into a RepositoryResponse.
     *
     * @param Collection|array $data
     * @param Collection|array $options
     * @return RepositoryResponse
     */
    protected function response(Collection|array $data = [], Collection|array $options = []): RepositoryResponse
    {
        return new RepositoryResponse(
            $data instanceof Collection ? $data->toArray() : $data,
            $options instanceof Collection ? $options->toArray() : $options
        );
    }
}
