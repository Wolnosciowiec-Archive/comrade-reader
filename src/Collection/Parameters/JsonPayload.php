<?php

namespace ComradeReader\Collection\Parameters;

/**
 * @package ComradeReader\Collection\Parameters
 */
class JsonPayload implements ParametersBagInterface
{
    /**
     * @var array $json
     */
    private $payload;

    /**
     * @param string $jsonPayload
     * @return ParametersBagInterface
     */
    public function set($jsonPayload): ParametersBagInterface
    {
        $this->payload = json_decode($jsonPayload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidARgumentException('Invalid JSON payload, cannot decode');
        }

        return $this;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $parameters
     *
     * @return array
     */
    public function buildParameters(string $url, string $method, array $parameters)
    {
        return [
            'json' => $this->payload,
        ];
    }
}