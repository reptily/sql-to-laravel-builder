<?php

namespace Reptily\SQLToLaravelBuilder;

class Options
{
    private $options;
    private $aggregateFn =['sum', 'min', 'max', 'avg', 'sum', 'count'];
    private $defaults = [
        'facade' => 'DB::',
        'group' => true
    ];
    private $supportingFn = ['date', 'month', 'year' ,'day', 'time'];

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function set(): void
    {
        if (!is_array($this->options)) {
            $this->options = [];
        }

        foreach ($this->defaults as $k => $v)
            if (!key_exists($k, $this->options))
                $this->options[$k] = $v;
            else {
                if (gettype($this->options[$k]) != gettype($this->defaults[$k]))
                    throw new \Exception('Invalid type in options. [' . $k . ' param type must be ' . gettype($this->defaults[$k]) . ']');
            }

        unset($this->options['settings']); // unset reserved
        $this->options['settings']['agg'] = $this->aggregateFn;
        $this->options['settings']['fns'] = $this->supportingFn;
    }

    public function get()
    {
        return $this->options;
    }
}