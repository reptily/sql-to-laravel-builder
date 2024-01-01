<?php

namespace Reptily\SQLToLaravelBuilder\extractors;
/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 *  groupBy
 *  groupByRaw
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class GroupByExtractor extends AbstractExtractor implements Extractor
{
    public function extract(array $value, array $parsed = [])
    {
        $parts = []; // columns
        $isRaw = false;
        foreach ($value as $val) {
            $partsTmp = [];
            $this->getExpressionParts([$val], $partsTmp); // expression parts since it can be anything! such as fn, subquery etc.
            $parts[] = $this->mergeExpressionParts($partsTmp);
            if ($this->isRaw($val)) {
                $isRaw = true;
            }
        }

        return ['parts' => $parts, 'is_raw' => $isRaw];
    }
}