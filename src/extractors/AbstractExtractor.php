<?php

namespace Reptily\SQLToLaravelBuilder\extractors;

/**
 * This class provides common functionality for all Extractor classes.
 * Extractor classes are classes which help to pull out SQL query parts in a way which are more understandable and processable by Builder.
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
abstract class AbstractExtractor
{

    protected $options;

    function __construct($options)
    {
        $this->options = $options;
    }

    function getValue($val)
    {
        return strtolower(trim($val));
    }

    function isLogicalOperator($operator)
    {
        return in_array($this->getValue($operator), array('and', 'or'));
    }

    function hasAlias($val)
    {
        return isset($val['alias']) && $val['alias'] !== false;
    }

    function getFnParams($val, &$params)
    {
        if ($val['sub_tree'] !== false) {
            foreach ($val['sub_tree'] as $k => $item) {
                $params .= $item['base_expr'];
                if ($k < count($val['sub_tree']) - 1) {
                    $params .= ", ";
                }
                if ($item['expr_type'] !== 'bracket_expression') {
                    $this->getFnParams($item, $params);
                }
            }
        }

        return $params;
    }

    function isSingleTable($parsed) // duplicate?
    {
        return (isset($parsed['FROM']) && count($parsed['FROM']) == 1 && $parsed['FROM'][0]['expr_type'] == 'table');
    }

    function validCountTable($parsed)
    {
        $k = $parsed;
        unset($k['SELECT']);
        unset($k['FROM']);
        // if has any other clause (e.g WHERE/HAVING/LIMIT)
        // except those that were removed
        // then it is not valid
        return count($k) === 0;
    }
    function validJoin($join_type)
    {
        return $this->getValue($join_type) != 'natural';
    }

    function isArithmeticOperator($op)
    {
        return in_array($this->getValue($op), ['+', '-', '*', '/', '%']);
    }

    function isComparisonOperator($operator, $append = [])
    {
        $simple_operators = ['>', '<', '=', '!=', '>=', '<=', '!<', '!>', '<>'];
        if (!empty($append)) {
            $simple_operators = array_merge($simple_operators, $append);
        }

        return in_array($this->getValue($operator), $simple_operators);
    }

    function getExpressionParts($value, &$parts, &$raws = [], $recursive = false)
    {
        $valLen = count($value);

        foreach ($value as $k => $val) {

            if (in_array($val['expr_type'], ['function', 'aggregate_function'])) { // base expressions are not enough in such cases
                $localParts = [$val['base_expr']];
                $localParts[] = '('; // e.g function wrappers
                if ($val['sub_tree'] !== false) { // functions + agg fn and others
                    $this->getExpressionParts($val['sub_tree'], $localParts, $raws, true);
                }
                $localParts[] = ')';
                if ($this->hasAlias($val)) {
                    $localParts[] = ' ' . $val['alias']['base_expr'];
                }
                $parts[] = implode('', $localParts);
                $raws[] = true;

                continue;
            }

            $subLocal = [$val['base_expr']];

            if (!in_array($val['expr_type'], ['expression', 'subquery'])) // these already have aliases appended
            {
                if ($this->hasAlias($val)) {
                    $subLocal[] = ' ' . $val['alias']['base_expr'];
                }
            }

            if ($recursive) {
                if (isset($val['delim']) && $val['delim'] !== false) {
                    $subLocal[] = $val['delim'];
                } else if ($k != $valLen - 1) {
                    $subLocal[] = ", ";
                }
                $parts = array_merge($parts, $subLocal);
            } else {
                $parts[] = implode('', $subLocal);
                $raws[] = $val['expr_type'] != 'colref';
            }
        }
    }

    public function mergeExpressionParts($parts)
    {
        return (implode('', $parts));
    }

    protected function getWithAlias($val, &$isRaw)
    {
        if ($val['expr_type'] === 'table') {
            $return = $val['table']; // no alias here, if any, it will be added at the end
        } else {
            if ($val['expr_type'] == 'subquery') {
                $return = '(' . $val['base_expr'] . ')';
            } else {
                $return = $val['base_expr'];
            }
        }
        if ($this->hasAlias($val)) {
            $return .= ' ';
            if ($val['alias']['as'] === false) { // because Laravel escapes 'table t' expressions entirely!
                $isRaw = true;
            }

            $return .= $val['alias']['base_expr'];
        }

        return $return;
    }

    public function isRaw($val)
    {
        return !($val['expr_type'] == 'colref' || $val['expr_type'] == 'const');
    }
}
