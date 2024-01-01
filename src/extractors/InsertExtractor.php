<?php

namespace Reptily\SQLToLaravelBuilder\extractors;

/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 *  insert
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class InsertExtractor extends AbstractExtractor implements Extractor
{
    public function extract(array $value, array $parsed = [])
    {
        $columnList = [];
        foreach ($value as $val) {
            if ($val['expr_type'] == 'column-list') {
                foreach ($val['sub_tree'] as $column) {
                    $columnList[] = $column['base_expr'];
                }
            }
        }

        $records = []; // collect data so you gave only records and know about if is it batch or not
        foreach ($parsed['VALUES'] as $item)
            if ($item['expr_type'] == 'record') {
                $data = [];
                foreach ($item['data'] as $datum) {
                    $data[] = $datum['base_expr'];
                }
                $records[] = $data;
            }

        return ['columns' => $columnList, 'records' => $records];
    }
}