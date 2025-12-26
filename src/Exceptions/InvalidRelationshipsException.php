<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\RPC\Exceptions;

use function array_diff;
use function implode;
use function sprintf;

/**
 * Exception thrown when requested relationships are not permitted for resource inclusion.
 *
 * Represents JSON-RPC error code -32602 for invalid relationship specifications in
 * include parameters. This exception prevents unauthorized relationship loading and
 * ensures clients only request relationships that are explicitly allowed, protecting
 * against unauthorized data access and N+1 query issues from unrestricted includes.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidRelationshipsException extends AbstractRequestException
{
    /**
     * Creates an invalid relationships exception with detailed error information.
     *
     * Validates requested relationships against the allowed relationship set and
     * generates a JSON-RPC compliant error response. Handles both scenarios where
     * allowed relationships exist (listing valid options) and where no relationships
     * are permitted (indicating the resource has no includable relationships).
     *
     * @param  array<int, string> $unknownRelationships Array of relationship names that were
     *                                                  requested but are not in the allowed set.
     *                                                  These will be compared against allowed
     *                                                  relationships to identify specific
     *                                                  unauthorized include attempts.
     * @param  array<int, string> $allowedRelationships Array of relationship names that are
     *                                                  permitted for eager loading. Can be empty
     *                                                  if the resource has no includable relations.
     *                                                  Used to generate helpful error messages
     *                                                  with valid relationship options.
     * @return self               a new instance with HTTP 422 status, JSON Pointer to /params/relationships,
     *                            detailed error message showing unknown relationships and either the allowed
     *                            relationships list or a message indicating no relationships are available,
     *                            plus meta information containing both unknown and allowed relationship lists
     */
    public static function create(array $unknownRelationships, array $allowedRelationships): self
    {
        $unknownRelationships = implode(', ', array_diff($unknownRelationships, $allowedRelationships));

        $message = sprintf('Requested relationships `%s` are not allowed. ', $unknownRelationships);

        if ($allowedRelationships !== []) {
            $allowedRelationships = implode(', ', $allowedRelationships);
            $message .= sprintf('Allowed relationships are `%s`.', $allowedRelationships);
        } else {
            $message .= 'There are no allowed relationships.';
        }

        return self::new(-32_602, 'Invalid params', [
            [
                'status' => '422',
                'source' => ['pointer' => '/params/relationships'],
                'title' => 'Invalid relationships',
                'detail' => $message,
                'meta' => [
                    'unknown' => $unknownRelationships,
                    'allowed' => $allowedRelationships,
                ],
            ],
        ]);
    }
}
