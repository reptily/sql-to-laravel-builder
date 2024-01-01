<?php


namespace Reptily\SQLToLaravelBuilder;

/**
 * This class provides additional functionality for the Creator class.
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class AbstractCreator
{
    public $qb;
    public $lastly;
    public $qbClosed;
    public bool $inUnion = false;

    function isSingleTable($parsed)
    {
        if (isset($parsed['FROM']) && count($parsed['FROM']) == 1) {
                return $parsed['FROM'][0]['expr_type'] == 'table';
        }

        return false;
    }

    public function resetQ()
    {
        $this->qb = '';
        $this->lastly = '';
    }
}
