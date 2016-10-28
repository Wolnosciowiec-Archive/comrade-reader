<?php

namespace ComradeReader\Model\Entity;

class PaginatedResults
{
    /** @var array $results */
    private $results = [];

    /** @var int $currentPage */
    private $currentPage;

    /** @var int $maxPages */
    private $maxPages;

    /**
     * PaginatedResults constructor.
     *
     * @param array $results
     * @param int $currentPage
     * @param int $maxPages
     */
    public function __construct(array $results, $currentPage, $maxPages)
    {
        $this->results     = $results;
        $this->currentPage = $currentPage;
        $this->maxPages    = $maxPages;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @return int
     */
    public function getMaxPages()
    {
        return $this->maxPages;
    }
}