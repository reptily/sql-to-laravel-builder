<?php

namespace Reptily\SQLToLaravelBuilder\utils;

interface SelectQueryTypes
{
    public const AGGREGATE = 'aggregate';
    public const COUNT_A_TABLE = 'count_a_table';
    public const OTHER = 'other';
}