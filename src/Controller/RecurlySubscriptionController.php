<?php

namespace Drupal\custom_recurly\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * Controller routines for Teachervision subscriptions.
 */
class RecurlySubscriptionController extends ControllerBase {

  /** @var \Symfony\Component\HttpFoundation\Request */
  private $request;

  /** @var \Drupal\Core\Database\Connection */
  private $database;

  /** @var \Drupal\Core\Routing\RouteMatchInterface */
  protected $routeMatch;

  /** @noinspection PhpMissingParentCallCommonInspection */
  /**
   * @inheritdoc
   *
   * @see ControllerBase::create()
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
    return new static(
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('database'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns render array listing available BillingPlans from.
   */
  public function listplans(RouteMatchInterface $route_match, $currency = NULL, $subscription_id = NULL) {

    //if user is not login then redirect to the user register page
    if (!\Drupal::currentUser()->id()) {
      $query = ['destination' => '/select-plan'];
      if (\Drupal::request()->query->get('gclid')) {
        $query['gclid'] = \Drupal::request()->query->get('gclid');
      }
      return $this->redirect('user.register', [], ['query' => $query]);
    }

    $entity_type_id = $this->config('recurly.settings')->get('recurly_entity_type');
    $entity = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id)->getLowercaseLabel();

    // Initialize the Recurly client with the site-wide settings.
    if (!recurly_client_initialize()) {
      return ['#markup' => $this->t('Could not initialize the Recurly client.')];
    }

    $mode = $subscription_id ? "change" : "signup";
    $subscriptions = [];

    // If loading an existing subscription.
    if (\Drupal::currentUser()->isAuthenticated()) {
      $currency = isset($currency) ? $currency : $this->config('recurly.settings')->get('recurly_default_currency');
      $account = recurly_account_load(['entity_type' => $entity_type, 'entity_id' => $entity->id()]);
      if ($account) {
        $subscriptions = recurly_account_get_subscriptions($account->account_code, 'active');
      }
    }
    // Make the list of subscriptions based on plan keys, rather than uuid.
    $plan_subscriptions = [];
    foreach ($subscriptions as $subscription) {
      $plan_subscriptions[$subscription->plan->plan_code] = $subscription;
    }

    //if the user has the subscription then redirect to that user to the profile page
    /*if (!empty($plan_subscriptions) && \Drupal::currentUser()->id()) {
      return new RedirectResponse("/user/" . \Drupal::currentUser()->id() . "/membership");
    }*/

    $all_plans = _get_all_subscription_plans();
    $enabled_plan_keys = $this->config('recurly.settings')->get('recurly_subscription_plans') ? : [];
    $enabled_plans = [];
    foreach ($enabled_plan_keys as $plan_code => $enabled) {
      foreach ($all_plans as $plan) {
        if ($enabled && $enabled['status'] && $plan_code == $plan->plan_code) {

          $enabled_plans[$plan_code] = $plan;
          $enabled_plans[$plan_code]->text_field = $enabled['text_of_subscription'];
        }
      }
    }
    return [
      '#theme' => [
        'custom_recurly_subscription_plan_select__' . $mode,
        'custom_recurly_subscription_plan_select'
      ],
      '#plans' => $enabled_plans,
      '#entity_type' => $entity_type,
      '#entity' => $entity,
      '#currency' => $currency,
      '#mode' => $mode,
      '#subscriptions' => $plan_subscriptions,
      '#subscription_id' => $subscription_id,
    ];
  }
}
