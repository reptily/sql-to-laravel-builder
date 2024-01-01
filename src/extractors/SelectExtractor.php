<?php

namespace Reptily\SQLToLaravelBuilder\extractors;

use Reptily\SQLToLaravelBuilder\utils\SelectQueryTypes;

/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 *  table
 *  distinct
 *  select
 *  sum
 *  avg
 *  min
 *  max
 *  count
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class SelectExtractor extends AbstractExtractor implements Extractor
{
    public function extract(array $value, array $parsed = [])
    {
        $distinct = $this->isDistinct($value);
        if ($distinct) {
            array_shift($value);
        }

        if ($this->isSingleTable($parsed) &&
            $this->isCountTable($value) && $this->validCountTable($parsed)) {

            return [
                's_type' => SelectQueryTypes::COUNT_A_TABLE,
                'parts' => ['table' => $parsed['FROM'][0]['base_expr'], 'distinct' => $distinct, 'selected' => 'COUNT(*)'],
            ];
        } else if ($this->isAggregate($value)) {
            return ['s_type' => SelectQueryTypes::AGGREGATE,
                'parts' => $this->extractAggregateParts($value, $distinct)];
        }

        $this->getExpressionParts($value, $parts, $raws);

        return ['s_type' => SelectQueryTypes::OTHER, 'parts' => ['selected' => $parts, 'distinct' => $distinct, 'raws' => $raws]];
    }

    private function isAggregate($value)
    {
        return count($value) == 1 && $value[0]['expr_type'] == 'aggregate_function'
            && in_array($this->getValue($value[0]['base_expr']), $this->options['settings']['agg']);
    }

    private function isDistinct($value)
    {
        return count($value) > 0 && $value[0]['expr_type'] == 'reserved' && $this->getValue($value[0]['base_expr']) == 'distinct';
    }

    private function isCountTable($value)
    {
        return count($value) == 1 && $value[0]['expr_type'] == 'aggregate_function' && $this->getValue($value[0]['base_expr']) == 'count' && $this->getFnParams($value[0], $d) === "*";
    }

    private function extractAggregateParts($value, $distinct)
    {
        $fn_suffix = $this->getValue($value[0]['base_expr']);
        $this->getExpressionParts($value[0]['sub_tree'], $parts);
        $column = implode('', $parts);

        $alias = $this->hasAlias($value[0]);
        if ($alias) {
            $alias = $value[0]['alias']['name'];
        }

        return ['suffix' => $fn_suffix, 'column' => $column, 'alias' => $alias, 'distinct' => $distinct];
    }

}