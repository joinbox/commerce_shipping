<?php

namespace Drupal\commerce_shipping\EventSubscriber;

use Drupal\commerce_cart\Event\CartEmptyEvent;
use Drupal\commerce_cart\Event\CartEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CartEvents::CART_EMPTY => 'onCartEmpty',
    ];
  }

  /**
   * Remove shipments from an emptied cart.
   *
   * The order refresh does not process orders which have no order items,
   * preventing the shipping order processor from removing shipments.
   *
   * @todo Re-evaluate after #3062594 is fixed.
   *
   * @param \Drupal\commerce_cart\Event\CartEmptyEvent $event
   *   The event.
   */
  public function onCartEmpty(CartEmptyEvent $event) {
    $cart = $event->getCart();
    if (!$cart->hasField('shipments') || $cart->get('shipments')->isEmpty()) {
      return;
    }

    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface[] $shipments */
    $shipments = $cart->get('shipments')->referencedEntities();
    foreach ($shipments as $shipment) {
      $shipment->delete();
    }
    $cart->set('shipments', []);
  }

}
