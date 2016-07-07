<?php

namespace Ethereal\Database;

use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Ethereal extends Model
{
    use HandlesRelations, Validates;

    const OPTION_SKIP = 1;
    const OPTION_SAVE = 2;
    const OPTION_DELETE = 4;
    const OPTION_PUSH = 8;
    const OPTION_ATTACH = 16;
    const OPTION_SYNC = 32;
    const OPTION_DETACH = 64;

    /**
     * Fillable relations.
     *
     * @var string[]
     */
    protected $fillableRelations = [];

    /**
     * Save a new model and return the instance.
     *
     * @param array $attributes
     * @return static
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public static function smartCreate(array $attributes = [])
    {
        $model = new static;
        $model->smartFill($attributes);
        $model->save();

        return $model;
    }

    /**
     * Save model and relations. When saving relations, they are linked to this model.
     *
     * @param array $options
     * @return bool|void
     */
    public function smartPush($options = [])
    {
        // To minimize the amount of database requests we make two phase
        // relations saving. First pass is to save relations that do not
        // require parent model to be save and can set relation value
        // directly. Second pass is so that relations that do require
        // parent model to exist, are linked and saved correctly.

        $relationOptions = isset($options['relations'])
            ? new Collection($options['relations'])
            : new Collection;

        // Make the first save pass
        if (! $this->saveRelations($relationOptions, true)) {
            return false;
        }

        // Make the second save pass
        if (! $this->save($options) || ! $this->saveRelations($relationOptions)) {
            return false;
        }

        return true;
    }

    /**
     * Fill attributes and relations.
     *
     * @param array $attributes
     * @return $this
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function smartFill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            $key = $this->removeTableFromKey($key);

            // The developers may choose to place some attributes in the "fillable"
            // array, which means only those attributes may be set through mass
            // assignment to the model, and all others will just be ignored.
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            } elseif ($totallyGuarded) {
                throw new MassAssignmentException($key);
            } elseif (in_array($key, $this->fillableRelations, true)) {
                $this->setRelation($key, $value);
            }
        }

        return $this;
    }

    /**
     * Set attribute ignoring setters.
     *
     * @param string $attribute
     * @param mixed $value
     */
    public function setRawAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;
    }
}