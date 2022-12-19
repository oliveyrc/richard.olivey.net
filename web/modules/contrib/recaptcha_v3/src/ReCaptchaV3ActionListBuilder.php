<?php

namespace Drupal\recaptcha_v3;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of reCAPTCHA v3 action entities.
 */
class ReCaptchaV3ActionListBuilder extends ConfigEntityListBuilder {

  /**
   * Recaptcha v3 challenge types.
   *
   * @var array
   *    An array of recaptcha v3 challenge types.
   */
  protected $challengeTypes;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Action');
    $header['threshold'] = $this->t('Threshold');
    $header['challenge'] = $this->t('Fail challenge');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\recaptcha_v3\ReCaptchaV3ActionInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['threshold'] = $entity->getThreshold();
    $challenge_type = $entity->getChallenge();
    $row['challenge'] = $this->getCaptchaChallengeTypes()[$challenge_type] ?? $this->t('Not defined');
    return $row + parent::buildRow($entity);
  }

  /**
   * Get reCaptcha v3 challenge types.
   *
   * @return array
   *   All reCaptcha v3 challenge types.
   */
  protected function getCaptchaChallengeTypes() {
    if ($this->challengeTypes === NULL) {
      $this->challengeTypes = \Drupal::service('captcha.helper')->getAvailableChallengeTypes(FALSE);
      $this->challengeTypes = array_filter($this->challengeTypes, static function ($captcha_type) {
        return !(strpos($captcha_type, 'recaptcha_v3') === 0);
      }, ARRAY_FILTER_USE_KEY);
      $default = \Drupal::config('recaptcha_v3.settings')->get('default_challenge');
      $this->challengeTypes['default'] = $this->challengeTypes[$default] ?? $this->t('Default');
    }
    return $this->challengeTypes;
  }

}
