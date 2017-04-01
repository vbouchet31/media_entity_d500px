<?php

namespace Drupal\media_entity_d500px\Plugin\MediaEntity\Type;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\media_entity\MediaTypeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media type plugin for 500px.
 *
 * @MediaType(
 *   id = "d500px",
 *   label = @Translation("500px"),
 *   description = @Translation("Provides business logic and metadata for 500px.")
 * )
 */
class D500px extends MediaTypeBase {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config_factory->get('media_entity.settings'));
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * List of validation regular expressions.
   *
   * @var array
   */
  public static $validationRegexp = array(
    '@(?P<shortcode><div class=\'pixels-photo\'>\s*<p>\s*<img src=\'https://drscdn.500px.org/photo/(?P<id>[0-9]+)/.*/[\w]+\' alt=\'(.*)\'>\s*</p>\s*<a href=\'https://500px.com/photo/[0-9]+/[\w-]+\' alt=\'(.*)\'></a>\s*</div><script type=\'text/javascript\' src=\'https://500px.com/embed.js\'></script>)@i' => 'shortcode',
  );

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'use_500px_api' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    $fields = array(
      'shortcode' => $this->t('500px shortcode'),
    );

    if ($this->configuration['use_500px_api']) {
      $fields += array(
        'id' => $this->t('Picture ID'),
        'name' => $this->t('Picture name'),
        'description' => $this->t('Picture description'),
        'username' => $this->t('Author of the picture'),
        'camera' => $this->t('Name of the camera used for the picture'),
        'votes' => $this->t('Number of votes'),
      );
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    $matches = $this->matchRegexp($media);

    if (!$matches['shortcode']) {
      return FALSE;
    }
    if ($name == 'shortcode') {
      return $matches['shortcode'];
    }

    if (!$matches['id']) {
      return FALSE;
    }
    if ($name == 'id') {
      return $matches['id'];
    }

    // If we have auth settings return the other fields.
    if ($this->configuration['use_500px_api'] && $d500px = $this->fetchD500px($matches['id'])) {
      //echo '<pre>' . print_r($d500px, 1) . '</pre>';die;

      switch ($name) {
        case 'name':
          if (isset($d500px->name)) {
            return $d500px->name;
          }
          return FALSE;

        case 'description':
          if (isset($d500px->description)) {
            return $d500px->description;
          }
          return FALSE;

        case 'username':
          if (isset($d500px->user->username)) {
            return $d500px->user->username;
          }
          return FALSE;

        case 'camera':
          if (isset($d500px->camera)) {
            return $d500px->camera;
          }
          return FALSE;

        case 'votes':
          if (isset($d500px->votes_count)) {
            return $d500px->votes_count;
          }
          return FALSE;

        case 'thumbnail':
          if (isset($d500px->image_url[1])) {
            return $d500px->image_url[1];
          }
          return FALSE;

        case 'thumbnail_local':
           $directory = $this->configFactory->get('media_entity_d500px.settings')->get('local_images');
          if (!file_exists($directory)) {
            file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
          }

          $local_uri = $this->getField($media, 'thumbnail_local_uri');
          if ($local_uri) {
            if (file_exists($local_uri)) {
              return $local_uri;
            }
            else {
              $image_url = $this->getField($media, 'thumbnail');
              $image_data = file_get_contents($image_url);
              if ($image_data) {
                return file_unmanaged_save_data($image_data, $local_uri, FILE_EXISTS_REPLACE);
              }
            }
          }
          return FALSE;

        case 'thumbnail_local_uri':
          if (isset($d500px->images[1]->url)) {
            return $this->configFactory->get('media_entity_d500px.settings')->get('local_images') . '/' . $matches['id'] . '.' . $d500px->images[1]->format;
          }
          return FALSE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $bundle = $form_state->getFormObject()->getEntity();
    $allowed_field_types = ['string', 'string_long', 'link'];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $bundle->id()) as $field_name => $field) {
      if (in_array($field->getType(), $allowed_field_types) && !$field->getFieldStorageDefinition()->isBaseField()) {
        $options[$field_name] = $field->getLabel();
      }
    }

    $form['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field with source information'),
      '#description' => $this->t('Field on media entity that stores 500px embed code. You can create a bundle without selecting a value for this dropdown initially. This dropdown can be populated after adding fields to the bundle.'),
      '#default_value' => empty($this->configuration['source_field']) ? NULL : $this->configuration['source_field'],
      '#options' => $options,
    ];

    $form['use_500px_api'] = [
      '#type' => 'select',
      '#title' => $this->t('Whether to use 500px api to fetch pics or not.'),
      '#description' => $this->t("500px's API can be used to fetch some metadata which can then be stored in Drupal fields. In order to use 500px's API, you have to configure @link.", ['@link' => Link::createFromRoute('500px integration', 'd500px.settings')->toString()]),
      '#default_value' => empty($this->configuration['use_500px_api']) ? 0 : $this->configuration['use_500px_api'],
      '#options' => [
        0 => $this->t('No'),
        1 => $this->t('Yes'),
      ],
    ];

    if (!\Drupal::moduleHandler()->moduleExists('d500px')) {
      $form['use_500px_api']['#disabled'] = TRUE;
      $form['use_500px_api']['#description'] = $this->t("In order to use 500px's API, you have to enable D500px.", ['@link' => Link::fromTextAndUrl('D500px module', Url::fromUri('https://www.drupal.org/project/d500px'))->toString()]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function attachConstraints(MediaInterface $media) {
    parent::attachConstraints($media);

    if (isset($this->configuration['source_field'])) {
      $source_field_name = $this->configuration['source_field'];
      if ($media->hasField($source_field_name)) {
        foreach ($media->get($source_field_name) as &$embed_code) {
          /** @var \Drupal\Core\TypedData\DataDefinitionInterface $typed_data */
          $typed_data = $embed_code->getDataDefinition();
          $typed_data->addConstraint('D500pxEmbedCode');
        }
      }
    }
  }

  /**
   * Runs preg_match on embed code.
   *
   * @param MediaInterface $media
   *   Media object.
   *
   * @return array|bool
   *   Array of preg matches or FALSE if no match.
   *
   * @see preg_match()
   */
  protected function matchRegexp(MediaInterface $media) {
    $matches = array();

    if (isset($this->configuration['source_field'])) {
      $source_field = $this->configuration['source_field'];
      if ($media->hasField($source_field)) {
        $property_name = $media->{$source_field}->first()->mainPropertyName();

        foreach (static::$validationRegexp as $pattern => $key) {
          if (preg_match($pattern, str_replace(["\r", "\n"],'', $media->{$source_field}->{$property_name}), $matches)) {
            return $matches;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Get a single pic.
   *
   * @param string $id
   *   The pic ID.
   * @return
   * @throws \Drupal\media_entity\MediaTypeException
   */
  protected function fetchD500px($id) {
    // @TODO: Use dependency injection instead.
    $d500pxintegration = \Drupal::service('d500px.d500pxintegration');
    $result = $d500pxintegration->requestD500px('photos/' . $id, ['image_size' => [100, 200]]);

    if ($result && isset($result->photo)) {
      return $result->photo;
    }
    else {
      throw new MediaTypeException(NULL, 'The media could not be retrieved.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->config->get('icon_base') . '/500px.png';
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    // If there's already a local image, use it.
    if ($local_image = $this->getField($media, 'thumbnail_local')) {
      return $local_image;
    }

    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultName(MediaInterface $media) {
    // Try to get some fields that need the API, if not available, just use the
    // ID as default name.
    $username = $this->getField($media, 'username');
    $id = $this->getField($media, 'id');
    if ($username && $id) {
      return $username . ' - ' . $id;
    }
    else {
      $id = $this->getField($media, 'id');
      if (!empty($od)) {
        return $id;
      }
    }

    return parent::getDefaultName($media);
  }

}
