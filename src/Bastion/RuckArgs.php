<?php

namespace Ethereal\Bastion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RuckArgs
{
    /**
     * Ability name.
     *
     * @var string
     */
    protected $ability;

    /**
     * Additional arguments.
     *
     * @var array
     */
    protected $payload = [];

    /**
     * Model class.
     *
     * @var string
     */
    protected $class;

    /**
     * Model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Model morph class.
     *
     * @var string
     */
    protected $morph;

    /**
     * RuckArgs constructor.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|array $model
     * @param array $arguments
     */
    public function __construct($ability, $model, $arguments = [])
    {
        $this->setAbility($ability);

        if (is_string($model)) {
            $this->class = $model;
            $this->morph = Helper::getMorphClassName($model);
        } elseif ($model instanceof Model) {
            $this->model = $model;
            $this->class = get_class($model);
            $this->morph = $model->getMorphClass();
        } else {
            $this->model = null;
        }

        $this->payload = $arguments;
        if (is_array($model)) {
            $this->payload = $model;
        }
    }

    /**
     * Get ability argument.
     *
     * @return string
     */
    public function getAbility()
    {
        return $this->ability;
    }

    /**
     * Get model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get model morph name.
     *
     * @return string
     */
    public function getMorph()
    {
        return $this->morph;
    }

    /**
     * Set ability argument.
     *
     * @param string $ability
     */
    public function setAbility($ability)
    {
        $this->ability = $ability;
    }

    /**
     * Get policy method name.
     *
     * @return string
     */
    public function getMethod()
    {
        $ability = $this->ability;

        if (strpos($ability, '-') !== false) {
            $ability = Str::camel($ability);
        }

        return $ability;
    }

    /**
     * Get arguments.
     *
     * @return array
     */
    public function getPayload()
    {
        return is_array($this->payload) ? $this->payload : [];
    }

    /**
     * Set additional arguments.
     *
     * @param array $payload
     */
    public function setPayload(array $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Get function call arguments.
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     *
     * @return array
     */
    public function getArguments($user)
    {
        $args = [$user];

        if ($this->model) {
            $args[] = $this->model;
        }

        $args = array_merge($args, $this->getPayload());

        return $args;
    }

    /**
     * Get model class.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
