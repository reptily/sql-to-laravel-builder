<?php

namespace Reptily\SQLToLaravelBuilder;

use Reptily\SQLToLaravelBuilder\builders\CriterionBuilder;
use Reptily\SQLToLaravelBuilder\builders\DeleteBuilder;
use Reptily\SQLToLaravelBuilder\builders\FromBuilder;
use Reptily\SQLToLaravelBuilder\builders\GroupByBuilder;
use Reptily\SQLToLaravelBuilder\builders\HavingBuilder;
use Reptily\SQLToLaravelBuilder\builders\InsertBuilder;
use Reptily\SQLToLaravelBuilder\builders\JoinBuilder;
use Reptily\SQLToLaravelBuilder\builders\LimitBuilder;
use Reptily\SQLToLaravelBuilder\builders\OrderBuilder;
use Reptily\SQLToLaravelBuilder\builders\SelectBuilder;
use Reptily\SQLToLaravelBuilder\builders\UnionBuilder;
use Reptily\SQLToLaravelBuilder\builders\UpdateBuilder;
use Reptily\SQLToLaravelBuilder\extractors\CriterionExtractor;
use Reptily\SQLToLaravelBuilder\extractors\DeleteExtractor;
use Reptily\SQLToLaravelBuilder\extractors\FromExtractor;
use Reptily\SQLToLaravelBuilder\extractors\GroupByExtractor;
use Reptily\SQLToLaravelBuilder\extractors\HavingExtractor;
use Reptily\SQLToLaravelBuilder\extractors\InsertExtractor;
use Reptily\SQLToLaravelBuilder\extractors\JoinExtractor;
use Reptily\SQLToLaravelBuilder\extractors\LimitExtractor;
use Reptily\SQLToLaravelBuilder\extractors\OrderExtractor;
use Reptily\SQLToLaravelBuilder\extractors\SelectExtractor;
use Reptily\SQLToLaravelBuilder\extractors\UpdateExtractor;

/**
 * This class orchestrates the process between Extractors and Builders in order to produce parts of Query Builder and arranges them
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class Creator extends AbstractCreator
{
    public $options;
    public $skipBag;
    public bool $isSelect = false;

    public function __construct($options)
    {
        $this->options = $options;
        $this->skipBag = [];
    }

    public function select($value, $parsed)
    {
        $this->isSelect = true;
        $extractor = new SelectExtractor($this->options);
        $builder = new SelectBuilder($this->options);

        $parts = $extractor->extract($value, $parsed);
        $buildRes = $builder->build($parts, $this->skipBag);

        $this->qbClosed = $buildRes['close_qb'];
        if ($buildRes['type'] == 'eq') {
            $this->qb = $buildRes['query_part'];
        } else if ($buildRes['type'] == 'lastly') {
            $this->lastly = $buildRes['query_part'];
        }
    }

    public function from($value, $parsed)
    {
        $fromExtractor = new FromExtractor($this->options);
        $fromBuilder = new FromBuilder($this->options);

        if ($this->isSingleTable($parsed)) {
            $fromParts = $fromExtractor->extractSingle($value);
            $this->qb = $fromBuilder->build($fromParts, $this->skipBag) . $this->qb;
        } else { // more than one table involved ?

            $fromParts = $fromExtractor->extract($value);
            if (isset($fromParts['joins'])) { // invalid joins found ?
                throw new \Exception('Invalid join type found! ');
            } else {
                $this->qb = $fromBuilder->build($fromParts) . $this->qb;

                $joinExtractor = new JoinExtractor($this->options);
                $joinBuilder = new JoinBuilder($this->options);

                $joins = $joinExtractor->extract($value);
                $this->qb .= $joinBuilder->build($joins);
            }
        }
    }

    public function where($value)
    {
        $extractor = new CriterionExtractor($this->options);
        $builder = new CriterionBuilder($this->options);

        if ($extractor->extractAsArray($value, $part)) {
            $q = $builder->buildAsArray($part);
        }
        else {
            $parts = $extractor->extract($value);
            $q = $builder->build($parts);
        }
        $this->qb .= $q;
    }

    public function group_by($value)
    {
        $extractor = new GroupByExtractor($this->options);
        $builder = new GroupByBuilder($this->options);

        $parts = $extractor->extract($value);
        $q = $builder->build($parts);
        $this->qb .= $q;
    }

    public function limit($value)
    {
        $extractor = new LimitExtractor($this->options);
        $builder = new LimitBuilder($this->options);

        $parts = $extractor->extract($value);
        $q = $builder->build($parts);
        $this->qb .= $q;
    }

    public function having($value)
    {
        $extractor = new HavingExtractor($this->options);
        $builder = new HavingBuilder($this->options);

        $parts = $extractor->extract($value);
        $q = $builder->build($parts);
        $this->qb .= $q;
    }

    public function order($value)
    {
        $extractor = new OrderExtractor($this->options);
        $builder = new OrderBuilder($this->options);

        $parts = $extractor->extract($value);
        $q = $builder->build($parts);
        $this->qb .= $q;
    }

    public function insert($value, $parsed)
    {
        $extractor = new InsertExtractor($this->options);
        $builder = new InsertBuilder($this->options);

        $parts = $extractor->extract($value, $parsed);
        $q = $builder->build($parts);
        $this->qb .= $q;

        unset($this->options['command']);
    }

    public function update($value, $parsed)
    {
        $extractor = new UpdateExtractor($this->options);
        $builder = new UpdateBuilder($this->options);

        $parts = $extractor->extract($value, $parsed);
        $q = $builder->build($parts, $this->skipBag);
        $this->lastly = $q;
    }

    public function delete($parsed)
    {
        $extractor = new DeleteExtractor($this->options);
        $builder = new DeleteBuilder($this->options);
        $parts = $extractor->extract([], $parsed);
        $this->lastly = $builder->build($parts, $this->skipBag);
    }

    public function union($parts)
    {
        $builder = new UnionBuilder($this->options);
        $this->qb = $builder->build($parts);
    }

    function getQuery($sql, $add_facade = false)
    {
        $this->qb .= $this->lastly;
        if (empty($this->qb) || !$this->isSelect) {
            if ($add_facade) {
                $this->qb .= $this->options['facade'];
                $this->qb .= "statement('" . $sql . "')";
            }
        } else {
            if (!$this->qbClosed) {
                $this->qb .= $this->inUnion ? '' : '->get()';
            }
        }

        if (!$this->inUnion) {
            $this->qb .= ';';
        }

        return $this->qb;
    }

}
