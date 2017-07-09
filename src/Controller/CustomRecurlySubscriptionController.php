<?php

namespace Drupal\custom_recurly\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\custom_recurly\Helper\CustomRecurlyHelper;


/**
 * Controller routines for Teachervision Custom Recurly.
 */
class CustomRecurlySubscriptionController extends ControllerBase {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Inject currentUser dependencies.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   */
  public function __construct(
    AccountProxyInterface $currentUser
  ) {
    $this->currentUser = $currentUser;
  }

  /**
   * Cancels renewal of free trial (i.e. does not become a paid subscription).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \InvalidArgumentException
   */
  public function cancelFreeTrial($user_id) {
    if ($this->currentUser->id() != $user_id) {
      return $this->redirect('subscription.membership');
    }

    // Initialize the Recurly client with the site-wide settings.
    if (!recurly_client_initialize()) {
      return ['#markup' => $this->t('Could not initialize the Recurly client.')];
    }

    $entity_type_id = $this->config('recurly.settings')->get('recurly_entity_type');
    $user = $entity = User::load($user_id);
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getLowercaseLabel();

    $local_account = recurly_account_load([
      'entity_type' => $entity_type,
      'entity_id' => $entity->id(),
    ], TRUE);
    try {
      $subscriptions = recurly_account_get_subscriptions($local_account->account_code, 'active');
      $subscription = reset($subscriptions);
      if ($subscription) {
        $subscription->cancel();
        $subscription->terminateWithoutRefund();
        $user->field_user_subscription = 'cancelled';
        $user->save();

        //unsubscribe the user from maropost
        $firstName = $user->hasField('field_first_name') ? $user->field_first_name->value : '';
        $lastName = $user->hasField('field_last_name') ? $user->field_last_name->value : '';
        \Drupal::service('maropost.contact_user_update')->saveUserData($user->getEmail(), $firstName, $lastName, "", "", "", "user_freetrial_cancelled");
        drupal_set_message($this->t('Free Trial Period cancelled'));
      } else {
        drupal_set_message($this->t('You have already cancelled free trail.'));
      }
    } catch (\Exception $e) {
      drupal_set_message($this->t('You have already cancelled free trail.'));
    }
    return $this->redirect('view.user_profile_pages.page_4', ['arg_0' => $user_id]);
  }

  /**
   * Cancels user subscription.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \InvalidArgumentException
   */
  public function cancelSubscription($id) {
    $userId = $id;
    if (!$userId) {
      return $this->redirect('subscription.membership');
    }

    // Initialize the Recurly client with the site-wide settings.
    if (!recurly_client_initialize()) {
      return ['#markup' => $this->t('Could not initialize the Recurly client.')];
    }

    $entity_type_id = $this->config('recurly.settings')->get('recurly_entity_type');
    $user = $entity = \Drupal\user\Entity\User::load($userId);
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getLowercaseLabel();

    $local_account = recurly_account_load([
      'entity_type' => $entity_type,
      'entity_id' => $entity->id(),
    ], TRUE);

    try {
      $subscriptions = recurly_account_get_subscriptions($local_account->account_code, 'active');
      $subscription = reset($subscriptions);
      if ($subscription) {

        $subscription->terminateWithoutRefund();

        $user->field_user_subscription = 'cancelled';
        $user->save();

        //unsubscribe the user from maropost
        $firstName = isset($user->field_first_name->getValue()[0]['value']) ? $user->field_first_name->getValue()[0]['value'] : '';
        $lastName = isset($user->field_last_name->getValue()[0]['value']) ? $user->field_last_name->getValue()[0]['value'] : '';
        $marpostDataSave = \Drupal::service('maropost.contact_user_update')->saveUserData($user->getEmail(), $firstName, $lastName, "", "", "", "member_cancelled");

        drupal_set_message($this->t('Your current membership has been cancelled.'));
      } else {
        drupal_set_message($this->t('You have already cancelled plan.'));
      }
    } catch (\Exception $exc) {
      drupal_set_message($this->t('You have already cancelled plan.'));
    }

    return new RedirectResponse("/user/$userId/membership");
  }

