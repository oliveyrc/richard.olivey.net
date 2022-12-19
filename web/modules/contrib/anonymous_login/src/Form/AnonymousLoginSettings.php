<?php

namespace Drupal\anonymous_login\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AnonymousLoginSettings.
 */
class AnonymousLoginSettings extends ConfigFormBase {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'anonymous_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_login_settings';
  }

  /**
   * Constructs a AnonymousLoginSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator) {
    parent::__construct($config_factory);
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('anonymous_login.settings');
    $form['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Page paths'),
      '#default_value' => $config->get('paths'),
      '#description' => $this->t('Enter a list of page paths that will force anonymous users to login before viewing. After logging in, they will be redirected back to the requested page. Enter each path on a different line. Wildcards (*) can be used. Prefix a path with ~ (tilde) to exclude it from being redirected.'),
    ];
    $form['login_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login page path'),
      '#default_value' => ($config->get('login_path')) ? $config->get('login_path') : '/user/login',
      '#required' => TRUE,
      '#description' => $this->t('Enter the user login page path of your site.'),
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Login message'),
      '#default_value' => $config->get('message'),
      '#description' => $this->t('Optionally provide a message that will be shown to users when they are redirected to login.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Login page path validation.
    $path = $this->pathValidator
      ->getUrlIfValidWithoutAccessCheck($form_state->getValue('login_path'));
    if (!$path) {
      $form_state->setErrorByName('login_path', $this->t('Login page path is invalid. Check it please.'));
    }
    else {
      // Set path without language prefix.
      $form_state->setValue('login_path', '/' . $path->getInternalPath());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('anonymous_login.settings')
      ->set('paths', $form_state->getValue('paths'))
      ->set('login_path', $form_state->getValue('login_path'))
      ->set('message', $form_state->getValue('message'))
      ->save();
  }

}
