<?php

namespace Reptily\SQLToLaravelBuilder\builders;

use Reptily\SQLToLaravelBuilder\utils\CriterionTypes;

/**
 * This class constructs and produces following Query Builder methods :
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
class HavingBuilder extends AbstractBuilder implements Builder
{
    public function build(array $parts, array &$skipBag = [])
    {
        $queryVal = '';
        $groupVal = '';
        $inGroup = false;

        foreach ($parts as $part) {

            if ($inGroup && $part['type'] != 'group') {
                if (!empty($groupVal))
                    $groupVal .= ' ' . $part['sep'] . ' ';

                if (isset($part['value1']))
                    $value = $part['value1'] . ' AND ' . $part['value2']; // in between
                else
                    $value = $part['value'];

                $groupVal .= $part['field'] . ' ' . strtoupper(implode(' ', $part['operators'])) . ' ' . $value;

                continue;
            }

            switch ($part['type']) {
                case CriterionTypes::GROUP:
                    $inGroup = $this->buildGroup($part, $groupVal, $queryVal);
                    break;
                case CriterionTypes::COMPARISON:
                case CriterionTypes::IS:
                case CriterionTypes::LIKE:
                    $op = implode(' ', $part['operators']);
                    $part['value'] = $this->getValue($part['value']) == 'null' ? 'null' : $part['value'];
                    if (in_array('is', $part['operators']) || $part['raw_field'] || $part['raw_value']) {
                        $fn = $this->getValue($part['sep']) == 'or' ? 'orHavingRaw' : 'havingRaw';
                        if ($part['value'] !== 'null')
                            $inner = $this->quote($part['field'] . ' ' . $op . ' ' . '?') . ', ' . '[' . $this->wrapValue($part['value']) . ']';
                        else
                            $inner = $this->quote($part['field'] . ' ' . $op . ' ' . $this->wrapValue($part['value']));
                    } else {
                        $fn = $this->getValue($part['sep']) == 'or' ? 'orHaving' : 'having';
                        $inner = $this->quote($part['field']) . ', ' . $this->quote($op) . ', ' . $this->wrapValue($part['value']);
                    }
                    $queryVal .= '->' . $fn . '(' . $inner . ')';
                    break;
                case CriterionTypes::IN_FIELD_VALUE:
                case CriterionTypes::IN_FIELD: // for sub queries
                    $fn = $this->getValue($part['sep']) == 'or' ? 'orHavingRaw' : 'havingRaw';
                    $inner = $part['field'] . ' ' . strtoupper(implode(' ', $part['operators'])) . ' ' . $part['value'];
                    $queryVal .= '->' . $fn . '(' . $this->quote($inner) . ')';
                    break;
                case CriterionTypes::BETWEEN:
                    $queryVal .= $this->buildBetween($part);
                    break;

                case CriterionTypes::AGAINST:
                    $fn = $this->getValue($part['sep']) == 'or' ? 'orWhereRaw' : 'whereRaw';
                    $queryVal .= '->' . $fn . '(' . $this->quote($part['field'] . ' AGAINST ' . $part['value']) . ')';
                    break;
                default:
                    break;
            }

        }
        return $queryVal;
    }

    private function buildBetween($part)
    {
        $prefix = $part['sep'] != 'and' ? 'or' : '';

        $query = '->';
        if ($prefix == 'or' || in_array('not', $part['operators'])) { // since not having not present, use having raw
            $fn = 'havingRaw';
            if ($prefix == 'or')
                $fn = 'orHavingRaw';

            $operators = strtoupper(implode(' ', $part['operators']));
            $inner = $part['field'] . ' ' . $operators . ' ' . $part['value1'] . ' AND ' . $part['value2'] . '';
            $query .= $fn . '(' . $this->quote($inner) . ')';
        } else {
            $query .= 'havingBetween(' . $this->buildRawable($part['field'], $part['raw_field']) . ', ';
            $query .= '[' . $this->buildRawable($part['value1'], $part['raw_values'][0]) . ', ' .
                $this->buildRawable($part['value2'], $part['raw_values'][1]) . ']' . ')';
        }

        return $query;
    }

    private function buildGroup($part, &$groupVal, &$queryVal)
    {
        if (in_array($part['se'], ['start', 'end'])) {

            if ($part['se'] == 'start'){
                return true;
            }
            else if ($part['se'] == 'end') {
                $queryVal .= '->havingRaw(' . $this->quote('(' . $groupVal . ')') . ')';
                $groupVal = ''; // reset collected

                return false;
            }
        }
    }
}
