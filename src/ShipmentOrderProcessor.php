<?php

namespace Drupal\commerce_shipping;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Processes the order's shipments.
 */
class ShipmentOrderProcessor implements OrderProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The shipping order manager.
   *
   * @var \Drupal\commerce_shipping\ShippingOrderManagerInterface
   */
  protected $shippingOrderManager;

  /**
   * Constructs a new ShipmentOrderProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_shipping\ShippingOrderManagerInterface $shipping_order_manager
   *   The shipping order manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ShippingOrderManagerInterface $shipping_order_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->shippingOrderManager = $shipping_order_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    if (!$order->hasField('shipments') || $order->get('shipments')->isEmpty()) {
      return;
    }

    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface[] $shipments */
    $shipments = $order->get('shipments')->referencedEntities();
    if (!$shipments) {
      // The referencedEntities() method may return an empty array even if the
      // entity reference field is not empty if the referenced entity does not
      // exist.
      // @see https://www.drupal.org/project/drupal/issues/2820574
      return;
    }

    if ($this->shouldRepack($order, $shipments)) {
      $shipping_profile = $this->shippingOrderManager->getProfile($order);
      // If the shipping profile does not exist, delete all shipments.
      if (!$shipping_profile) {
        $shipment_storage = $this->entityTypeManager->getStorage('commerce_shipment');
        $shipment_storage->delete($shipments);
        return;
      }
      $shipments = $this->shippingOrderManager->pack($order);
      $order->set('shipments', $shipments);
    }

    $single_shipment = count($shipments) === 1;
    foreach ($shipments as $shipment) {
      // Shipments without an amount are incomplete / unrated.
      if ($amount = $shipment->getAmount()) {
        $order->addAdjustment(new Adjustment([
          'type' => 'shipping',
          'label' => $single_shipment ? t('Shipping') : $shipment->getTitle(),
          'amount' => $amount,
          'source_id' => $shipment->id(),
        ]));
      }
    }
  }

  /**
   * Determines whether the given order's shipments should be repacked.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface[] $shipments
   *   The shipments.
   *
   * @return bool
   *   TRUE if the order should be repacked, FALSE otherwise.
   */
  protected function shouldRepack(OrderInterface $order, array $shipments) {
    // Skip repacking if there's at least one shipment that was created outside
    // of the packing process (via the admin UI, for example).
    foreach ($shipments as $shipment) {
      if (!$shipment->getData('owned_by_packer')) {
        return FALSE;
      }
    }
    // Ideally repacking would happen only if the order items changed.
    // However, it is not possible to detect order item quantity changes,
    // because the order items are saved before the order itself.
    // Therefore, repacking runs on every refresh, but as a minimal
    // optimization, this processor ignores refreshes caused by moving
    // through checkout, unless an order item was added/removed along the way.
    if (isset($order->original) && $order->hasField('checkout_step')) {
      $previous_step = $order->original->checkout_step->value;
      $current_step = $order->checkout_step->value;
      $previous_order_item_ids = array_map(function ($value) {
        return $value['target_id'];
      }, $order->original->get('order_items')->getValue());
      $current_order_item_ids = array_map(function ($value) {
        return $value['target_id'];
      }, $order->get('order_items')->getValue());
      if ($previous_step != $current_step && $previous_order_item_ids == $current_order_item_ids) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
