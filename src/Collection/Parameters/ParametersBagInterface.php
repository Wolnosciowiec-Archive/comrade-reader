<?php

namespace ComradeReader\Collection\Parameters;

/**
 * @package ComradeReader\Collection\Parameters
 */
interface ParametersBagInterface
{
    /**
     * Parameters, a file, JSON or something else
     *
     * @param string|array $parameters
     * @return ParametersBagInterface
     */
    public function set($parameters): ParametersBagInterface;

    /**
     * @param string $url
     * @param string $method
     * @param array  $parameters
     *
     * @return array
     */
    public function buildParameters(string $url, string $method, array $parameters);
}