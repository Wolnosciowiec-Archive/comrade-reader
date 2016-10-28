<?php

namespace ComradeReader\Service;

use ComradeReader\Model\Entity\PaginatedResults;
use Symfony\Component\Serializer\Serializer;

/**
 * Deserializer
 * ============
 *   Decodes a JSON string into single object or into
 *   multiple objects. Supports paginated responses.
 *
 * @package ComradeReader\Service
 */
class ComradeDeserializer
{
    /** @var string $response */
    private $response;

    /** @var Serializer $serializer */
    protected $serializer;

    /**
     * @param string     $response
     * @param Serializer $serializer
     */
    public function __construct($response, Serializer $serializer)
    {
        $this->response   = $response;
        $this->serializer = $serializer;
    }

    /**
     * @return string
     */
    public function getPlainResponse()
    {
        return $this->response;
    }

    /**
     * @return array
     */
    public function decodeIntoArray()
    {
        $decoded = json_decode($this->getPlainResponse(), true);

        if (isset($decoded['success'])
            && $decoded['success']
            && isset($decoded['data'])
            && is_array($decoded['data']))
        {
            return $decoded['data'];
        }

        return $decoded;
    }

    /**
     * @param string $targetEntity
     * @return object
     */
    public function decode($targetEntity)
    {
        $response = $this->_convertNaming(
            $this->decodeIntoArray()
        );
        return $this->serializer->deserialize(json_encode($response), $targetEntity, 'json');
    }

    /**
     * Decodes a response into array of objects or into a PaginatedResults
     *
     * @param string $targetEntity
     * @return object[]|PaginatedResults
     */
    public function decodeMultiple($targetEntity)
    {
        $responseDecoded = $this->decodeIntoArray();

        // paginator
        $responseObjects = isset($responseDecoded['results']) ? $responseDecoded['results'] : $responseDecoded;
        $responseObjects = array_map(

            function ($item) use ($targetEntity)
            {
                $item = $this->_convertNaming($item);
                return $this->serializer->deserialize(json_encode($item), $targetEntity, 'json');
            },

            $responseObjects
        );

        if (isset($responseDecoded['results'])) {
            return new PaginatedResults(
                $responseObjects,
                $responseDecoded['current_page'],
                $responseDecoded['max_pages']
            );
        }

        return $responseObjects;
    }

    /**
     * @param array $array
     * @return array
     */
    private function _convertNaming($array)
    {
        $result = [];

        foreach ($array as $key => $value) {
            $key = lcfirst(implode('', array_map('ucfirst', explode('_', $key))));
            $result[$key] = $value;
        }

        return $result;
    }
}