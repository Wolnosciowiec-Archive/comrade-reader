<?php

namespace ComradeReader\Test\Service;
use ComradeReader\Service\ComradeReader;
use ComradeReader\Test\Helpers\SimpleTestEntity;
use Doctrine\Common\Cache\VoidCache;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @package ComradeReader\Service
 */
class ComradeReaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return ComradeReader
     */
    private function getReader()
    {
        $serializer = new Serializer(
            [new PropertyNormalizer()], [new JsonDecode()]
        );

        return new ComradeReader('http://localhost:8005', 'test', $serializer, new VoidCache());
    }

    /**
     * Basic test for valid request
     *
     * @see examples/test-api-responses/colors.php
     */
    public function testGet()
    {
        /** @var SimpleTestEntity $color */
        $color = $this->getReader()->get('/colors.php')->decode(SimpleTestEntity::class);

        $this->assertSame('red', $color->getColorName());
        $this->assertSame(1, $color->getId());
    }

    /**
     * Test passing POST parameters
     */
    public function testPassingPostAndGetParameters()
    {
        $reader = $this->getReader();
        $reader->setTokenFieldName('custom_token_field_name');

        $response = $reader->post(
            '/post-parameters.php?this_is_a_get_parameter=true', ['integer' => 1], 500
        )->getData();

        $this->assertSame('true', $response['GET']['this_is_a_get_parameter']);
        $this->assertSame('test', $response['GET']['custom_token_field_name']);
        $this->assertSame('1', $response['POST']['integer']);
    }

    /**
     * @expectedException \ComradeReader\Exception\ResourceNotFoundException
     * @expectedExceptionMessage Resource "/this-should-not-be-found" was not found on remote server, got a HTTP 404 error while trying to get the resource. Please verify if the resource exists, and if the HTTP method is correct
     */
    public function testNotFound()
    {
        $this->getReader()->request('GET', '/this-should-not-be-found')
            ->decode(SimpleTestEntity::class);
    }
}