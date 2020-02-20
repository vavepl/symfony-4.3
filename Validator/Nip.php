<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;


/**
 * @Annotation
 */
class Nip extends Constraint
{
    const INVALID_PATTERN_ERROR = 'b2e5a844-1127-43dd-be56-861a5e37fd8e';
    const INVALID_CHECKSUM_ERROR = 'ba94bb8a-88bc-4be8-9577-f71ec17cd7e7';

    protected static $errorNames = [
        self::INVALID_PATTERN_ERROR => 'INVALID_PATTERN_ERROR',
        self::INVALID_CHECKSUM_ERROR => 'INVALID_CHECKSUM_ERROR',
    ];

    public $pattern = null;
    public $patternMessage = 'This is not a valid NIP number.';
    public $checksum = true;
    public $checksumMessage = 'This is not a valid NIP number.';
    public $allowDashes = false;
    public $requireDashes = false;
    public $allowPrefix = false;
    public $requirePrefix = false;
    public $prefixLength = 2;

    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}