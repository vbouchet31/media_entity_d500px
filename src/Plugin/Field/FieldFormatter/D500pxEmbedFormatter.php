<?php

namespace Drupal\media_entity_d500px\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\media_entity\EmbedCodeValueTrait;

/**
 * Plugin implementation of the 'd500px_embed' formatter.
 *
 * @FieldFormatter(
 *   id = "d500px_embed",
 *   label = @Translation("500px embed"),
 *   field_types = {
 *     "link", "string", "string_long"
 *   }
 * )
 */
class D500pxEmbedFormatter extends FormatterBase {

  use EmbedCodeValueTrait;

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();

    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#type' => 'markup',
        '#markup' => $this->getEmbedCode($item),
        '#allowed_tags' => ['img', 'p', 'a', 'div', 'script'],
      ];
    }

    return $element;
  }

}
