<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * UniqEmailCampaign.
 *
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
class UniqEmailCampaign extends Constraint
{
}