  /**
   * Cancels renewal of user subscription.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \InvalidArgumentException
   */
  public function cancelRenewalSubscription($id) {
    $userId = $id;
    if (!$userId) {
      return $this->redirect('subscription.membership');
    }

    // Initialize the Recurly client with the site-wide settings.
    if (!recurly_client_initialize()) {
      return ['#markup' => $this->t('Could not initialize the Recurly client.')];
    }

    $entity_type_id = $this->config('recurly.settings')->get('recurly_entity_type');
    $user = $entity = \Drupal\user\Entity\User::load($userId);
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getLowercaseLabel();

    $local_account = recurly_account_load([
      'entity_type' => $entity_type,
      'entity_id' => $entity->id(),
    ], TRUE);

    try {
      $subscriptions = recurly_account_get_subscriptions($local_account->account_code, 'active');
      $subscription = reset($subscriptions);
      if ($subscription) {

        $subscription->cancel();
        //$subscription->terminateWithoutRefund();

        $user->field_user_subscription = 'autorenewal-cancel';
        $user->save();

        //unsubscribe the user from maropost
        $firstName = isset($user->field_first_name->getValue()[0]['value']) ? $user->field_first_name->getValue()[0]['value'] : '';
        $lastName = isset($user->field_last_name->getValue()[0]['value']) ? $user->field_last_name->getValue()[0]['value'] : '';
        $marpostDataSave = \Drupal::service('maropost.contact_user_update')->saveUserData($user->getEmail(), $firstName, $lastName, "", "", "", "member_cancelled");

        drupal_set_message($this->t('Automatic renewal of your membership has been cancelled. You will continue to have access until the Membership expiration date shown below.'));
      } else {
        drupal_set_message($this->t('You have already cancelled plan.'));
      }
    } catch (\Exception $exc) {
      drupal_set_message($this->t('You have already cancelled plan.'));
    }

    return new RedirectResponse("/user/$userId/membership");
  }

  /**
   * Function to create print page to print the receipt.
   *
   * @return array
   *   Drupal render array representing the receipt.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function printReceipt($id) {
    $userId = $id;
    if (!$userId) {
      return $this->redirect('subscription.membership');
    }

    // Initialize the Recurly client with the site-wide settings.
    if (!recurly_client_initialize()) {
      return ['#markup' => $this->t('Could not initialize the Recurly client.')];
    }

    $entity_type_id = $this->config('recurly.settings')->get('recurly_entity_type');
    $user = $entity = \Drupal\user\Entity\User::load($userId);
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getLowercaseLabel();

    $local_account = recurly_account_load([
      'entity_type' => $entity_type,
      'entity_id' => $entity->id(),
    ], TRUE);
    try {
      //get the last invoice number and redirec to invoice page.
      $invoice_list = \Recurly_InvoiceList::getForAccount($local_account->account_code, ['per_page' => 1]);
      $invoices = \Drupal::service('recurly.pager_manager')->pagerResults($invoice_list, 1);
      foreach ($invoices as $value) {
        $invoice_number = $value->invoice_number;
        $pdfUrl = "/user/$userId/subscription/invoices/$invoice_number/pdf";
        return new RedirectResponse($pdfUrl);
      }
      drupal_set_message($this->t("You don't have any invoice."));
    } catch (\Exception $exc) {
      drupal_set_message($this->t('You have already cancelled plan.'));
    }
    return new RedirectResponse("/user/$userId/membership");
  }

  public function changePlan($plan_id) {
    $user_id = $this->currentUser->id();
    $subscription = CustomRecurlyHelper::getUserSubscription($user_id);
    //check the plan code is in the admin configuration for changing the plan from the recurly direct
    try {
      $new_plan = \Recurly_Plan::get($plan_id);
      if (!empty($subscription)) {
        //if for this plan admin configuration has been set that user can able to change the plan then plan will changes into recurly else we will send a mail to admin
        $changePlan = CustomRecurlyHelper::checkChangePlan($subscription->plan->plan_code);
        if ($changePlan) {
          $subscription->plan_code = $plan_id;
          $subscription->updateImmediately();
          $message = "Your plan changes has been changed to this.";
        } else {
          $message = "Your plan changes request has been send to admin, admin will contact you soon.";
          //send the mail to admin configuration
        }
        drupal_set_message($message);
        return new RedirectResponse("/user/$user_id/membership");
      }
    } catch (\Exception $ex) {
      return $this->redirect('recurly__subscription.recurly_subscription_plans');
    }

    return $this->redirect('recurly__subscription.recurly_subscription_plans');
  }

}
