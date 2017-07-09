<?php

namespace Drupal\custom_recurly\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RecurlyTrialDurationForm.
 *
 * @package Drupal\custom_recurly\Form
 */
class RecurlyTrialDurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'custom_recurly.trial_duration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recurly_trial_duration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_recurly.trial_duration');
    $list_value = $config->get('free_trial_duration');

    // Provides a select list for page views fetch range.
    $form['free_trial_duration'] = [
      '#type' => 'select',
      '#title' => $this->t('Cancelled User Free Trail Check'),
      '#default_value' => $list_value ? $list_value : '%y',
      '#description' => $this->t('Select the time after which a cancelled user can subscribe for free trial again.'),
      '#options' => array(
        '%a' => '1 day',
        '%m' => '1 month',
        '%y' => '1 year',
      ),
    ];

    // Provide the plan code for which changes plan should be run without admin configuration.
    $form['change_plan_code_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Plan Code for Change Plan'),
      '#default_value' => $config->get('change_plan_code_list') ? $config->get('change_plan_code_list') : '',
      '#description' => $this->t('Enter plan code with new line separator for which changes plan should be work.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('custom_recurly.trial_duration')
      ->set('free_trial_duration', $form_state->getValue('free_trial_duration'))
      ->set('change_plan_code_list', $form_state->getValue('change_plan_code_list'))->save();
  }

}
