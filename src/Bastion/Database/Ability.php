<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Ability extends Ethereal
{
    protected $columns = ['id', 'name', 'entity_id', 'entity_type', 'created_at', 'updated_at'];

    /**
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param string|int|null $id
     *
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
    public static function findAbility($ability, $model = null, $id = null)
    {
        list($modelType, $modelId) = static::getModelTypeAndId($model, $id);

        return static::query()
            ->where('name', $ability)
            ->where('entity_id', $modelId)
            ->where('entity_type', $modelType)
            ->first();
    }

    /**
     * Create a new ability.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param string|int|null $id
     * @param array $attributes
     *
     * @return mixed
     */
    public static function createAbilityRecord($ability, $model = null, $id = null, array $attributes = [])
    {
        list($modelType, $modelId) = static::getModelTypeAndId($model, $id);

        return static::create(
            array_merge([
                'name' => $ability,
                'entity_id' => $modelId,
                'entity_type' => $modelType,
            ], $attributes)
        );
    }

    /**
     * Collection abilities.
     *
     * @param array $abilities
     * @param \Illuminate\Database\Eloquent\Model|null $model
     *
     * @return \Illuminate\Support\Collection
     */
    public static function collectAbilities($abilities, $model = null)
    {
        $abilitiesList = new Collection();

        foreach ($abilities as $key => $ability) {
            if ($ability instanceof Model) {
                if (!$ability->exists) {
                    $ability->save();
                }

                $abilitiesList->push($ability);
            } elseif (is_numeric($ability)) {
                $abilitiesList->push(static::findOrFail($ability));
            } elseif (is_string($key) && is_array($ability)) {
                $abilitiesList->push(
                    static::findAbility($key, $model) ?: static::createAbilityRecord($key, $model, null, $ability)
                );
            } elseif (is_string($ability)) {
                $abilitiesList->push(
                    static::findAbility($ability, $model) ?: static::createAbilityRecord($ability, $model)
                );
            }
        }

        return $abilitiesList->keyBy((new static)->getKeyName());
    }

    /**
     * Get model type and id.
     *
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @param string|int|null $id
     *
     * @return array
     */
    protected static function getModelTypeAndId($model, $id)
    {
        $modelType = null;
        $modelId = null;

        if (is_string($model)) {
            $modelType = Helper::getMorphOfClass($model);
            $modelId = $id;
            return [$modelType, $modelId];
        } elseif ($model instanceof Model) {
            $modelType = $model->getMorphClass();
            $modelId = $model->getKey();
            return [$modelType, $modelId];
        }
        return [$modelType, $modelId];
    }
}
