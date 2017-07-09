<?php

/**
 * @file
 * Contains \Drupal\custom_recurly\Event\CustomRecurlyEventSubscriber.
 */

namespace Drupal\custom_recurly\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\recurlyjs\RecurlyJsEvents;
use Drupal\user\Entity\User;
use Drupal\subscription\Helper\TvUser;
use Drupal\recurlyjs\Event\SubscriptionAlter;
use Drupal\recurlyjs\Event\SubscriptionCreated;

/**
 * Alter subscriptions.
 */
class CustomRecurlyEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RecurlyJsEvents::SUBSCRIPTION_CREATED][] = ['subscriptionCreated'];
    $events[RecurlyJsEvents::SUBSCRIPTION_ALTER][] = ['subscriptionAlter'];
    return $events;
  }

  /**
   * Alter a subscription before it is created.
   */
  public function subscriptionAlter(SubscriptionAlter $event) {
    // check if free trial should be given or not.
    $config_trial = \Drupal::config('custom_recurly.trial_duration')->get('free_trial_duration');
    $config_trial_format = $config_trial ? $config_trial : '%y';
    $not_give_trial = FALSE;
    try {
      $expired_subscriptions = recurly_account_get_subscriptions($event->getSubscription()->account->account_code, 'expired');
      foreach ($expired_subscriptions as $expired_subscriptions_value) {
        // Expired plan datetime
        $expires_at_datetime = $expired_subscriptions_value->expires_at;
        // Todays datetime        
        $todays_datetime = new \DateTime('today', new \DateTimeZone("UTC"));
        $interval = $expires_at_datetime->diff($todays_datetime);
        $time_passed = $interval->format($config_trial_format);
        // If account expired is less than 1 year, we will not give free trial.
        if($time_passed < 1){
          $not_give_trial = TRUE;
          break;
        }
      }
    } catch (\Exception $e) {
      syslog(E_ERROR, $e->getMessage());
    }

    $subscription = $event->getSubscription();
    if ($not_give_trial) {
      $subscription->trial_ends_at = new \DateTime('today', new \DateTimeZone("UTC"));
    }
    $subscription->account->username = $event->getEntity()->get('uuid')->value;
    $event->updateSubscription($subscription);
  }

  /**
   * Respond to a subscription being created.
   */
  public function subscriptionCreated(SubscriptionCreated $event) {
    $subscription = $event->getSubscription();

    //check the data accrording to the trail started date and add the status to the user according to the trail expire date
    $trial_started_at = $subscription->trial_ends_at;
    $today_time = new \DateTime('today', new \DateTimeZone("UTC"));
    $interval = $trial_started_at->diff($today_time);
    $days_passed = $interval->format('%a');
    if ($days_passed >= 1) {
      $status = "free";
      $expiry_date = $subscription->trial_ends_at;
      $user_status = 'user_freetrial';
    } else {
      $status = "subscribed";
      $expiry_date = $subscription->current_period_ends_at;
      $user_status = 'member_subscribed';
    }

    //update the user data in the drupal system and add those field in the drupal system
    //field_user_subscription, field_next_billing_date, field_entitlement_expiry_date
    $user_entity = User::load(\Drupal::currentUser()->id());
    $user = new TvUser($user_entity);
    $time_zone = new \DateTimeZone(DATETIME_STORAGE_TIMEZONE);
    $user->set(
      'field_next_billing_date',
      $expiry_date->setTimezone($time_zone)
        ->format(DATETIME_DATETIME_STORAGE_FORMAT)
    );
    $user->set(
      'field_entitlement_expiry_date',
      $expiry_date->setTimezone($time_zone)
        ->format(DATETIME_DATETIME_STORAGE_FORMAT)
    );
    $user->set('field_user_subscription', $status);
    $user->save();

    //send the user data in the maropost
    $expiry_date = $user->hasField('field_entitlement_expiry_date') ? $user->field_entitlement_expiry_date->value : '';
    \Drupal::service('maropost.contact_user_update')->saveUserData($user->getEmail(),
      $user->getFirstName(), $user->getLastName(), $expiry_date, '', '', $user_status, $subscription->plan_code);
  }

}
