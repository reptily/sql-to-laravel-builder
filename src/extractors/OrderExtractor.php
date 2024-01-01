<?php

namespace Reptily\SQLToLaravelBuilder\extractors;
/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 *  orderBy
 *  orderByRaw
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class OrderExtractor extends AbstractExtractor implements Extractor
{
    public function extract(array $value, array $parsed = [])
    {
        $this->getExpressionParts($value, $partsTemp);
        $parts = [];
        foreach ($value as $k => $val) {
            $parts[] = ['field' => $partsTemp[$k], 'dir' => $val['direction'], 'type' => 'normal', 'raw' => $this->isRaw($val)];
        }

        return $parts;
    }
}