<?php

declare(strict_types=1);

namespace LongAoDai\Repository\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when a repository operation fails unexpectedly.
 *
 * Example: Create/Update/Delete failed due to DB or unexpected state.
 */
class RepositoryFailureHandlingException extends RuntimeException
{
    /**
     * Create a new RepositoryFailureHandlingException instance.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = 'Repository operation failed.',
        int $code = 500,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
