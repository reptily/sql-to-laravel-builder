<?php

namespace Reptily\SQLToLaravelBuilder\builders;

use Reptily\SQLToLaravelBuilder\utils\SelectQueryTypes;

/**
 * This class constructs and produces following Query Builder methods :
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
class SelectBuilder extends AbstractBuilder implements Builder
{
    public function build(array $parts, array &$skipBag = [])
    {
        $type = $parts['s_type'];
        $parts = $parts['parts'];

        $qb = '';
        if ($parts['distinct']) {
            $qb = $this->distinctQ();
        }

        switch ($type) {
            case SelectQueryTypes::AGGREGATE:
                $qb .= $this->aggregateQ($parts['suffix'], $parts['column'], $parts['alias'], $closeQb);
                return ['query_part' => $qb, 'type' => $closeQb ? 'lastly' : 'eq', 'close_qb' => $closeQb];
            case SelectQueryTypes::COUNT_A_TABLE:
                $skipBag[] = 'FROM';
                $qb .= $this->countAllQ($parts['table']);
                return ['query_part' => $qb, 'type' => 'eq', 'close_qb' => true];
            case SelectQueryTypes::OTHER:
                $qb .= $this->selectOnlyQ($parts['selected'], $parts['raws']);
                return ['query_part' => $qb, 'type' => 'eq', 'close_qb' => false];
            default:
                break;
        }
    }

    private function aggregateQ($suffix, $column, $alias, &$closeQb)
    {
        $closeQb = false;
        if ($alias !== false) {
            $fn = strtoupper($suffix) . '(' . $column . ')';
            $qb = "->selectRaw(" . $this->buildRawable($fn . " AS " . $alias) . ")";
        } else {
            $closeQb = true; // max(something) is the end of query / or count
            if ($column != '*') {
                $qb = '->' . $this->getValue($suffix) . '(' . $this->quote($column) . ')';
            } else {
                $qb = '->' . $this->getValue($suffix) . '()';
            }
        }

        return $qb;
    }

    private function selectOnlyQ($parts, $raws)
    {
        $columnLen = count($parts);

        if ($columnLen == 1 && $parts[0] == '*') {
            return '';
        }

        $query = '->';
        $ciPart = 'select'; // to be done selectRaw
        $query .= $ciPart . "(";
        foreach ($parts as $k => $column) {
            $query .= $this->buildRawable($column, $raws[$k]);
            if ($k + 1 != $columnLen) {
                $query .= ', ';
            }
        }
        $query .= ")";
        return $query;
    }

    private function countAllQ($table)
    {
        return 'table(' . $this->quote($table) . ')->count()';
    }

    private function distinctQ()
    {
        return '->distinct()';
    }
}
