<?php

declare(strict_types=1);

namespace SlimEdge\DataTransferObject;

enum FetchType
{
    /**
     * Fetch from query
     */
    case Query;

    /**
     * Fetch from parsed body
     */
    case Body;

    /**
     * Fetch from uploaded files in multipart form
     */
    case File;

    /**
     * Fetch from URI arguments
     */
    case Args;

    /**
     * Fetch from header line
     */
    case Header;
}