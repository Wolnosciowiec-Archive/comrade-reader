<?php

namespace ComradeReader\Exception;

/**
 * Resource not found / 404 HTTP Error
 *
 * @package ComradeReader\Exception
 */
class ResourceNotFoundException extends ReaderException
{
    /**
     * @param string          $resource
     * @param \Exception|null $previous
     */
    public function __construct($resource, \Exception $previous = null)
    {
        parent::__construct(
            'Resource "' . $resource . '" was not found on remote server, got a HTTP 404 error while trying to get the resource. ' .
                'Please verify if the resource exists, and if the HTTP method is correct',
            404,
            $previous
        );
    }
}