<?php

namespace Ethereal\Bastion\Database;

use Ethereal\Bastion\Database\Traits\IsAbility;
use Ethereal\Bastion\Helper;
use Ethereal\Database\Ethereal;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Ability extends Ethereal
{
    use IsAbility;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'title'];

    /**
     * Create a new Permission model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->table = config('bastion.tables.abilities', 'abilities');

        parent::__construct($attributes);
    }

    /**
     * @param $abilities
     * @param string|Model|null $model
     * @param bool $create
     */
    public static function collectAbilities($abilities, $model = null, $create = true)
    {
        $abilitiesList = collect([]);

        foreach ($abilities as $ability) {
            if ($ability instanceof Model) {
                if (! $ability->exists) {
                    throw new InvalidArgumentException('Provided ability model does not existing. Did you forget to save it?');
                }

                $abilitiesList->push($ability);
            } elseif (is_numeric($ability)) {
                $abilitiesList->push(static::findOrFail($ability));
            } elseif (is_string($ability)) {
                $entityType = null;
                if (is_string($model)) {
                    $entityType = Helper::getMorphClassName($model);
                } elseif ($model instanceof Model) {
                    $entityType = $model->getMorphClass();
                }

                $instance = static::query()
                    ->where('name', $ability)
                    ->where('entity_id', $model instanceof Model && $model->exists ? $model->getKey() : null)
                    ->where('entity_type', $entityType)
                    ->first();

                if ($instance) {
                    $abilitiesList[] = $instance;
                } elseif ($create) {
                    $abilitiesList[] = static::createAbility($ability, $model);
                }
            }
        }
    }

    /**
     * Create a new ability.
     *
     * @param string $ability
     * @param \Illuminate\Database\Eloquent\Model|string|null $model
     * @return mixed
     */
    public static function createAbility($ability, $model = null)
    {
        if ($model === null) {
            return static::forceCreate([
                'name' => $ability,
            ]);
        }

        if ($model === '*') {
            return static::forceCreate([
                'name' => $ability,
                'entity_type' => '*',
            ]);
        }

        return static::forceCreate([
            'name' => $ability,
            'entity_id' => $model instanceof Model && $model->exists ? $model->getKey() : null,
            'entity_type' => is_string($model) ? Helper::getMorphClassName($model) : $model->getMorphClass(),
        ]);
    }
}
