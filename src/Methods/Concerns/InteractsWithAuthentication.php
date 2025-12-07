<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Methods\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function abort_unless;
use function auth;

/**
 * Provides authentication helpers for JSON-RPC methods.
 *
 * Offers convenient methods for retrieving and verifying the authenticated user
 * within JSON-RPC method handlers, with automatic 401 response on auth failure.
 *
 * @author Brian Faust <brian@cline.sh>
 */
trait InteractsWithAuthentication
{
    /**
     * Get the currently authenticated user or abort with 401.
     *
     * Retrieves the authenticated user from Laravel's auth system. If no user
     * is authenticated, aborts the request with a 401 Unauthorized response.
     * Use this method when authentication is required for the operation.
     *
     * @throws HttpException When no user is authenticated
     *
     * @return Authenticatable The authenticated user instance
     */
    protected function getCurrentUser(): Authenticatable
    {
        abort_unless(auth()->check(), 401, 'Unauthorized');

        /** @var Authenticatable */
        return auth()->user();
    }
}
