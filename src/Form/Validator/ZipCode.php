<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * ZipCode.
 *
 * @Annotation
 */
#[\Attribute]
class ZipCode extends Constraint
{
    public string $message = "";
    public array $departments = [];

    /**
     * ZipCode constructor.
     *
     * @param mixed $options
     * @param string|null $message
     */
    public function __construct(array $options = null, string $message = null)
    {
        parent::__construct($options);

        $this->message = $message ?? $this->message;
        $this->departments = !empty($options['departments']) ? $options['departments'] : $this->departments;
    }
}
