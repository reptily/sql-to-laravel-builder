<?php

namespace Reptily\SQLToLaravelBuilder\utils;

interface CriterionContext
{
    public const HAVING = 'having';
    public const WHERE = 'where';
    public const JOIN = 'join';
}