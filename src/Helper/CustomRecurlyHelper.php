<?php
/**
 * @file
 * Contains Helper.php
 *
 * PHP Version 5
 */

namespace Drupal\custom_recurly\Helper;

class CustomRecurlyHelper {
  /**
   * Get the user subscription form the user id
   *
   * @param string $user_id
   *
   * @return array
   */
  public static function getUserSubscription($user_id) {
    $entity_type_id = \Drupal::config('recurly.settings')->get('recurly_entity_type');
    $entity = \Drupal\user\Entity\User::load($user_id);
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getLowercaseLabel();

    $local_account = recurly_account_load([
      'entity_type' => $entity_type,
      'entity_id' => $entity->id(),
    ], TRUE);

    $subscriptions = recurly_account_get_subscriptions($local_account->account_code, 'active');
    return reset($subscriptions);
  }

  public static function checkChangePlan($plan_code) {
    $change_plan_setting = array_map('trim', explode("\n", \Drupal::config('custom_recurly.trial_duration')->get('change_plan_code_list')));
    if (in_array($plan_code, $change_plan_setting)) {
      $variables['change_plan_status'] = true;
      $variables['change_plan_message'] = "Do you really want to change the plan";
      return true;
    }
    return false;
  }
}
