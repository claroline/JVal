<?php

/*
 * This file is part of the JVal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JVal\Constraint;

use JVal\Context;
use JVal\Walker;
use stdClass;

/**
 * Constraint for the "exclusiveMaximum" keyword.
 */
class ExclusiveMaximumConstraint extends AbstractRangeConstraint
{
    /**
     * {@inheritdoc}
     */
    public function keywords()
    {
        return ['exclusiveMaximum'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply($instance, stdClass $schema, Context $context, Walker $walker)
    {
        if ($instance >= $schema->exclusiveMaximum) {
            $context->addViolation('should be lesser than %s', [$schema->exclusiveMaximum]);
        }
    }
}
