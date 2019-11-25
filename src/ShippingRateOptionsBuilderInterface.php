<?php

namespace Drupal\commerce_shipping;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 * Builds shipping rate options for a shipment.
 */
interface ShippingRateOptionsBuilderInterface {

  /**
   * Builds the shipping rate options for the given shipment.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   *
   * @return \Drupal\commerce_shipping\ShippingRateOption[]
   *   The shipping rate options, keyed by option ID.
   */
  public function buildOptions(ShipmentInterface $shipment);

  /**
   * Selects the default shipping rate option for the given shipment.
   *
   * Priority:
   * 1) The shipping rate applied on the shipment.
   * 2) First defined option.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment
   *   The shipment.
   * @param array $options
   *   The options.
   *
   * @return \Drupal\commerce_shipping\ShippingRateOption
   *   The selected option.
   */
  public function selectDefaultOption(ShipmentInterface $shipment, array $options);

}
