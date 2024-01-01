<?php

namespace Reptily\SQLToLaravelBuilder\Test;

use Reptily\SQLToLaravelBuilder\SQLToLaravelBuilder;

class CountTest extends AbstractCases
{
    private $type = 'count';

    public function testAvg()
    {
        $sql = "SELECT COUNT(*) FROM members";
        $converter = new SQLToLaravelBuilder($this->options);
        $actual = $converter->convert($sql);
        $expected = $this->getExpectedValue($this->type, "count");
        $this->assertEquals($expected, $actual);
    }


    public function testCountATableWithOtherClauses()
    {
        # @yhinger`s case reported on issue #4
        $sql = "SELECT COUNT(*) FROM members WHERE is_active = 0;";
        $converter = new SQLToLaravelBuilder($this->options);
        $actual = $converter->convert($sql);
        $expected = $this->getExpectedValue($this->type, "count_a_table");
        $this->assertEquals($expected, $actual);
    }

    // todo add more, with alias, column and column alias
}