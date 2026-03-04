<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use Override;

/**
 * Base exception for request validation failures.
 *
 * This exception represents a JSON-RPC server error that maps to HTTP 422,
 * indicating that the request is well-formed but contains semantic errors that
 * prevent processing. This is commonly used for validation failures where the
 * request structure is valid but the data doesn't meet business rules or
 * constraints. The exception formats validation errors according to JSON:API
 * error specifications with source pointers for precise error location.
 *
 * @author Brian Faust <brian@cline.sh>
 */
abstract class UnprocessableEntityException extends AbstractRequestException
{
    /**
     * Get the HTTP status code for this exception.
     *
     * @return int HTTP 422 Unprocessable Entity status code
     */
    #[Override()]
    public function getStatusCode(): int
    {
        return 422;
    }
}
