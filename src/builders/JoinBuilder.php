<?php

namespace Reptily\SQLToLaravelBuilder\builders;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  join
 *  leftJoin
 *  rightJoin
 *  crossJoin
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class JoinBuilder extends AbstractBuilder implements Builder
{

    public function build(array $parts, array &$skipBag = [])
    {
        $qb = '';

        foreach ($parts as $join) {

            if ($this->getValue($join['type']) !== 'join') { // left,right,cross etc
                $fn = $this->fnMerger(array(strtolower($join['type']), 'join'));
            } else {
                $fn = $this->fnMerger(array('join'));
            }

            $qb .= "->" . $fn . "(" . $this->buildRawable($join['table'], $join['table_is_raw']);
            if (isset($join['on_clause']) && count($join['on_clause']) > 0) // in cross join no on_clause!
            {
                // everything except columns are raw !
                if (count($join['on_clause']) == 1
                    && $join['on_clause'][0]['type'] !== 'between'
                    && $join['on_clause'][0]['raw_field'] === false
                    && $join['on_clause'][0]['raw_value'] === false) {

                    $onClause = $join['on_clause'][0];
                    $qb .= ", " . $this->quote($onClause['field'])
                        . ", " . $this->quote(implode(' ', $onClause['operators']))
                        . ", " . $this->quote($onClause['value']);
                } else {

                    $qb .= ', function($join) {';
                    $qb .= '$join';

                    foreach ($join['on_clause'] as $onClause) {

                        if ($onClause['type'] == 'between' || $onClause['raw_field'] || $onClause['raw_value']) {
                            if (isset($onClause['const_value']))
                                $onClause['raw_value'] = !$onClause['const_value'];
                            $builder = new CriterionBuilder($this->options);
                            $q = $builder->build(array($onClause));
                            $qb .= $q;
                        } else {
                            // no raw found and not between
                            $operators = implode(' ', $onClause['operators']);
                            $fnParts = $onClause['sep'] == 'and' ? ['on'] : ['or', 'on'];

                            $qb .= '->';
                            $qb .= $this->fnMerger($fnParts);
                            $qb .= '(';

                            $qb .= $this->quote($onClause['field'], $onClause['raw_field'])
                                . ", " . $this->quote($operators)
                                . ", " . $this->quote($onClause['value'],
                                    !$onClause['const_value'] && $onClause['raw_value']);

                            $qb .= ')';
                        }
                    }
                    $qb .= '; }';
                }
            }
            $qb .= ")";
        }

        return $qb;
    }
}
