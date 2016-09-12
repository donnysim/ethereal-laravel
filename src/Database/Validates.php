<?php

namespace Ethereal\Database;

use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;

/**
 * @mixin Ethereal
 */
trait Validates
{
    /**
     * Validator used for validating this model.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * Check if model is valid, otherwise throw an exception.
     *
     * @return bool
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validOrFail()
    {
        if ($this->invalid()) {
            $this->throwValidationException();
        }

        return true;
    }

    /**
     * Check if the model is invalid.
     *
     * @return bool
     */
    public function invalid()
    {
        return ! $this->valid();
    }

    /**
     * Check if model is valid.
     *
     * @return array
     */
    public function valid()
    {
        $validator = $this->validator();

        return $validator->passes();
    }

    /**
     * Make a Validator instance for a given ruleset.
     *
     * @param array $rules
     * @param bool $full
     * @return \Illuminate\Validation\Validator
     */
    protected function validator(array $rules = [], $full = false)
    {
        $attributes = $full ? $this->toArray() : $this->attributesToArray();
        $messages = $this->customValidationMessages();

        return $this->validator = app('validator')->make($attributes, $rules ?: $this->validationRules(), $messages);
    }

    /**
     * Get custom validation messages.
     *
     * @return array
     */
    public function customValidationMessages()
    {
        return [];
    }

    /**
     * Get model validation rules.
     *
     * @return array
     */
    public function validationRules()
    {
        return [];
    }

    /**
     * Throw a validation exception.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwValidationException()
    {
        throw new ValidationException($this->validator);
    }

    /**
     * Check if model is fully valid otherwise throw an exception.
     *
     * @return bool
     * @throws \Illuminate\Validation\ValidationException
     */
    public function fullyValidOrFail()
    {
        if (! $this->fullyValid()) {
            $this->throwValidationException();
        }

        return true;
    }

    /**
     * Check if model and all of it's relations are valid. This does not include
     *
     * @return array
     */
    public function fullyValid()
    {
        if (count($this->relations) === 0) {
            return $this->valid();
        }

        $rules = $this->validationRules();

        foreach ($this->relations as $relation => $value) {
            if ($value instanceof Ethereal) {
                foreach ($value->validationRules() as $field => $rule) {
                    $rules["$relation.$field"] = $rule;
                }
            } elseif ($value instanceof \Illuminate\Support\Collection && ! $value->isEmpty() && $value[0] instanceof Ethereal) {
                foreach ($value[0]->validationRules() as $field => $rule) {
                    $rules["$relation.*.$field"] = $rule;
                }
            } elseif (is_array($value) && count($value) > 0 && $value[0] instanceof Ethereal) {
                foreach ($value[0]->validationRules() as $field => $rule) {
                    $rules["$relation.*.$field"] = $rule;
                }
            }
        }

        $validator = $this->validator($rules, true);

        return $validator->passes();
    }

    /**
     * Get validation errors.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function validationErrors()
    {
        if ($this->validator) {
            return $this->validator->messages();
        }

        return new MessageBag();
    }

    /**
     * Check if model is valid and save.
     *
     * @param array $options
     * @return bool
     */
    public function saveIfValid(array $options = [])
    {
        if ($this->invalid()) {
            return false;
        }

        return $this->save($options);
    }

    /**
     * Check if model is valid and save or throw exception if validation
     * fails or save fails.
     *
     * @param array $options
     * @return bool
     * @throws \Throwable
     * @throws \Illuminate\Validation\ValidationException
     */
    public function saveIfValidOrFail(array $options = [])
    {
        if ($this->invalid()) {
            $this->throwValidationException();
        }

        return $this->saveOrFail($options);
    }
}