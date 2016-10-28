<?php

namespace ComradeReader\Service;

use Doctrine\Common\Cache\CacheProvider;
use GuzzleHttp\Client;
use Silex\Application;
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

    /** @var string $secret */
    protected $secret;

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
        $this->secret     = $apiKey;
        $this->serializer = $serializer;
        $this->cache      = $cache;
        $this->client     = new Client([
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ]);
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
     * @param string        $url
     * @param string|array  $parameters
     * @param string        $method
     * @param int           $cacheLifeTime
     *
     * @return ComradeDeserializer
     */
    public function request(
        $method,
        $url,
        $parameters = '',
        $cacheLifeTime = 500
    )
    {
        $parameters = $this->buildParameters($parameters);

        // read from cache (if available)
        if ($cacheLifeTime >= 0 && $this->cache->fetch($this->getCacheId($method, $url, $parameters))) {
            return $this->cache->fetch($this->getCacheId($method, $url, $parameters));
        }

        // send a request
        $response = $this->client
            ->request(
                $method,
                $this->getPreparedRequestUrl($url, $parameters),
                $this->getConnectionOptions()
            )->getBody()->getContents();

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
     * @param string $path
     * @param string $parameters
     * @return string
     */
    protected function getPreparedRequestUrl($path, $parameters)
    {
        return $this->url . $path . '?token=' . $this->secret . '&' . $parameters;
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
     * @param string|array $parameters
     * @return string
     */
    protected function buildParameters($parameters)
    {
        if (is_array($parameters)) {
            $parameters = http_build_query($parameters);
        }

        return $parameters;
    }
}