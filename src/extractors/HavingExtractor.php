<?php

namespace Reptily\SQLToLaravelBuilder\extractors;

use Reptily\SQLToLaravelBuilder\utils\CriterionContext;

/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 *  having
 *  orHaving
 *  havingRaw
 *  orHavingRaw
 *  havingBetween
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class HavingExtractor extends AbstractExtractor implements Extractor
{
    public function extract(array $value, array $parsed = [])
    {
        $criterion = new CriterionExtractor($this->options);
        $criterion->getCriteriaParts($value, $parts, CriterionContext::HAVING);

        return $parts;
    }
}
