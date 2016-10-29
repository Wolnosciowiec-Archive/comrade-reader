<?php

namespace ComradeReader\Service;

use ComradeReader\Exception\InvalidArgumentException;
use ComradeReader\Exception\ResourceNotFoundException;
use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Serializer\Serializer;

/**
 * Comrade Reader
 * ==============
 *   Makes requests to API and allows
 *   to decode response to objects
 *
 * Written for Wolnościowiec as a bridge
 * between microservices and comrades who
 * wants to share the anarchist events,
 * articles and news
 *
 * @url http://wolnosciowiec.net
 * @package ComradeReader\Service
 */
class ComradeReader
{
    /** @var Client $client */
    protected $client;

    /** @var string $url */
    protected $url;

    /** @var string $secretToken */
    protected $secretToken;

    /** @var string $tokenFieldName */
    protected $tokenFieldName = 'token';

    /** @var Serializer $serializer */
    protected $serializer;

    /** @var CacheProvider $cache */
    protected $cache;

    /**
     * @param string        $apiUrl
     * @param string        $apiKey
     * @param Serializer    $serializer
     * @param CacheProvider $cache
     */
    public function __construct(
        $apiUrl,
        $apiKey,
        Serializer $serializer,
        CacheProvider $cache)
    {
        $this->url        = $apiUrl;
        $this->secretToken     = $apiKey;
        $this->serializer = $serializer;
        $this->cache      = $cache;
        $this->client     = new Client([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ]);
    }

    /**
     * @param string $tokenFieldName
     * @return ComradeReader
     */
    public function setTokenFieldName($tokenFieldName)
    {
        $this->tokenFieldName = $tokenFieldName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTokenFieldName()
    {
        return $this->tokenFieldName;
    }

    /**
     * @return array
     */
    protected function getConnectionOptions()
    {
        return [
            'connect_timeout' => 15,
            'timeout'         => 15,
            //'debug'           => true,
        ];
    }

    /**
     * Make a request to the server
     * and return a decoded response
     *
     * @param string       $method
     * @param string       $url
     * @param array        $parameters
     * @param int          $cacheLifeTime
     *
     * @throws InvalidArgumentException
     * @throws ResourceNotFoundException
     *
     * @return ComradeDeserializer
     */
    public function request(
        $method,
        $url,
        $parameters = [],
        $cacheLifeTime = 500
    )
    {
        $parameters = $this->buildParameters($url, $method, $parameters);

        // read from cache (if available)
        if ($cacheLifeTime >= 0 && $this->cache->fetch($this->getCacheId($method, $url, $parameters))) {
            return new ComradeDeserializer(
                $this->cache->fetch($this->getCacheId($method, $url, $parameters)),
                $this->serializer
            );
        }

        // send a request
        try {
            $response = $this->client
                ->request(
                    $method,
                    $this->getPreparedRequestUrl($url),
                    $parameters
                )->getBody()->getContents();

        }
        catch (ClientException $e) {
            if ($e->getCode() === 404) {
                throw new ResourceNotFoundException($url);
            }

            throw $e;
        }

        // update cache with a new response from server
        if ($cacheLifeTime >= 0) {
            $this->cache->save(
                $this->getCacheId($method, $url, $parameters),
                $response,
                $cacheLifeTime
            );
        }

        return new ComradeDeserializer($response, $this->serializer);
    }

    /**
     * Alias to request()
     *
     * @param string $url
     * @param array  $parameters
     * @param int    $cacheLifeTime
     *
     * @throws ResourceNotFoundException
     * @return ComradeDeserializer
     */
    public function get(
        $url,
        $parameters = [],
        $cacheLifeTime = 500
    )
    {
        return $this->request('GET', $url, $parameters, $cacheLifeTime);
    }

    /**
     * Alias to request(), with "POST" preselected as a HTTP method
     *
     * @param string $url
     * @param array  $parameters
     * @param int    $cacheLifeTime
     *
     * @throws ResourceNotFoundException
     * @return ComradeDeserializer
     */
    public function post(
        $url,
        $parameters = [],
        $cacheLifeTime = 500
    )
    {
        return $this->request('POST', $url, $parameters, $cacheLifeTime);
    }

    /**
     * Alias to request(), with "PUT" preselected as a HTTP method
     *
     * @param string $url
     * @param array  $parameters
     * @param int    $cacheLifeTime
     *
     * @throws ResourceNotFoundException
     * @return ComradeDeserializer
     */
    public function put(
        $url,
        $parameters = [],
        $cacheLifeTime = 500
    )
    {
        return $this->request('PUT', $url, $parameters, $cacheLifeTime);
    }

    /**
     * Alias to request(), with "DELETE" preselected as a HTTP method
     *
     * @param string $url
     * @param array  $parameters
     * @param int    $cacheLifeTime
     *
     * @throws ResourceNotFoundException
     * @return ComradeDeserializer
     */
    public function delete(
        $url,
        $parameters = [],
        $cacheLifeTime = 500
    )
    {
        return $this->request('DELETE', $url, $parameters, $cacheLifeTime);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function getPreparedRequestUrl($path)
    {
        return $this->url . $path;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $parameters
     * @return string
     */
    protected function getCacheId($method, $url, $parameters)
    {
        return md5($method . $url . json_encode($parameters));
    }

    /**
     * @param string $url
     * @param string $method
     * @param array  $parameters
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function buildParameters($url, $method, $parameters)
    {
        if (!is_array($parameters)) {
            throw new \InvalidArgumentException('$parameters should be of type array');
        }

        $query = [
            $this->getTokenFieldName() => $this->secretToken
        ];

        // merge query string from the url
        $extractedQueryFromUrl = parse_url($url, PHP_URL_QUERY);

        if (strlen($extractedQueryFromUrl) > 0) {
            parse_str($extractedQueryFromUrl, $queryArray);

            $query = array_merge($query, $queryArray);
        }

        if ('POST' === $method) {
            return array_merge($this->getConnectionOptions(), [
                'query'       => $query,
                'form_params' => $parameters,
            ]);
        }

        return array_merge($this->getConnectionOptions(), [
            'query' => array_merge($query, $parameters),
        ]);
    }
}