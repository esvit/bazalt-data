<?php

namespace Bazalt\Data;

class ValidationSet
{
    protected $messages = [];

    protected $validators = [];

    protected $depends = [];

    /**
     * @var Validator
     */
    protected $validator = null;

    public function __construct($validator)
    {
        $this->validator = $validator;
    }

    public function end()
    {
        return $this->validator;
    }
    
    public function validate($value, &$messages = [])
    {
        $valid = true;
        foreach ($this->validators as $name => $validator) {
            if (!$res = $validator($value)) {
                $valid = false;
                $messages[$name] = isset($this->messages[$name]) ?
                    (is_callable($this->messages[$name]) ? $this->messages[$name]() : $this->messages[$name]) :
                    null;
            }
        }
        return $valid;
    }

    public function validator($name, $function, $message = null, $depends = [])
    {
        $this->validators[$name] = $function;
        $this->messages[$name] = $message;
        $this->depends += $depends;

        return $this;
    }

    public function required()
    {
        return $this->validator('required', function($value) {
            $value = trim($value);
            return !empty($value);
        }, 'Field cannot be empty');
    }

    public function email()
    {
        return $this->validator('email', function($value) {
            $value = trim($value);
            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        }, 'Invalid email');
    }

    public function bool()
    {
        return $this->validator('bool', function(&$value) {
            $valid = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($valid !== null) {
                $value = $valid;
            }
            return $valid !== null;
        }, 'Invalid boolean value');
    }

    public function equal($dataValue)
    {
        return $this->validator('equal', function($value) use ($dataValue) {
            return $value === $dataValue;
        }, 'Fields not equals');
    }

    public function float()
    {
        return $this->validator('float', function(&$value) {
            $valid = filter_var($value, FILTER_VALIDATE_FLOAT);
            if ($valid) {
                $value = (double)$value;
            }
            return $valid;
        }, 'Invalid float value');
    }

    public function int($min = null, $max = null)
    {
        return $this->validator('float', function(&$value) use ($min, $max) {
            $options = [];
            if ($min !== null) {
                $options['min_range'] = $min;
            }
            if ($max !== null) {
                $options['max_range'] = $max;
            }
            $valid = filter_var($value, FILTER_VALIDATE_INT, ['options' => $options]);
            if ($valid !== false) {
                $value = $valid;
                return true;
            }
            return false;
        }, 'Invalid integer value');
    }

    public function nested(Validator $validator)
    {
        $messages = [];
        return $this->validator('nested', function(&$value) use ($validator, &$messages) {
            $valid = $validator->data($value)->validate();
            $messages = $validator->errors();
            return $valid;
        }, function() use (&$messages){
            return $messages;
        });
    }

    public function isArray()
    {
        return $this->validator('isArray', function($value) {
            return is_array($value);
        }, 'Invalid array');
    }

    /**
     * @param Validator $validator
     * @return ValidationSet
     */
    public function nestedArray(Validator $validator)
    {
        return $this->isArray()->validator('nestedArray', function(&$value) use ($validator) {
            $valid = true;
            foreach ($value as $item) {
                $itemValid = $validator->data($item)->validate();
                if (!$itemValid) {
                    $valid = false;
                }
            }
            return $valid;
        }, 'Invalid nested array validation');
    }

    /**
     * @param $keys
     * @return ValidationSet
     */
    public function keys($keys)
    {
        $messages = null;
        return $this->validator('keys', function(&$value) use ($keys, &$messages) {
            if (!is_array($value)) {
                $value = (array)$value;
            }
            $itemKeys = array_keys($value);
            $diff = array_diff($itemKeys, $keys);
            if (count($diff) > 0) {
                $messages = 'Invalid keys "' . implode(",", $diff) . '"';
                return false;
            }
            return true;
        }, function() use (&$messages) {
            return $messages;
        });
    }

    public function length($min = null, $max = null)
    {
        $messages = [];
        return $this->validator('length', function(&$value) use ($min, $max, &$messages) {
            $len = strLen($value);
            if ($min !== null && $len < $min) {
                $messages['minlength'] = 'String must be more then ' . $min . ' symbols';
            }
            if ($max !== null && $len > $max) {
                $messages['maxlength'] = 'String must be less then ' . $max . ' symbols';
            }
            return count($messages) == 0;
        }, function() use (&$messages) {
            return $messages;
        });
    }
}