<?php

namespace Drupal\custom_recurly\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Component\Utility\Html;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a 'Recurly Update AccountInfo' block.
 *
 * @Block(
 *   id = "recurly_update_account_info",
 *   admin_label = @Translation("Recurly Update AccountInfo"),
 * )
 */
class UpdateAccountInfoBlock extends BlockBase {
  
  /**
   * Card type string to match against Recurly response.
   */
  const CARD_TYPE_AMEX = 'American Express';

  /**
   * American Express card number length.
   */
  const CARD_LENGTH_AMEX = 11;

  /**
   * Standard credit card length.
   */
  const CARD_LENGTH_OTHER = 12;  

  public function build() {
    /** @var RouteMatchInterface $route_match */
    $output = [];
    $route_match = \Drupal::service('current_route_match');
    $uid = $route_match->getParameter('arg_0');
    $user = User::load($uid);
    if (!$user) {
      throw new NotFoundHttpException();
    }
    // Initialize the Recurly client with the site-wide settings.
    if (!recurly_client_initialize()) {
      return [
        '#markup' => $this->t('Could not initialize the Recurly client.'),
      ];
    }
    // See if we have a local mapping of entity ID to Recurly account code.
    $recurly_account = recurly_account_load(['entity_type' => 'user', 'entity_id' => $uid]);
    try {
      $billing_info = \Recurly_BillingInfo::get($recurly_account->account_code);
      // Format expiration date.
      $exp_date = sprintf('%1$02d', Html::escape($billing_info->month)) . '/' . Html::escape($billing_info->year);

      $output = [
        '#theme' => 'recurly_payment_info',
        '#card_type' => Html::escape($billing_info->card_type),
        '#exp' => $exp_date,
        '#uid' => $uid,
        '#accnum' => Html::escape($billing_info->last_four),
        '#cache' => ['max-age' => 0],
      ];
    }
    catch (\Recurly_NotFoundError $e) {
      $output = [
        '#markup' => $this->t('Recurly account not found.'),
      ];
    }
    catch(\Exception $e){
        $output = [
        '#markup' => $this->t('Exception occured while fetching data.'),
      ];
    }
    
    return $output;
  }

}
