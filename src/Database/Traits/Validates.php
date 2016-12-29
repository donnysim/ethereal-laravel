<?php

namespace Ethereal\Database\Traits;

use Ethereal\Database\Ethereal;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Traversable;

trait Validates
{
    /**
     * Validator instance used for validation.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * Get model validation rules. Validation of the model is based on
     * it's attributes, so filling the model and then validating it is the
     * intended way.
     *
     * @return array
     */
    public function validationRules()
    {
        return [];
    }

    /**
     * The array of custom validation error messages.
     *
     * @var array
     * @return array
     */
    public function validationMessages()
    {
        return [];
    }

    /**
     * The array of custom validation attribute names.
     *
     * @var array
     * @return array
     */
    public function validationAttributes()
    {
        return [];
    }

    /**
     * Determine if the model data is valid.
     *
     * @param array $rules
     *
     * @return bool
     */
    public function valid(array $rules = [])
    {
        $validator = $this->validator(false, $rules);

        return $validator->passes();
    }

    /**
     * Determine if the model data is invalid.
     *
     * @param array $rules
     *
     * @return bool
     */
    public function invalid(array $rules = [])
    {
        return !$this->valid($rules);
    }

    /**
     * Get model validator.
     *
     * @param bool $full
     * @param array $rules
     *
     * @return \Illuminate\Validation\Validator
     */
    public function validator($full = false, array $rules = [])
    {
        $data = $this->collectValidationData($full);

        if (empty($rules)) {
            $rules = $this->collectValidationRules($full);
        }

        $validator = app('validator')->make($data, $rules, $this->validationMessages(), $this->validationAttributes());

        return $validator;
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
     * Collect validation rules for this model and relations if
     * targeting full validation.
     *
     * @param bool $full
     * @param string $base
     *
     * @return array
     */
    public function collectValidationRules($full = false, $base = '')
    {
        if ($base !== '' && !Str::endsWith($base, '.')) {
            $base .= '.';
        }

        $rules = [];

        foreach ($this->validationRules() as $field => $rule) {
            $rules["{$base}{$field}"] = $rule;
        }

        if (!$full) {
            return $rules;
        }

        foreach ($this->getRelations() as $relation => $value) {
            // Skip empty values
            if (!$value) {
                continue;
            }

            $root = "{$base}{$relation}.";
            $modelRules = [];
            /** @var \Ethereal\Database\Ethereal $model */
            $model = $value;

            // Valid relations are models and Traversable collections
            if ($value instanceof Ethereal) {
                $modelRules = $value->validationRules();
            } elseif ($value instanceof Traversable) {
                if (method_exists($value, 'first')) {
                    $model = $value->first();
                } else {
                    $model = head($value);
                }

                if (!$model || !$model instanceof Ethereal) {
                    continue;
                }

                $root = "{$base}{$relation}.*.";
                $modelRules = $model->validationRules();
            }

            if (!$modelRules) {
                continue;
            }

            foreach ($modelRules as $field => $rule) {
                $rules["{$root}{$field}"] = $rule;
            }

            $rules = array_merge($rules, $model->collectValidationRules($full, $root));
        }

        return $rules;
    }

    /**
     * Collect data for validation.
     *
     * @param bool $full
     *
     * @return array
     */
    public function collectValidationData($full = false)
    {
        $data = $this->getAttributes();

        if (!$full) {
            return $data;
        }

        foreach ($this->getRelations() as $relation => $value) {
            // Skip empty values
            if (!$value) {
                continue;
            }

            // Valid relations are models and Traversable collections
            if ($value instanceof Ethereal) {
                $data[$relation] = $value->collectValidationData($full);
            } elseif ($value instanceof Traversable) {
                if (method_exists($value, 'first')) {
                    $model = $value->first();
                } else {
                    $model = head($value);
                }

                if (!$model || !$model instanceof Ethereal) {
                    continue;
                }

                $data[$relation][] = $model->collectValidationData($full);
            }
        }

        return $data;
    }
}
