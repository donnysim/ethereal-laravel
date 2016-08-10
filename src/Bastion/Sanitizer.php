<?php

namespace Ethereal\Bastion;

use InvalidArgumentException;
use Traversable;

class Sanitizer
{
    protected $registry = [];

    /**
     * Register sanitizer handler for object.
     *
     * @param string $target
     * @param string $sanitizerClass
     */
    public function register($target, $sanitizerClass)
    {
        $this->registry[$target] = $sanitizerClass;
    }

    /**
     * Remove sanitization handler for target.
     *
     * @param string $target
     */
    public function remove($target)
    {
        unset($this->registry[$target]);
    }

    /**
     * Sanitize given object.
     *
     * @param mixed $target
     * @throws \InvalidArgumentException
     */
    public function sanitize(&$target)
    {
        $isCollection = $target instanceof Traversable || is_array($target);

        if ($isCollection) {
            if (! count($target)) {
                return;
            }

            $class = get_class(array_first($target));
        } else {
            $class = get_class($target);
        }

        if (! isset($this->registry[$class])) {
            throw new InvalidArgumentException('No sanitizer registered for type ' . $class);
        }

        $handler = app($this->registry[$class]);

        if ($isCollection) {
            foreach ($target as &$item) {
                $handler->sanitize($item);
            }
        } else {
            $handler->sanitize($target);
        }
    }
}