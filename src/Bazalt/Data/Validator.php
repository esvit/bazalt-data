<?php

namespace Bazalt\Data;

class Validator implements \ArrayAccess
{
    protected $data = array();

    /**
     * @var ValidationSetarray()
     */
    protected $fields = array();

    protected $errors = array();

    /**
     * @param array $data
     * @return Validator
     */
    public static function create($data = array())
    {
        return new Validator($data);
    }

    protected  function __construct($data = array())
    {
        $this->data = $data;
    }
    
    public function errors()
    {
        return $this->errors;
    }

    public function field($name)
    {
        return $this->fields[$name] = new ValidationSet($this);
    }

    public function data($data = null)
    {
        if ($data !== null) {
            $this->data = $data;
            return $this;
        }
        return $this->data;
    }

    public function validate()
    {
        $valid = true;
        foreach ($this->fields as $name => $field) {
            $messages = array();
            $valid &= $field->validate($this[$name], $messages);
            if (count($messages) > 0) {
                $this->errors[$name] = $messages;
            }
        }
        return $valid > 0;
    }

    public function offsetExists($offset)
    {
        return is_object($this->data) ? property_exists($this->data, $offset) : isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return ($this->offsetExists($offset)) ? (is_object($this->data) ? $this->data->{$offset} : $this->data[$offset]) : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_object($this->data)) {
            $this->data->{$offset} = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        if (is_object($this->data)) {
            $this->data->{$offset} = null;
        } else {
            unset($this->data[$offset]);
        }
    }
}