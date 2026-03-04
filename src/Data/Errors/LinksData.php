<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Data\Errors;

use Cline\RPC\Data\AbstractData;

/**
 * Represents hypermedia links for JSON:API error objects.
 *
 * Provides URLs to external resources related to an error, such as
 * documentation pages explaining the error type or API reference links
 * that help developers understand and resolve the issue.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://jsonapi.org/format/#error-objects
 */
final class LinksData extends AbstractData
{
    /**
     * Create a new error links instance.
     *
     * @param null|string $about URL to a resource providing more information about
     *                           this specific error occurrence, such as documentation
     *                           explaining the error condition and potential solutions
     * @param null|string $type  URL to a resource identifying the error type definition,
     *                           typically pointing to API documentation or schema definitions
     *                           that describe the error in detail
     */
    public function __construct(
        public readonly ?string $about,
        public readonly ?string $type,
    ) {}
}
