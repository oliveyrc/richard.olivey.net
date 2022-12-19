<?php

namespace Drupal\video_embed_html5\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class VideoEmbedHtml5ConfigForm
 * @package Drupal\video_embed_html5\Form
 */
class VideoEmbedHtml5ConfigForm extends ConfigFormBase {

  protected $fileUsage;

  /**
   * VideoEmbedHtml5ConfigForm constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   */
  public function __construct(ConfigFactoryInterface $config_factory, FileUsageInterface $file_usage) {
    parent::__construct($config_factory);
    $this->fileUsage = $file_usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('file.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'video_embed_html5_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['video_embed_html5.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('video_embed_html5.config');

    $form['add_placeholder'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add placeholder while generating video thumbnail'),
      '#description' => $this->t('This only applies when the module "PHP FFMpeg" is not installed.'),
      '#default_value' => $config->get('add_placeholder'),
    ];

    $form['placeholder'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Placeholder image'),
      '#upload_location' => 'public://',
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg', 'png'],
      ],
      '#default_value' => $config->get('placeholder'),
    ];


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('video_embed_html5.config');
    $config->set('add_placeholder', $form_state->getValue('add_placeholder'));
    $config->set('placeholder', $form_state->getValue('placeholder'));
    $config->save();

    if (!empty($form_state->getValue('placeholder')[0])) {
      // Record this module as using this file.
      /** @var FileInterface $placeholder */
      $placeholder = File::load($form_state->getValue('placeholder')[0]);
      $references = $this->fileUsage->listUsage($placeholder);
      if (empty($references)) {
        $this->fileUsage->add($placeholder, 'video_embed_html5', 'settings', 0);
      }
    }


    parent::submitForm($form, $form_state);
  }

}