<?php

namespace Drupal\commerce_shipping_test\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\FlatRate;
use Drupal\commerce_shipping\ShippingRate;

/**
 * Provides the Dynamic shipping method. Prices multiplied by weight of package.
 *
 * @CommerceShippingMethod(
 *   id = "dynamic",
 *   label = @Translation("Dynamic by package weight"),
 * )
 */
class DynamicRate extends FlatRate {

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    $amount = Price::fromArray($this->configuration['rate_amount']);
    $weight = $shipment->getPackageType()->getWeight()->convert('g')->getNumber() ?: 1;
    $rates = [];
    $rates[] = new ShippingRate([
      'shipping_method_id' => $this->parentEntity->id(),
      'service' => $this->services['default'],
      'amount' => $amount->multiply($weight),
    ]);

    return $rates;
  }

}
