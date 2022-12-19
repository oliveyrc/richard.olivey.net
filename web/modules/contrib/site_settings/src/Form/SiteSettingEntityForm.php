<?php

namespace Drupal\site_settings\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_settings\Entity\SiteSettingEntityType;
use Drupal\site_settings\SiteSettingsLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Site Setting edit forms.
 *
 * @ingroup site_settings
 */
class SiteSettingEntityForm extends ContentEntityForm {

  /**
   * The site settings loader.
   *
   * @var \Drupal\site_settings\SiteSettingsLoaderInterface
   */
  protected $siteSettingsLoader;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\site_settings\SiteSettingsLoaderInterface $site_settings_loader
   *   The site settings loader service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, SiteSettingsLoaderInterface $site_settings_loader) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->siteSettingsLoader = $site_settings_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('site_settings.loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\site_settings\Entity\SiteSettingEntity $entity */
    $form = parent::buildForm($form, $form_state);
    $site_settings_entity_type = SiteSettingEntityType::load($this->entity->getType());

    $form['heading1'] = [
      '#markup' => '<h2>' . $site_settings_entity_type->get('label') . '</h2>',
      '#weight' => -100,
    ];

    // Set entity title and fieldset to match the bundle.
    $form['name']['widget'][0]['value']['#value'] = $site_settings_entity_type->get('label');
    $form['fieldset']['widget'][0]['value']['#value'] = $site_settings_entity_type->get('fieldset');

    // Hide fields.
    hide($form['name']);
    hide($form['user_id']);
    hide($form['fieldset']);
    if (isset($form['multiple'])) {
      hide($form['multiple']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $entity = $this->entity;
    $entity_bundle = $entity->bundle();
    $entity_type = $entity->getEntityType()->getBundleEntityType();

    // Get existing entities in this settings bundle.
    $query = $this->entityTypeManager->getStorage('site_setting_entity')->getQuery();
    $query->accessCheck(TRUE);
    $query->condition('type', $entity_bundle);
    $existing = $query->execute();
    $bundle = $this->entityTypeManager->getStorage($entity_type)->load($entity_bundle);

    if (!$bundle->multiple) {
      if (count($existing) > 0 && $entity->id() != reset($existing)) {
        $form_state->setErrorByName('name', $this->t('There can only be one of this setting.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Save the form.
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Site Setting.', [
          '%label' => $this->entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Site Setting.', [
          '%label' => $this->entity->label(),
        ]));
    }

    // Clear the site settings cache.
    $this->siteSettingsLoader->clearCache();

    $form_state->setRedirect('entity.site_setting_entity.collection');
  }

}
