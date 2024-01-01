<?php

namespace Reptily\SQLToLaravelBuilder\builders;

use Reptily\SQLToLaravelBuilder\utils\CriterionTypes;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  where
 *  orWhere
 *  whereRaw
 *  orWhereRaw
 *
 *  whereBetween
 *  orWhereNotBetween
 *  whereIn
 *  orWhereIn
 *  whereNotIn
 *  orWhereNotIn
 *
 *  whereNull
 *  whereNotNull
 *
 *  Logical Grouping methods
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class CriterionBuilder extends AbstractBuilder implements Builder
{

    public function build(array $parts, array &$skipBag = array())
    {
        $queryVal = '';

        foreach ($parts as $part) {

            switch ($part['type']) {

                case CriterionTypes::COMPARISON:
                case CriterionTypes::IS:
                case CriterionTypes::LIKE:
                    $op = implode(' ', $part['operators']);
                    $part['value'] = $this->getValue($part['value']) == 'null' ? 'null' : $part['value'];

                    if ($part['raw_field'] || $part['raw_value']) {

                        $fn = $this->getValue($part['sep']) == 'or' ? 'orWhereRaw' : 'whereRaw';

                        if ($part['raw_field'] && !$part['raw_value'] && $this->getValue($part['value']) !== 'null')
                            $inner = $this->quote($part['field'] . ' ' . strtoupper($op) . ' ? ') . ', ' . '[' . $this->wrapValue($part['value']) . ']';
                        else
                            $inner = $this->quote($part['field'] . ' ' . strtoupper($op) . ' ' . $part['value']) . '';
                    } else {

                        $fnParts = $this->getValue($part['sep']) == 'or' ? ['or', 'where'] : ['where'];

                        if ($part['value'] == 'null') {
                            if ($op == 'is not') {
                                $fnParts[] = 'not';
                                $fnParts[] = 'null';
                            } else if ($op == 'is')
                                $fnParts[] = 'null';
                            $inner = $this->quote($part['field']);
                        } else
                            $inner = $this->quote($part['field']) . ', ' . $this->quote(strtoupper($op)) . ', ' . $this->wrapValue($part['value']);
                        $fn = $this->fnMerger($fnParts);
                    }
                    $queryVal .= '->' . $fn . '(' . $inner . ')';
                    break;

                case CriterionTypes::IN_FIELD_VALUE:
                case CriterionTypes::IN_FIELD: // for sub queries

                    $valuePHPArr = (isset($part['as_php_arr']) && $part['as_php_arr'] == true);

                    if ($part['raw_field'] || ($part['raw_value'] && !$valuePHPArr)) {
                        // Raw Methods
                        $fn = $this->getValue($part['sep']) == 'or' ? 'orWhereRaw' : 'whereRaw';
                        $queryVal .= '->' . $fn . '(' . $this->quote($part['field'] . ' ' . strtoupper(implode(' ', $part['operators'])) . ' ' . $part['value']) . ')';

                    } else {
                        // Additional Where Clauses
                        $operatorTokens = $this->getValue($part['sep']) == 'or' ? ['or', 'where'] : ['where'];
                        $operatorTokens = array_merge($operatorTokens, $part['operators']); // not + in part (depending on what was passed)
                        $fn = $this->fnMerger($operatorTokens);

                        $queryVal .= '->' . $fn . '(' . $this->quote($part['field']) . ', ';
                        if ($valuePHPArr) {
                            $queryVal .= '[' . $this->unBracket($part['value']) . ']';
                        } else {
                            $queryVal .= '[' . $this->wrapValue($part['value']) . ']';
                        }
                        $queryVal .= ')';
                    }
                    break;
                case CriterionTypes::BETWEEN:
                    $queryVal .= $this->buildBetween($part);
                    break;
                case CriterionTypes::GROUP:
                    $this->buildGroup($part, $queryVal);
                    break;
                case CriterionTypes::AGAINST:
                    $fn = $this->getValue($part['sep']) == 'or' ? 'orWhereRaw' : 'whereRaw';
                    $queryVal .= '->' . $fn . '(' . $this->quote($part['field'] . ' AGAINST ' . $part['value']) . ')';
                    break;
                case CriterionTypes::FN:
                    $fn = $this->getValue($part['sep']) == 'or' ? 'orWhere' : 'where';
                    $fn = $this->fnMerger(array($fn, $part['fn']));
                    $op = $part['operator'];
                    $inner = $this->quote($part['field']) . ', ' . $this->quote(strtoupper($op)) . ', ' . $this->wrapValue($part['value']['value']);
                    $queryVal .= '->' . $fn . '(' . $inner . ')';
                    break;
                default:
                    break;
            }
        }

        return $queryVal;
    }

    public function buildAsArray(array $parts)
    {
        $queryVal = $this->arrayify($parts);
        if ($queryVal !== false) {
            return '->where(' . $queryVal . ')';
        }

        return false;
    }

    private function buildGroup($part, &$queryVal)
    {
        if (in_array($part['se'], ['start', 'end'])) {
            $fn = '';
            if ($part['se'] == 'start') {
                $queryVal .= "->";

                if ($part['has_negation']) {  // when not is before group, only where can be used!
                    $fn = 'where(function (Builder $query) { $query';
                } else {
                    if ($part['log'] == 'or') {
                        $fn = 'orWhere(function (Builder $query) { $query';
                    } else {
                        $fn = 'where(function (Builder $query) { $query';
                    }
                }


            } else if ($part['se'] == 'end') {
                if ($part['has_negation']) {
                    $fn = ';}, null, null, \'' . $part['log'] . ' ' . 'not' . '\')'; // see https://github.com/laravel/ideas/issues/708
                } else {
                    $fn = ';})';
                }
            }

            $queryVal .= $fn;
        }
    }

    private function buildBetween($part)
    {
        $query = '->';
        $prefix = $part['sep'] == 'and' ? null : 'or';
        if (in_array('not', $part['operators'])) { // is not between?
            $fnParts = ['where', 'not', 'between'];
            if ($prefix == 'or') {
                array_unshift($fnParts, $prefix);
            }
            $fn = $this->fnMerger($fnParts);
            $query .= $fn . '(' . $this->buildRawable($part['field'], $part['raw_field']) . ', ';
        } else { // is simply between ?
            $fnParts = ['where', 'between'];
            if ($prefix == 'or') {
                array_unshift($fnParts, $prefix);
            }
            $fn = $this->fnMerger($fnParts);
            $query .= $fn . '(' . $this->buildRawable($part['field'], $part['raw_field']) . ', ';
        }

        $query .= '[' . $this->buildRawable($part['value1'], $part['raw_values'][0]) . ', ' .
            $this->buildRawable($part['value2'], $part['raw_values'][1]) . ']' . ')';

        return $query;
    }
}
