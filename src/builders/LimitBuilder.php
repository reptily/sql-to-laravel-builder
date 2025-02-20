<?php

namespace Reptily\SQLToLaravelBuilder\builders;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  offset
 *  limit
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class LimitBuilder extends AbstractBuilder implements Builder
{
    public function build(array $parts, array &$skipBag = [])
    {
        $queryVal = '';

        if (isset($parts['offset']))
            $queryVal .= "->offset(" . $parts['offset'] . ')';
        if (isset($parts['rowcount']))
            $queryVal .= "->limit(" . $parts['rowcount'] . ')';

        return $queryVal;
    }

}
