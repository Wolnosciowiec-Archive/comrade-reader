<?php

namespace ComradeReader\Test\Helpers;

/**
 * @package ComradeReader\Test\Helpers
 */
class SimpleTestEntity
{
    /** @var string $id */
    private $id;

    /** @var string $colorName */
    private $colorName;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getColorName()
    {
        return $this->colorName;
    }

    /**
     * @param string $colorName
     * @return SimpleTestEntity
     */
    public function setColorName($colorName)
    {
        $this->colorName = $colorName;
        return $this;
    }

    /**
     * @param string $id
     * @return SimpleTestEntity
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
}