<?php

namespace Drupal\custom_recurly\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Recurly routes.
 */
class CustomRecurlyJsRouteSubscriber extends RouteSubscriberBase {

/**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
  // kint($collection->get('entity.poll.canonical')->getDefault('_title_callback'));die;
//    if ($route = $collection->get('entity.poll.canonical')) {
//      $route->setDefault('_title_callback', '\Drupal\poll_fe_tv\Controller\PollRelatedContent::alterTitle');
//    }
  }

}
