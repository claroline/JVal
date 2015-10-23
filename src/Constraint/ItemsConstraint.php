<?php

namespace JsonSchema\Constraint;

use JsonSchema\Constraint;
use JsonSchema\Context;
use JsonSchema\Exception\Constraint\InvalidTypeException;
use JsonSchema\Types;
use JsonSchema\Walker;
use stdClass;

class ItemsConstraint implements Constraint
{
    public function keywords()
    {
        return ['items', 'additionalItems'];
    }

    public function supports($type)
    {
        return $type === Types::TYPE_ARRAY;
    }

    public function normalize(stdClass $schema, Context $context, Walker $walker)
    {
        if (!property_exists($schema, 'items')) {
            $schema->items = new stdClass();
        }

        if (!property_exists($schema, 'additionalItems') || $schema->additionalItems === true) {
            $schema->additionalItems = new stdClass();
        }

        $context->enterNode($schema->items, 'items');

        if (is_object($schema->items)) {
            $walker->parseSchema($schema->items, $context);
        } elseif (is_array($schema->items)) {
            foreach ($schema->items as $index => $item) {
                $context->enterNode($schema->items[$index], $index + 1);

                if (!is_object($item)) {
                    throw new InvalidTypeException($context, Types::TYPE_OBJECT);
                }

                $walker->parseSchema($item, $context);
                $context->leaveNode();
            }
        } else {
            throw new InvalidTypeException($context, [Types::TYPE_OBJECT, Types::TYPE_ARRAY]);
        }

        $context->enterSibling($schema->additionalItems, 'additionalItems');

        if (is_object($schema->additionalItems)) {
            $walker->parseSchema($schema->additionalItems, $context);
        } elseif (!is_bool($schema->additionalItems)) {
            throw new InvalidTypeException($context, [Types::TYPE_OBJECT, Types::TYPE_BOOLEAN]);
        }

        $context->leaveNode();
    }

    public function apply($instance, stdClass $schema, Context $context, Walker $walker)
    {
        if (is_object($schema->items)) {
            // 8.2.3.1. If items is a schema, then the child instance must be
            // valid against this schema, regardless of its index, and
            // regardless of the value of "additionalItems".
            foreach ($instance as $index => $item) {
                $context->enterNode($item, $index + 1);
                $walker->applyConstraints($item, $schema->items, $context);
                $context->leaveNode();
            }
        } else { // "items" is an array
            $itemSize = count($schema->items);

            foreach ($instance as $index => $item) {
                $context->enterNode($item, $index + 1);

                // 8.2.3.2.  If the index is less than, or equal to, the size of
                // "items", the child instance must be valid against the
                // corresponding schema in the "items" array; otherwise, it must
                // be valid against the schema defined by "additionalItems".
                //
                // NOTE: this is adapted for 0-based indexation.
                if ($index < $itemSize) {
                    $walker->applyConstraints($item, $schema->items[$index], $context);
                } elseif ($schema->additionalItems === false) {
                    $context->addViolation('additional items are not allowed');
                } else {
                    $walker->applyConstraints($item, $schema->additionalItems, $context);
                }

                $context->leaveNode();
            }
        }
    }
}
