<?php

namespace Drupal\addanother\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Add another settings for this site.
 */
class AddAnotherSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'addanother_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['addanother.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('addanother.settings');

    $form['addanother_display'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default settings for newly created content types'),
    ];
    $form['addanother_display']['default_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable <i>Display Add another button on node add form</i> for new content types.'),
      '#default_value' => $config->get('default_button'),
    ];
    $form['addanother_display']['default_message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable <i>Display the Add another message after node creation</i> for new content types.'),
      '#default_value' => $config->get('default_message'),
    ];
    $form['addanother_display']['default_tab'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable <i>Display the Add another tab</i> for new content types.'),
      '#default_value' => $config->get('default_tab'),
    ];
    $form['addanother_display']['default_tab_edit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable <i>Also display the Add another tab on edit page</i> for new content types.'),
      '#default_value' => $config->get('default_tab_edit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('addanother.settings')
      ->set('default_button', $form_state->getValue('default_button'))
      ->set('default_message', $form_state->getValue('default_message'))
      ->set('default_tab', $form_state->getValue('default_tab'))
      ->set('default_tab_edit', $form_state->getValue('default_tab_edit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
