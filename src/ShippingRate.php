<?php

namespace Drupal\commerce_shipping;

use Drupal\commerce_price\Price;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Represents a shipping rate.
 */
final class ShippingRate {

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The shipping method ID.
   *
   * @var string
   */
  protected $shippingMethodId;

  /**
   * The shipping service.
   *
   * @var \Drupal\commerce_shipping\ShippingService
   */
  protected $service;

  /**
   * The amount.
   *
   * @var \Drupal\commerce_price\Price
   */
  protected $amount;

  /**
   * The delivery date.
   *
   * @var \Drupal\Core\Datetime\DrupalDateTime
   */
  protected $deliveryDate;

  /**
   * The delivery terms.
   *
   * @var string
   */
  protected $deliveryTerms;

  /**
   * Constructs a new ShippingRate object.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['shipping_method_id', 'service', 'amount'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property %s.', $required_property));
      }
    }
    if (!$definition['service'] instanceof ShippingService) {
      throw new \InvalidArgumentException(sprintf('Property "service" should be an instance of %s.', ShippingService::class));
    }
    if (!$definition['amount'] instanceof Price) {
      throw new \InvalidArgumentException(sprintf('Property "amount" should be an instance of %s.', Price::class));
    }
    // The ID is not required because most shipping methods generate one
    // rate per service, and use the service ID when purchasing labels.
    if (empty($definition['id'])) {
      $shipping_method_id = $definition['shipping_method_id'];
      $service_id = $definition['service']->getId();
      $definition['id'] = $shipping_method_id . '--' . $service_id;
    }

    $this->id = $definition['id'];
    $this->shippingMethodId = $definition['shipping_method_id'];
    $this->service = $definition['service'];
    $this->amount = $definition['amount'];
    $this->deliveryDate = $definition['delivery_date'] ?? NULL;
    $this->deliveryTerms = $definition['delivery_terms'] ?? NULL;
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
   * Gets the shipping method ID.
   *
   * @return string
   *   The shipping method ID.
   */
  public function getShippingMethodId() : string {
    return $this->shippingMethodId;
  }

  /**
   * Gets the shipping service.
   *
   * The shipping service label is meant to be displayed when presenting rates
   * for selection.
   *
   * @return \Drupal\commerce_shipping\ShippingService
   *   The shipping service.
   */
  public function getService() : ShippingService {
    return $this->service;
  }

  /**
   * Gets the amount.
   *
   * @return \Drupal\commerce_price\Price
   *   The amount.
   */
  public function getAmount() : Price {
    return $this->amount;
  }

  /**
   * Sets the amount.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The amount.
   *
   * @return $this
   */
  public function setAmount(Price $amount) {
    $this->amount = $amount;
    return $this;
  }

  /**
   * Gets the delivery date, if known.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The delivery date, or NULL.
   */
  public function getDeliveryDate() {
    return $this->deliveryDate;
  }

  /**
   * Sets the delivery date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $delivery_date
   *   The delivery date.
   *
   * @return $this
   */
  public function setDeliveryDate(DrupalDateTime $delivery_date) {
    $this->deliveryDate = $delivery_date;
    return $this;
  }

  /**
   * Gets the delivery terms, if known.
   *
   * Example: "Delivery in 1 to 3 business days."
   * Can be displayed to the end-user, if no translation is required.
   *
   * @return string|null
   *   The delivery terms, or NULL.
   */
  public function getDeliveryTerms() {
    return $this->deliveryTerms;
  }

  /**
   * Sets the delivery terms.
   *
   * @param string $delivery_terms
   *   The delivery terms.
   *
   * @return $this
   */
  public function setDeliveryTerms(string $delivery_terms) {
    $this->deliveryTerms = $delivery_terms;
    return $this;
  }

  /**
   * Gets the array representation of the shipping rate.
   *
   * @return array
   *   The array representation of the shipping rate.
   */
  public function toArray() : array {
    return [
      'id' => $this->id,
      'shipping_method_id' => $this->shippingMethodId,
      'service' => $this->service,
      'amount' => $this->amount,
      'delivery_date' => $this->deliveryDate,
      'delivery_terms' => $this->deliveryTerms,
    ];
  }

}
