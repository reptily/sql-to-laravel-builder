<?php

namespace Reptily\SQLToLaravelBuilder\extractors;

use Reptily\SQLToLaravelBuilder\utils\CriterionContext;
use Reptily\SQLToLaravelBuilder\utils\CriterionTypes;

/**
 * This class extracts and compiles SQL query parts for the following Query Builder methods :
 *
 *  where
 *  orWhere
 *  whereRaw
 *  orWhereRaw
 *
 *  whereBetween
 *  orWhereNotBetween
 *  whereIn
 *  whereNotIn
 *
 *  whereNull
 *  whereNotNull
 *
 *  Logical Grouping methods
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class CriterionExtractor extends AbstractExtractor implements Extractor
{
    private bool $negationOn = false;
    private bool $handleOuterNegation = false;

    public function extract(array $value, array $parsed = [])
    {
        $this->getCriteriaParts($value, $parts);

        return $parts;
    }

    function extractAsArray($value, &$part)
    {
        if (!$this->options['group']) {
            $part = false;

            return false;
        }

        $part = $this->getArrayParts($value); // passed by reference value changed
        if ($part !== false) {
            return true;
        }

        return false;
    }

    function getCriteriaParts($value, &$parts = [], $context = CriterionContext::WHERE, $handleOuterNegation = true)
    {
        $currIndex = 0;
        $logicalOperator = 'and';

        foreach ($value as $index => $val) {

            if ($index < $currIndex) {
                continue; // skip those collected in inner loop
            }

            if (in_array($val['expr_type'], array('operator', 'reserved'))) { // reserved since k+10 in (in is considered reserved)
                $sep = $this->getValue($val['base_expr']);
                if ($this->isLogicalOperator($sep)) {
                    $logicalOperator = $this->getValue($val['base_expr']);
                    continue;
                }

                switch ($this->getValue($sep)) {
                    case $this->isComparisonOperator($sep):
                        $this->handleOuterNegation = $handleOuterNegation;
                        $resField = $this->getLeft($index, $value, $context);
                        $resValue = $this->getRight($index, $value, $currIndex, $context);

                        $parts[] = [
                            'type' => CriterionTypes::COMPARISON,
                            'operators' =>[strtolower($sep)],
                            'field' => $resField['value'],
                            'value' => $resValue['value'],
                            'raw_field' => $resField['is_raw'],
                            'raw_value' => $resValue['is_raw'],
                            'sep' => $logicalOperator,
                            'const_value' => $resValue['is_const'],
                        ];
                        break;
                    case 'is':

                        $this->handleOuterNegation = $handleOuterNegation;

                        $resField = $this->getLeft($index, $value);
                        $resValue = $this->getRight($index, $value, $currIndex, $context);

                        $operators = ['is'];
                        if ($resValue['has_negation'])
                            $operators[] = 'not';

                        $parts[] = [
                            'type' => CriterionTypes::IS,
                            'operators' => $operators,
                            'field' => $resField['value'],
                            'value' => $resValue['value'],
                            'raw_field' => $resField['is_raw'],
                            'raw_value' => $resValue['is_raw'],
                            'sep' => $logicalOperator,
                            'const_value' => $resValue['is_const'],
                        ]; // now combine fields + operators
                        break;
                    case "between":
                        $btwOperators = [];
                        if ($this->negationOn) {
                            $btwOperators[] = 'not';
                        }
                        $btwOperators[] = 'between';

                        $resField = $this->getLeft($index, $value);
                        $resVal = $this->getBetweenValue($index, $value, $currIndex);


                        $parts[] =  [
                            'type' => CriterionTypes::BETWEEN,
                            'operators' => $btwOperators,
                            'field' => $resField['value'],
                            'value1' => implode('', $resVal['value'][0]),
                            'value2' => implode('', $resVal['value'][1]),
                            'raw_field' => $resField['is_raw'],
                            'raw_values' => $resVal['is_raw'],
                            'sep' => $logicalOperator, // now combine fields + operators
                        ];
                        break;
                    case "like":
                        $like_operators = [];
                        if ($this->negationOn) {
                            $like_operators[] = 'not';
                        }
                        $like_operators[] = 'like';

                        $resField = $this->getLeft($index, $value);
                        $resVal = $this->getRight($index, $value, $currIndex, $context);

                        $parts[] = [
                            'type' => CriterionTypes::LIKE,
                            'operators' => $like_operators,
                            'field' => $resField['value'],
                            'value' => $resVal['value'],
                            'raw_field' => $resField['is_raw'],
                            'raw_value' => $resVal['is_raw'],
                            'sep' => $logicalOperator,
                            'const_value' => $resVal['is_const'],
                        ];
                        break;
                    case "in":
                        $inOperators = [];
                        if ($this->negationOn) { // is not in ?
                            $inOperators[] = 'not';
                        }
                        $inOperators[] = 'in';

                        $resField = $this->getLeft($index, $value);
                        $resVal = $this->getRight($index, $value, $currIndex, $context);

                        $parts[] = [
                            'type' => $resVal['value_type'] == 'field_only' 
                                ? CriterionTypes::IN_FIELD 
                                : CriterionTypes::IN_FIELD_VALUE,
                            'operators' => $inOperators,
                            'field' => $resField['value'],
                            'value' => $resVal['value'],
                            'raw_field' => $resField['is_raw'],
                            'raw_value' => $resVal['is_raw'],
                            'sep' => $logicalOperator,
                            'as_php_arr' => $resVal['value_type'] == 'in-list',
                            'const_value' => $resVal['is_const'],
                        ];
                        break;
                    case "not":
                        $this->negationOn = !$this->negationOn;
                        break;
                    default:
                        break;
                }
            } else if ($val['expr_type'] == 'bracket_expression') {
                $local = [];

                if ($val['sub_tree'] !== false) { // skip cases such ()
                    $negationOn = $this->negationOn;
                    $this->getCriteriaParts($val['sub_tree'], $local, $context,false); // recursion
                    if (!empty($local)) {
                        $parts[] = array('type' => CriterionTypes::GROUP, 'se' => 'start', 'has_negation' => $negationOn, 'log' => $logicalOperator);
                        $parts = array_merge($parts, $local);
                        $parts[] = array('type' => CriterionTypes::GROUP, 'se' => 'end', 'has_negation' => $negationOn, 'log' => $logicalOperator);
                    }
                }

            } else if ($val['expr_type'] == 'function') {
                if ($this->getValue($val['base_expr']) == 'against') {
                    $resField = $this->getLeft($index, $value);
                    $resVal = $this->getRight($index, $value, $currIndex, $context);

                    $parts[] = [
                        'type' => CriterionTypes::AGAINST,
                        'field' => $resField['value'],
                        'value' => $resVal['value'],
                        'sep' => $logicalOperator,
                    ];

                } else if (CriterionContext::WHERE == $context) {
                    $fn = $this->getValue($val['base_expr']);

                    if (in_array($fn, $this->options['settings']['fns'])) {

                        if ($val['sub_tree'] !== false && $this->isRaw($val['sub_tree'][0])) {
                            continue;
                        }

                        $params = ''; // params is field in this context
                        $this->getFnParams($val, $params);

                        $tempIndex = $currIndex;
                        $currIndex = $index = ($index + 1); // move to operator
                        $sep = $this->getValue($value[$currIndex]['base_expr']);
                        $resVal = $this->getRight($index, $value, $currIndex, $context);

                        if ($resVal['is_raw']) {
                            $currIndex = $tempIndex;
                            continue;
                        }
                        
                        $parts[] = [
                            'type' => CriterionTypes::FN,
                            'fn' => $fn,
                            'field' => $params,
                            'value' => $resVal,
                            'operator' => $sep,
                            'sep' => $logicalOperator,
                        ];
                    }
                }

            }
        }
    }

    private function getArrayParts($val)
    {
        $fields = [];
        $values = [];
        $operators = [];

        $next = 'field';
        $localOperators = [];

        foreach ($val as $v) {
            if ($v['expr_type'] == 'operator') {
                if ($this->getValue($v['base_expr']) == 'not' && (count($fields) - count($values)) == 0) { // not x > 1
                    return false;
                }

                if ($this->isComparisonOperator($v['base_expr'], array('like', 'not'))) { // in this case [like, not like] are are valid comparison operators
                    $localOperators[] = $v['base_expr'];
                    continue;
                }

                if ($this->getValue($v['base_expr']) != 'and') // prevent group on [or] operator, also in any operation too such as field1+field2 > number (this needs not to be escaped, therefore it will be as separate row)
                    return false;
            } else {
                if ($next == 'field') {
                    $field = $this->getAllValue($v);
                    if (in_array($field, $fields)) {// dont allow grouping in duplicate keys (because of php arrays)
                        return false;
                    }

                    if ($this->isRaw($v)) {
                        return false;
                    }

                    $fields[] = $field;
                    $next = 'value';
                } else if ($next == 'value') {
                    $value = $this->getAllValue($v);
                    if ($this->isRaw($v))
                        return false;
                    $values[] = $value;
                    $operators[] = implode(' ', $localOperators);
                    $next = 'field';
                    $localOperators = [];
                }
            }
        }

        if (count($fields) != count($values) || count($fields) <= 1)
            return false;

        return array('fields' => $fields, 'operators' => $operators, 'values' => $values);
    }

    public function getLeft($index, $value, $context = CriterionContext::WHERE)
    {
        $fieldValue = '';
        $leftIndex = $index;
        $leftOperator = '';
        $isRaw = false;
        while (!$this->isLogicalOperator($leftOperator)) {
            if ($leftIndex > 0) {
                $leftIndex--;
                $op_type = $this->getValue($value[$leftIndex]['expr_type']);
                if ($op_type == 'operator') {
                    if (!$this->isArithmeticOperator($this->getValue($value[$leftIndex]['base_expr']))) {
                        $leftOperator = $this->getValue($value[$leftIndex]['base_expr']);
                    } else {
                        $fieldValue = $value[$leftIndex]['base_expr'] . $fieldValue;
                        $isRaw = true; // if some operation is happening then the expression should not be escaped
                    }
                } else if ($context == CriterionContext::HAVING && $op_type == 'colref' && $value[$leftIndex]['base_expr'] == ',') {
                    break;
                } else {
                    if ($op_type == 'reserved') // where x like '%abc'; stop at where, todo needs a better solution
                        break;

                    if ($op_type != 'const' && $op_type != 'colref') {
                        $isRaw = true;
                    }

                    $fieldValue = $this->getAllValue($value[$leftIndex]) . $fieldValue;
                }
            } else {
                break;
            }
        }

        if ($this->handleOuterNegation && $this->negationOn) {
            $fieldValue = ' NOT ' . $fieldValue;
            $isRaw = true;
        }

        $this->negationOn = false;
        $this->handleOuterNegation = false;

        return ['value' => $fieldValue, 'is_raw' => $isRaw];
    }

    function getRight($index, $value, &$currIndex, $context = CriterionContext::WHERE)
    {
        $hasNegation = false;
        $valueStr = '';
        $valueType = '';

        $rightIndex = $index;
        $rightOperator = '';
        $isRaw = false;

        $isConst = null;

        while (!$this->isLogicalOperator($rightOperator)) { // x > 2 and (until you find first logical operator keep looping)
            $rightIndex++;
            if ($rightIndex < count($value)) {

                if ($this->getValue($value[$rightIndex]['expr_type']) == 'operator') {

                    if (!$this->isArithmeticOperator($value[$rightIndex]['base_expr'])) {
                        $rightOperator = $this->getValue($value[$rightIndex]['base_expr']);
                    } else {
                        $valueStr .= $value[$rightIndex]['base_expr'];
                        if ($context === CriterionContext::JOIN) {// because on x=x+5, x+5 is escaped entirely !
                            $isConst = false;
                        } else {
                            $isRaw = true; // if some operation is happening then the expression should not be escaped
                        }
                    }

                    if ($rightOperator == 'not') {
                        $hasNegation = true;
                    }
                } else if ($context == CriterionContext::HAVING
                    && $value[$rightIndex]['expr_type'] == 'colref'
                    && $value[$rightIndex]['base_expr'] == ','
                ) {
                    break;
                } else {
                    $valueType = $value[$rightIndex]['expr_type'];
                    if ($context === CriterionContext::JOIN) { // on x = y (both x,y must be column)
                        if ($value[$rightIndex]['expr_type'] != 'colref') {
                            $isRaw = true;
                        }

                        if (!isset($isConst)) {
                            if ($value[$rightIndex]['expr_type'] == 'const') {
                                $isConst = true;
                            } else {
                                $isConst = false;
                            }
                        }

                    } else {
                        if ($value[$rightIndex]['expr_type'] != 'const') {
                            $isRaw = true;
                        }
                    }

                    if ($valueType == 'subquery') {
                        $valueType = 'field_only';
                    }

                    $val = $this->getAllValue($value[$rightIndex]);
                    $valueStr .= $val;
                }
            } else
                break;
        }
        $currIndex = $rightIndex;
        return ['value' => $valueStr, 'has_negation' => $hasNegation,
            'is_raw' => $isRaw, 'value_type' => $valueType, 'is_const' => $isConst];
    }

    private function getBetweenValue($index, $value, &$currIndex)
    {
        $hasNegation = false;
        $final = $raws = $valuesArray = [];
        $rightIndex = $index;
        $rightOperator = '';

        $log_operator_count = 0;
        $isRaw = false;
        while ($log_operator_count != 2) { // between x and y and (until you find second logical operator keep looping)
            $rightIndex++;
            if ($rightIndex < count($value)) {
                if ($this->getValue($value[$rightIndex]['expr_type']) == 'operator') {
                    if (!$this->isArithmeticOperator($value[$rightIndex]['base_expr'])) {
                        $rightOperator = $this->getValue($value[$rightIndex]['base_expr']);
                    }

                    if ($rightOperator == 'not') {
                        $hasNegation = true;
                        $rightOperator = '';
                        continue;
                    }

                    if ($this->isLogicalOperator($rightOperator)) {
                        $log_operator_count++;
                        $rightOperator = '';
                        $final[] = $valuesArray;
                        $raws[] = $isRaw;
                        $isRaw = false;
                        $valuesArray = [];
                        continue;
                    }

                }

                if ($value[$rightIndex]['expr_type'] != 'const') {
                    $isRaw = true;
                }

                $valuesArray [] = $this->getAllValue($value[$rightIndex]);
            } else {
                break;
            }
        }

        if (!empty($valuesArray)) {
            $final[] = $valuesArray;
            $raws[] = $isRaw;
        }
        $currIndex = $rightIndex;

        return ['value' => $final, 'has_negation' => $hasNegation, 'is_raw' => $raws];
    }

    private function getAllValue($val)
    {
        $this->getExpressionParts([$val], $parts);

        return $this->mergeExpressionParts($parts);
    }
}