<?php

namespace RexShijaku\SQLToLaravelBuilder\builders;

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

    function build(array $parts, array &$skipBag = array())
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
            foreach ($parts as $k => $f_v) {
                if (!empty($inner))
                    $inner .= ', ';

                if ($f_v['type'] == 'fn')
                    $inner .= ($f_v['dir']) . ' (' . ($f_v['field']) . ')';
                else
                    $inner .= ($f_v['field']) . ' ' . ($f_v['dir']);
            }

            $q .= '->orderByRaw(' . $this->quote($inner) . ')';
        } else {
            foreach ($parts as $k => $f_v)
                $q .= "->orderBy(" . $this->quote($f_v['field']) . ', ' . $this->quote($f_v['dir']) . ')';
        }

        return $q;
    }

}
