<?php

namespace Reptily\SQLToLaravelBuilder\builders;

/**
 *  Builder.php
 *
 *  Interface declaration for all builder classes.
 *  A builder can create a part (method) of Query Builder. The necessary information
 *  are provided by the function parameter as array. This array is compiled from
 *  the corresponding extractors output.
 *
 * @author Rexhep Shijaku <rexhepshijaku@gmail.com>
 */
interface Builder
{
    /**
     * Builds Query Builder methods.
     *
     * @param array $parts
     * @param array $skipBag
     * @return A string, which contains a part of Query Builder.
     */
    public function build(array $parts, array &$skipBag = []);
}


