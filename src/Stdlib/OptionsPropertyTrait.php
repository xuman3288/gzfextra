<?php
namespace Gzfextra\Stdlib;


trait OptionsPropertyTrait
{
    protected $options = [];

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($key)
    {
        return isset($this->options[$key]) ? $this->options[$key] : null;
    }

    /**
     * Configure state
     *
     * @param  array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }

        return $this;
    }

    public function setOption($name, $value)
    {
        $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
        if (method_exists($this, $method)) {
            $this->{$method}($value);
        } else {
            $this->options[$name] = $value;
        }

        return $this;
    }
} 