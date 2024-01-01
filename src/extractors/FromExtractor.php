<?php

namespace Reptily\SQLToLaravelBuilder\extractors;

/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 * table
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class FromExtractor extends AbstractExtractor implements Extractor
{
    public function extract(array $value, array $parsed = [])
    {
        $parts = [];

        foreach ($value as $val) {
            if (!isset($parts['table'])) {
                $parts = $this->extractSingle($value);
            } else {
                if (!$this->validJoin($val['join_type'])) { // such as natural join
                    $join = [
                        'type' => $val['join_type'],
                        'table_expr' => $val['base_expr'],
                    ];
                    $parts['joins'][] = $join;
                }
            }
        }
        
        return $parts;
    }

    public function extractSingle($value)
    {
        $isRaw = $value[0]['expr_type'] != 'table';
        $table = $this->getWithAlias($value[0], $isRaw);
        
        return ['table' => $table, 'is_raw' => $isRaw];
    }
}