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
   * Constructs a new ShippingRate instance.
   *
   * @param string $id
   *   The ID.
   * @param \Drupal\commerce_shipping\ShippingService $service
   *   The shipping service.
   * @param \Drupal\commerce_price\Price $amount
   *   The amount.
   * @param \Drupal\Core\Datetime\DrupalDateTime $delivery_date
   *   The delivery date.
   * @param string $delivery_terms
   *   The delivery terms.
   */
  public function __construct($id, ShippingService $service, Price $amount, DrupalDateTime $delivery_date = NULL, $delivery_terms = NULL) {
    $this->id = $id;
    $this->service = $service;
    $this->amount = $amount;
    $this->deliveryDate = $delivery_date;
    $this->deliveryTerms = $delivery_terms;
  }

  /**
   * Creates a new shipping rate from the given array.
   *
   * @param array $shipping_rate
   *   The shipping rate array, with the "id", "service" and "amount" keys.
   *
   * @return static
   */
  public static function fromArray(array $shipping_rate) {
    if (!isset($shipping_rate['id'], $shipping_rate['service'], $shipping_rate['amount'])) {
      throw new \InvalidArgumentException('ShippingRate::fromArray() called with a malformed array.');
    }
    $shipping_rate += [
      'delivery_date' => NULL,
      'delivery_terms' => NULL,
    ];
    return new static($shipping_rate['id'], $shipping_rate['service'], $shipping_rate['amount'], $shipping_rate['delivery_date'], $shipping_rate['delivery_terms']);
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
   * Gets the delivery date, if known.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|null
   *   The delivery date, or NULL.
   */
  public function getDeliveryDate() {
    return $this->deliveryDate;
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
   * Gets the array representation of the shipping rate.
   *
   * @return array
   *   The array representation of the shipping rate.
   */
  public function toArray() : array {
    return [
      'id' => $this->id,
      'service' => $this->service,
      'amount' => $this->amount,
      'delivery_date' => $this->deliveryDate,
      'delivery_terms' => $this->deliveryTerms,
    ];
  }

}
