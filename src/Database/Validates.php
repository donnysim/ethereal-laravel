<?php

namespace Ethereal\Database;

use Illuminate\Support\Collection;
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
     * @param array $additionalRules
     *
     * @return $this
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validOrFail(array $additionalRules = [])
    {
        if ($this->invalid($additionalRules)) {
            $this->throwValidationException();
        }

        return $this;
    }

    /**
     * Check if the model is invalid.
     *
     * @param array $additionalRules
     *
     * @return bool
     */
    public function invalid(array $additionalRules = [])
    {
        return !$this->valid($additionalRules);
    }

    /**
     * Check if model is valid.
     *
     * @param array $additionalRules
     *
     * @return bool
     */
    public function valid(array $additionalRules = [])
    {
        $validator = $this->validator([], $additionalRules);

        return $validator->passes();
    }

    /**
     * Make a Validator instance for a given ruleset.
     *
     * @param array $rules
     * @param array $additionalRules
     * @param bool $full
     *
     * @return \Illuminate\Validation\Validator
     */
    protected function validator(array $rules = [], array $additionalRules = [], $full = false)
    {
        $attributes = $this->toPlainArray($full);
        $messages = $this->customValidationMessages();

        $this->validator = app('validator')->make(
            $attributes,
            array_merge_recursive($rules ?: $this->validationRules(), $additionalRules),
            $messages
        );

        return $this->validator;
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
     * @param array $additionalRules
     *
     * @return $this
     * @throws \Illuminate\Validation\ValidationException
     */
    public function fullyValidOrFail(array $additionalRules = [])
    {
        if (!$this->fullyValid($additionalRules)) {
            $this->throwValidationException();
        }

        return $this;
    }

    /**
     * Check if model and all of it's relations are valid. This does not include
     *
     * @param array $additionalRules
     *
     * @return bool
     */
    public function fullyValid(array $additionalRules = [])
    {
        if (count($this->relations) === 0) {
            return $this->valid($additionalRules);
        }

        $rules = $this->validationRules();

        foreach ($this->relations as $relation => $value) {
            if ($value instanceof Ethereal) {
                foreach ($value->validationRules() as $field => $rule) {
                    $rules["$relation.$field"] = $rule;
                }
            } elseif ($value instanceof Collection && !$value->isEmpty() && $value[0] instanceof Ethereal) {
                foreach ($value[0]->validationRules() as $field => $rule) {
                    $rules["$relation.*.$field"] = $rule;
                }
            } elseif (is_array($value) && count($value) > 0 && $value[0] instanceof Ethereal) {
                foreach ($value[0]->validationRules() as $field => $rule) {
                    $rules["$relation.*.$field"] = $rule;
                }
            }
        }

        $validator = $this->validator($rules, $additionalRules, true);

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
}
