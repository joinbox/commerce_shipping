<?php

namespace Drupal\commerce_shipping;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\profile\Entity\ProfileInterface;

interface ShippingOrderManagerInterface {

  /**
   * Creates a shipping profile for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param array $values
   *   (optional) An array of field values to set on the profile.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   A shipping profile.
   */
  public function createProfile(OrderInterface $order, array $values = []);

  /**
   * Gets the shipping profile for the given order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The shipping profile, NULL if none found.
   */
  public function getProfile(OrderInterface $order);

  /**
   * Determines if the order is shippable.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   Returns whether the order is shippable.
   */
  public function isShippable(OrderInterface $order);

  /**
   * Packs the given order and return saved shipments.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The shipping profile.
   *
   * @return array
   *   An array with the populated shipments, as returned by the packer manager.
   */
  public function pack(OrderInterface $order, ProfileInterface $profile = NULL);

}
