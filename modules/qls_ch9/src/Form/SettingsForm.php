<?php

namespace Drupal\qls_ch9\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['qls_ch9.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qls_ch9_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['back_link'] = [
      '#markup' => '<a href="/quicklearning_symbol/qls_ch9" class="button">'.$this->t('back').'</a>',
      '#allowed_tags' => ['a'],
    ];

    $form['private_key_policy'] = [
      '#type' => 'item',
      '#markup' => $this->t('Private keys are not stored in Drupal configuration. Enter private keys only in the transaction example forms that require them.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    parent::submitForm($form, $form_state);
  }
}
