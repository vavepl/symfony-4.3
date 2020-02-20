<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;


/**
 * @Annotation
 */
class Phone extends Constraint
{
    public $message = 'Wprowadź prawidłowy numer telefonu. Przykład: 48600100100 lub 600100100';

    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}
