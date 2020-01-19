<?php

namespace Drupal\commerce_shipping;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;

/**
 * Completes the order refresh process for shipments.
 *
 * Saves any previously modified shipments.
 * Transfers the shipment amount and adjustments to the order.
 *
 * Runs after other order processors (promotion, tax, etc).
 *
 * @see \Drupal\commerce_shipping\EarlyOrderProcessor
 */
class LateOrderProcessor implements OrderProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if (!$order->hasField('shipments') || $order->get('shipments')->isEmpty()) {
      return;
    }
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface[] $shipments */
    $shipments = $order->get('shipments')->referencedEntities();
    $single_shipment = count($shipments) === 1;

    foreach ($shipments as $shipment) {
      if ($shipment->hasTranslationChanges()) {
        $shipment->save();
      }

      if ($amount = $shipment->getAmount()) {
        // Shipments without an amount are incomplete / unrated.
        $order->addAdjustment(new Adjustment([
          'type' => 'shipping',
          'label' => $single_shipment ? t('Shipping') : $shipment->getTitle(),
          'amount' => $amount,
          'source_id' => $shipment->id(),
        ]));
        foreach ($shipment->getAdjustments() as $adjustment) {
          $order->addAdjustment($adjustment);
        }
      }
    }
  }

}
