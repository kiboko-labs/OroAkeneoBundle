<?php

namespace Oro\Bundle\AkeneoBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class JsonConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.akeneo.validator.ajax.message';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'oro_akeneo.json_validator';
    }
}
