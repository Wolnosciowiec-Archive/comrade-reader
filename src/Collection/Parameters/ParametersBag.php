<?php

namespace ComradeReader\Collection\Parameters;

/**
 * @package ComradeReader\Collection\Parameters
 */
class ParametersBag implements ParametersBagInterface
{
    /**
     * @var array $parameters
     */
    private $parameters;

    public function set($parameters): ParametersBagInterface
    {
        if (!is_array($parameters)) {
            throw new \InvalidArgumentException('$parameters for ParamtersBag should be an array');
        }

        $this->parameters = $parameters;
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
        if (!is_array($this->parameters)) {
            throw new \InvalidArgumentException('$parameters should be of type array');
        }

        // merge query string from the url
        $extractedQueryFromUrl = parse_url($url, PHP_URL_QUERY);

        if (strlen($extractedQueryFromUrl) > 0) {
            parse_str($extractedQueryFromUrl, $queryArray);

            $parameters = array_merge($parameters, $queryArray);
        }

        if ('POST' === $method || 'PUT' === $method) {
            return [
                'query'       => $parameters,
                'form_params' => $this->parameters,
            ];
        }

        return [
            'query' => array_merge($parameters, $this->parameters),
        ];
    }
}
