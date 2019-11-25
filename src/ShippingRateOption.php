<?php

namespace Drupal\commerce_shipping;

/**
 * Represents a shipping rate option.
 *
 * @see \Drupal\commerce_shipping\ShippingRateOptionsBuilderInterface::buildOptions()
 */
final class ShippingRateOption {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The label.
   *
   * @var string
   */
  protected $label;

  /**
   * The shipping method ID.
   *
   * @var string
   */
  protected $shippingMethodId;

  /**
   * The shipping rate.
   *
   * @var \Drupal\commerce_shipping\ShippingRate
   */
  protected $shippingRate;

  /**
   * Constructs a new ShippingRateOption object.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['id', 'label', 'shipping_method_id', 'shipping_rate'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property "%s".', $required_property));
      }
    }

    $this->id = $definition['id'];
    $this->label = $definition['label'];
    $this->shippingMethodId = $definition['shipping_method_id'];
    $this->shippingRate = $definition['shipping_rate'];
  }

  /**
   * Gets the ID.
   *
   * @return string
   *   The ID.
   */
  public function getId() : string {
    return $this->id;
  }

  /**
   * Gets the label.
   *
   * @return string
   *   The label.
   */
  public function getLabel() : string {
    return $this->label;
  }

  /**
   * Gets the shipping method ID.
   *
   * @return string
   *   The shipping method ID.
   */
  public function getShippingMethodId() : string {
    return $this->shippingMethodId;
  }

  /**
   * Gets the shipping rate.
   *
   * @return \Drupal\commerce_shipping\ShippingRate
   *   The shipping rate.
   */
  public function getShippingRate() : ShippingRate {
    return $this->shippingRate;
  }

}
