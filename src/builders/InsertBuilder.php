<?php

namespace RexShijaku\SQLToLaravelBuilder\builders;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  insert
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class InsertBuilder extends AbstractBuilder implements Builder
{

    public function build(array $parts, array &$skipBag = array())
    {
        $qb = '';
        $recordLen = count($parts['records']);
        $columnLen = count($parts['columns']);

        if ($recordLen == 0) {
            return false;
        }

        $isBatch = $recordLen > 1;

        $innerArrays = '';
        foreach ($parts['records'] as $recordKey => $record) {

            if (count($record) != $columnLen) {
                return '';
            }

            if ($isBatch && $recordKey > 0) {
                $innerArrays .= ", ";
            }

            $singleArray = $isBatch ? '[' : '';
            foreach ($record as $k => $colVal) {
                if ($k > 0) {
                    $singleArray .= ',';
                }
                $singleArray .= $this->quote($parts['columns'][$k]) . '=>' . ($this->wrapValue($colVal));

            }
            $innerArrays .= $singleArray . ($isBatch ? ']' : '');
        }

        if (!empty($innerArrays)) {
            $outerArray = '[' . $innerArrays . ']';
            $qb = '->insert(' . $outerArray . ')';
        }
        return $qb;
    }

}
