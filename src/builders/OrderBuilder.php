<?php

namespace Reptily\SQLToLaravelBuilder\builders;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  orderBy
 *  orderByRaw
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class OrderBuilder extends AbstractBuilder implements Builder
{
    function build(array $parts, array &$skipBag = [])
    {
        $q = '';
        $isRaw = false;
        foreach ($parts as $part)
            if ($part['raw']) {
                $isRaw = true;
                break;
            }

        if ($isRaw) {
            $inner = '';
            foreach ($parts as $val) {
                if (!empty($inner)) {
                    $inner .= ', ';
                }

                if ($val['type'] == 'fn') {
                    $inner .= ($val['dir']) . ' (' . ($val['field']) . ')';
                } else {
                    $inner .= ($val['field']) . ' ' . ($val['dir']);
                }
            }
            $q .= '->orderByRaw(' . $this->quote($inner) . ')';
        } else {
            foreach ($parts as $val) {
                if (trim(mb_strtolower($this->quote($val['dir']))) == "'asc'") {
                    $q .= "->orderBy(" . $this->quote($val['field']) . ')';
                } else {
                    $q .= "->orderByDesc(" . $this->quote($val['field']) . ')';
                }
            }
        }

        return $q;
    }

}
