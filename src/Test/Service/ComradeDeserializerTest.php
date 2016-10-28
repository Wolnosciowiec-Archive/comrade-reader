<?php

namespace ComradeReader\Test\Service;

use ComradeReader\Model\Entity\PaginatedResults;
use ComradeReader\Service\ComradeDeserializer;
use ComradeReader\Test\Helpers\SimpleTestEntity;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @see ComradeDeserializer
 * @package ComradeReader\Test\Service
 */
class ComradeDeserializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $response
     * @return ComradeDeserializer
     */
    private function constructComrade($response)
    {
        return new ComradeDeserializer(json_encode($response), new Serializer([
            new PropertyNormalizer()
        ], [
            new JsonDecode()
        ]));
    }

    /**
     * @see ComradeDeserializer::decode()
     */
    public function testDecode()
    {
        $comrade = $this->constructComrade([
            'success' => true,
            'data'    => [
                'id'        => 1,
                'colorName' => 'red',
            ]
        ]);

        /** @var SimpleTestEntity $decoded */
        $decoded = $comrade->decode(SimpleTestEntity::class);

        $this->assertSame(1, $decoded->getId());
        $this->assertSame('red', $decoded->getColorName());
    }

    /**
     * @see ComradeDeserializer::decodeMultiple()
     */
    public function testDecodeMultiple()
    {
        $comrade = $this->constructComrade([
            'success' => true,
            'data'    => [
                [
                    'id'        => 1,
                    'colorName' => 'red',
                ],
                [
                    'id'        => 2,
                    'colorName' => 'black',
                ]
            ]
        ]);

        /** @var SimpleTestEntity[] $decoded */
        $decoded = $comrade->decodeMultiple(SimpleTestEntity::class);

        $this->assertCount(2, $decoded);
    }

    /**
     * @see ComradeDeserializer::decodeIntoArray()
     */
    public function testDecodeIntoArray()
    {
        $comrade = $this->constructComrade([
            'success' => true,
            'data'    => [
                'id'        => 1,
                'colorName' => 'Black & Red',
            ]
        ]);

        /** @var array $decoded */
        $decoded = $comrade->decodeIntoArray();

        $this->assertInternalType('array', $decoded);
        $this->assertSame('Black & Red', $decoded['colorName']);
    }

    /**
     * @see ComradeDeserializer::getPlainResponse()
     */
    public function testGetPlainResponse()
    {
        $request = [
            'success' => true,
            'data'    => [
                'id'        => 1,
                'colorName' => 'Black & Red',
            ]
        ];
        $comrade = $this->constructComrade($request);

        $this->assertSame(json_encode($request), $comrade->getPlainResponse());
    }

    /**
     * @see ComradeDeserializer::decodeMultiple()
     */
    public function testPaginatedResults()
    {
        $comrade = $this->constructComrade([
            'success' => true,
            'data'    => [
                'results' => [
                    [
                        'id'        => 1,
                        'colorName' => 'Black & Red',
                    ],
                ],

                'current_page' => 1,
                'max_pages'    => 1,
            ]
        ]);

        /** @var PaginatedResults $results */
        $results =  $comrade->decodeMultiple(SimpleTestEntity::class);

        $this->assertInstanceOf(
            PaginatedResults::class,
            $results
        );

        $this->assertInstanceOf(SimpleTestEntity::class, current($results->getResults()));
    }
}