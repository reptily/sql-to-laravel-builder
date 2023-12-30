<?php

namespace RexShijaku\SQLToLaravelBuilder\builders;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  groupBy
 *  groupByRaw
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class GroupByBuilder extends AbstractBuilder implements Builder
{
    public function build(array $parts, array &$skipBag = array())
    {
        $qb = '';
        $partsLen = count($parts['parts']);

        if ($partsLen == 0)
            return $qb;

        $fn = !$parts['is_raw'] ? 'groupBy' : 'groupByRaw';

        $inner = '';
        if ($partsLen == 1)
            $inner .= $this->quote($parts['parts'][0]);
        else if ($partsLen > 1) {
            if ($parts['is_raw'])
                $inner = $this->quote(implode(', ', $parts['parts']));
            else
                $inner = '[' . implode(", ", array_map(array($this, 'quote'), $parts['parts'])) . ']';
        }

        $qb .= "->" . $fn . '(' . $inner . ')';

        return $qb;
    }
}
