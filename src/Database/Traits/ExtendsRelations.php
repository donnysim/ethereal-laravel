<?php

namespace Ethereal\Database\Traits;

use Ethereal\Database\Relations\Manager;
use Illuminate\Database\Eloquent\Relations\Relation;
use UnexpectedValueException;

trait ExtendsRelations
{
    /**
     * Use extended relations handling.
     *
     * @var bool
     */
    protected $extendedRelations = true;

    /**
     * Set the specific relationship in the model.
     *
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function setRelation($name, $value)
    {
        if (!$this->extendedRelations || !method_exists($this, $name)) {
            return parent::setRelation($name, $value);
        }

        $relation = $this->{$name}();

        if (!$relation instanceof Relation) {
            throw new UnexpectedValueException("`{$name}` is not a valid relation.");
        }

        if (!Manager::canHandle($relation)) {
            return parent::setRelation($name, $value);
        }

        $this->relations[$name] = (new Manager($this))->makeHandler($relation, $name, $value, 0)->build();

        return $this;
    }
}
