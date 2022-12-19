<?php

namespace Drupal\site_settings\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_settings\SiteSettingsLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting Site Setting entities.
 *
 * @ingroup site_settings
 */
class SiteSettingEntityDeleteForm extends ContentEntityDeleteForm {

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
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, SiteSettingsLoaderInterface $site_settings_loader) {
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
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Clear the site settings cache.
    $this->siteSettingsLoader->clearCache();

    // Submit the parent form.
    parent::submitForm($form, $form_state);
  }

}
