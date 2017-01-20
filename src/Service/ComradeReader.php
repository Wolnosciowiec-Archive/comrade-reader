<?php

namespace ComradeReader\Service;

use ComradeReader\Collection\Parameters\ParametersBagInterface;
use ComradeReader\Exception\InvalidArgumentException;
use ComradeReader\Exception\ResourceNotFoundException;
use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

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
    /** @var ClientInterface $client */
    protected $client;

    /** @var string $url */
    protected $url;

    /** @var string $secretToken */
    protected $secretToken;

    /** @var string $tokenFieldName */
    protected $tokenFieldName = 'token';

    /** @var object $serializer */
    protected $serializer;

    /** @var CacheProvider $cache */
    protected $cache;

    /** @var string[] $headers */
    protected $headers = [];

    /**
     * @param string        $apiUrl
     * @param string        $apiKey
     * @param object        $serializer A Symfony serializer Component, JMS Serializer or other
     * @param CacheProvider $cache
     */
    public function __construct(
        $apiUrl,
        $apiKey,
        $serializer,
        CacheProvider $cache
    ) {
        $this->url        = $apiUrl;
        $this->secretToken     = $apiKey;
        $this->serializer = $serializer;
        $this->cache      = $cache;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return ClientInterface
     */
    protected function getClient()
    {
        if (!$this->client instanceof ClientInterface) {
            $this->client     = new Client([
                'headers' => array_merge(
                    [
                        'Content-Type' => 'application/json'
                    ],
                    $this->headers
                ),
            ]);
        }

        return $this->client;
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
            'headers'         => $this->headers,
            //'debug'           => true,
        ];
    }

    /**
     * Make a request to the server
     * and return a decoded response
     *
     * @param string                      $method
     * @param string                      $url
     * @param ParametersBagInterface|null $parameters
     * @param int                         $cacheLifeTime
     *
     * @throws InvalidArgumentException
     * @throws ResourceNotFoundException
     *
     * @return ComradeDeserializer
     */
    public function request(
        $method,
        $url,
        ParametersBagInterface $parameters = null,
        $cacheLifeTime = 500
    ) {
        $parameters = $this->buildParameters($url, $method, $parameters);

        // read from cache (if available)
        if ($cacheLifeTime >= 0 && $this->cache->fetch($this->getCacheId($method, $url, $parameters))) {
            return new ComradeDeserializer(
                unserialize($this->cache->fetch($this->getCacheId($method, $url, $parameters))),
                $this->serializer
            );
        }

        // send a request
        try {
            $response = $this->getClient()
                ->request(
                    $method,
                    $this->getPreparedRequestUrl($url),
                    $parameters
                );
        }
        catch (RequestException $e) {
            if ($e->getCode() === 404) {
                throw new ResourceNotFoundException($url);
            }

            throw $e;
        }

        // update cache with a new response from server
        if ($cacheLifeTime >= 0) {
            $this->cache->save(
                $this->getCacheId($method, $url, $parameters),
                serialize($response),
                $cacheLifeTime
            );
        }

        return new ComradeDeserializer($response, $this->serializer);
    }

    /**
     * Alias to request()
     *
     * @param string $url
     * @param ParametersBagInterface|null $parameters
     * @param int    $cacheLifeTime
     *
     * @throws ResourceNotFoundException
     * @return ComradeDeserializer
     */
    public function get(
        $url,
        ParametersBagInterface $parameters = null,
        $cacheLifeTime = 500
    ) {
        return $this->request('GET', $url, $parameters, $cacheLifeTime);
    }

    /**
     * Alias to request(), with "POST" preselected as a HTTP method
     *
     * @param string                       $url
     * @param ParametersBagInterface|null  $parameters
     * @param int                          $cacheLifeTime
     *
     * @throws ResourceNotFoundException
     * @return ComradeDeserializer
     */
    public function post(
        $url,
        ParametersBagInterface $parameters = null,
        $cacheLifeTime = 500
    ) {
        return $this->request('POST', $url, $parameters, $cacheLifeTime);
    }

    /**
     * Alias to request(), with "PUT" preselected as a HTTP method
     *
     * @param string                       $url
     * @param ParametersBagInterface|null  $parameters
     * @param int                          $cacheLifeTime
     *
     * @throws ResourceNotFoundException
     * @return ComradeDeserializer
     */
    public function put(
        $url,
        ParametersBagInterface $parameters = null,
        $cacheLifeTime = 500
    ) {
        return $this->request('PUT', $url, $parameters, $cacheLifeTime);
    }

    /**
     * Alias to request(), with "DELETE" preselected as a HTTP method
     *
     * @param string                       $url
     * @param ParametersBagInterface|null  $parameters
     * @param int                          $cacheLifeTime
     *
     * @throws ResourceNotFoundException
     * @return ComradeDeserializer
     */
    public function delete(
        $url,
        ParametersBagInterface $parameters = null,
        $cacheLifeTime = 500
    ) {
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
     * @param ParametersBagInterface $parametersBag
     *
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function buildParameters($url, $method, ParametersBagInterface $parametersBag = null)
    {
        return array_merge(
            $this->getConnectionOptions(),
            $parametersBag instanceof ParametersBagInterface ?
                $parametersBag->buildParameters($url, $method, [$this->getTokenFieldName() => $this->secretToken]) : []
        );
    }
}