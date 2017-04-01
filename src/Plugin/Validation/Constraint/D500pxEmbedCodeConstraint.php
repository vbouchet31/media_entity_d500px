<?php

namespace Drupal\media_entity_d500px\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check if a value is a valid 500px embed code/URL.
 *
 * @constraint(
 *   id = "D500pxEmbedCode",
 *   label = @Translation("500px embed code", context = "Validation"),
 *   type = { "link", "string", "string_long" }
 * )
 */
class D500pxEmbedCodeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Not valid 500px embed code.';

}
