<?php

namespace Oro\Bundle\AkeneoBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class AlternativeIdentifierConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.akeneo.validator.alternative_identifier.message';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy()
    {
        return 'oro_akeneo.alternative_identifier_validator';
    }
}
