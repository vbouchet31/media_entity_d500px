<?php

namespace Drupal\media_entity_d500px\Plugin\Validation\Constraint;

use Drupal\media_entity\EmbedCodeValueTrait;
use Drupal\media_entity_d500px\Plugin\MediaEntity\Type\D500px;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the D500pxEmbedCode constraint.
 */
class D500pxEmbedCodeConstraintValidator extends ConstraintValidator {

  use EmbedCodeValueTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $value = str_replace(["\r", "\n"],'', $this->getEmbedCode($value));

    if (!isset($value)) {
      return;
    }

    $matches = [];
    foreach (D500px::$validationRegexp as $pattern => $key) {
      if (preg_match($pattern, $value, $item_matches)) {
        $matches[] = $item_matches;
      }
    }

    if (empty($matches)) {
      $this->context->addViolation($constraint->message);
    }
  }

}
