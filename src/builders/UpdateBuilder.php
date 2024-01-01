<?php

namespace Reptily\SQLToLaravelBuilder\builders;

/**
 * This class constructs and produces following Query Builder methods :
 *
 *  update
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 *
 */
class UpdateBuilder extends AbstractBuilder implements Builder
{
    public function build(array $parts, array &$skipBag = [])
    {
        $skipBag[] = 'SET';
        return '->update(' . $this->getSetAsArray($parts['records']) . ')';
    }

    private function getSetAsArray($records)
    {
        if (empty($records))
            return '[]';

        $innerArray = '';
        foreach ($records as $record) {
            if (!empty($innerArray)) {
                $innerArray .= ', ';
            }

            $innerArray .= ($this->quote($record['field']) . ' => ')
                . $this->buildRawable($record['value'], $record['raw_val']);
        }

        return '[' . $innerArray . ']';
    }

}
