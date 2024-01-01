<?php

namespace Reptily\SQLToLaravelBuilder\utils;

interface CriterionTypes
{
    public const AGAINST = 'against';
    public const BETWEEN = 'between';
    public const COMPARISON = 'comparison';
    public const GROUP = 'group';
    public const IN_FIELD = 'in_field';
    public const IN_FIELD_VALUE = 'in_field_value';
    public const IS = 'is';
    public const LIKE = 'like';
    public const FN = 'fn';
}