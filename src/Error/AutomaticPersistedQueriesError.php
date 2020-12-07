<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Error;

use GraphQL\Error\ClientAware;
use GraphQL\Error\Error;

class AutomaticPersistedQueriesError extends Error implements ClientAware
{
    const CODE_PERSISTED_QUERY_NOT_SUPPORTED = 'PERSISTED_QUERY_NOT_SUPPORTED';
    const CODE_PERSISTED_QUERY_NOT_FOUND = 'PERSISTED_QUERY_NOT_FOUND';
    const CODE_INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';

    /**
     * @return self
     */
    public static function persistedQueriesNotSupported(): self
    {
        return new self(
            'PersistedQueryNotSupported',
            $nodes = null,
            $source = null,
            $positions = [],
            $path = null,
            $previous = null,
            $extensions = [
                'code' => self::CODE_PERSISTED_QUERY_NOT_SUPPORTED,
            ]
        );
    }

    /**
     * @return self
     */
    public static function persistedQueriesNotFound(): self
    {
        return new self(
            'PersistedQueryNotFound',
            $nodes = null,
            $source = null,
            $positions = [],
            $path = null,
            $previous = null,
            $extensions = [
                'code' => static::CODE_PERSISTED_QUERY_NOT_FOUND,
            ]
        );
    }

    /**
     * @param  null|string  $message
     * @return self
     */
    public static function internalServerError($message = null): self
    {
        return new self(
            $message ?? '',
            $nodes = null,
            $source = null,
            $positions = [],
            $path = null,
            $previous = null,
            $extensions = [
                'code' => static::CODE_INTERNAL_SERVER_ERROR,
            ]
        );
    }

    public function isClientSafe(): bool
    {
        return true;
    }

    public function getCategory(): string
    {
        return 'apq';
    }
}
