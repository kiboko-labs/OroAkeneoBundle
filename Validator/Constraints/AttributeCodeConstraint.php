<?php

namespace Oro\Bundle\AkeneoBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AttributeCodeConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.akeneo.validator.attribute_code.message';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'oro_akeneo.attribute_code_validator';
    }
}
