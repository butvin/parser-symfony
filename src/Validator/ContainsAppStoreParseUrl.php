<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ContainsAppStoreParseUrl extends Constraint
{
    public $message = 'Not found parser for url "{{ url }}", please enter allowed url.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}