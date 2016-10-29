<?php

namespace ComradeReader\Exception\Deserializer;

use ComradeReader\Exception\ReaderException;

/**
 * @package ComradeReader\Exception\Deserializer
 */
class DeserializerException extends ReaderException
{
    /**
     * @var string $response
     */
    private $response;

    /**
     * DeserializerException constructor.
     * @param string $message
     * @param string $response
     * @param \Exception $previous
     */
    public function __construct($message, $response, \Exception $previous)
    {
        $this->response = $response;
        parent::__construct($message, 0, $previous);
    }
}