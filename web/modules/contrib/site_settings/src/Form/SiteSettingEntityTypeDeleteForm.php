<?php

namespace Drupal\site_settings\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\site_settings\SiteSettingsLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete Site Setting type entities.
 */
class SiteSettingEntityTypeDeleteForm extends EntityConfirmFormBase {

  /**
   * The site settings loader service.
   *
   * @var \Drupal\site_settings\SiteSettingsLoaderInterface
   */
  protected $siteSettingsLoader;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\site_settings\SiteSettingsLoaderInterface $site_settings_loader
   *   The site settings loader service.
   */
  public function __construct(SiteSettingsLoaderInterface $site_settings_loader) {
    $this->siteSettingsLoader = $site_settings_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('site_settings.loader'));
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.site_setting_entity_type.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get site settings of this type.
    $entities = $this->entityTypeManager
      ->getStorage('site_setting_entity')
      ->loadByProperties(['type' => $this->entity->id()]);

    if (!empty($entities)) {

      // Delete site settings of this type.
      $controller = $this->entityTypeManager->getStorage('site_setting_entity');
      $controller->delete($entities);
    }

    // Delete the site setting entity type.
    $this->entity->delete();

    $this->messenger()->addMessage($this->t('Successfully deleted the "@label" site setting.', [
      '@label' => $this->entity->label(),
    ]));

    // Rebuild the site settings cache.
    $this->siteSettingsLoader->clearCache();

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
