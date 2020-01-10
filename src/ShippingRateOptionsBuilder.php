<?php

namespace Drupal\commerce_shipping;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

class ShippingRateOptionsBuilder implements ShippingRateOptionsBuilderInterface {

  use StringTranslationTrait;

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * The shipment manager.
   *
   * @var \Drupal\commerce_shipping\ShipmentManagerInterface
   */
  protected $shipmentManager;

  /**
   * Constructs a new ShippingRateOptionsBuilder object.
   *
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   * @param \Drupal\commerce_shipping\ShipmentManagerInterface $shipment_manager
   *   The shipment manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(CurrencyFormatterInterface $currency_formatter, ShipmentManagerInterface $shipment_manager, TranslationInterface $string_translation) {
    $this->currencyFormatter = $currency_formatter;
    $this->shipmentManager = $shipment_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptions(ShipmentInterface $shipment) {
    $options = [];
    $shipping_rates = $this->shipmentManager->calculateRates($shipment);
    foreach ($shipping_rates as $rate_id => $shipping_rate) {
      $service = $shipping_rate->getService();
      $amount = $shipping_rate->getAmount();
      $option_label = $this->t('@service: @amount', [
        '@service' => $service->getLabel(),
        '@amount' => $this->currencyFormatter->format($amount->getNumber(), $amount->getCurrencyCode()),
      ]);
      list($shipping_method_id, $shipping_rate_id) = explode('--', $rate_id);
      $options[$rate_id] = new ShippingRateOption([
        'id' => $rate_id,
        'label' => $option_label,
        'shipping_method_id' => $shipping_method_id,
        'shipping_rate' => $shipping_rate,
      ]);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function selectDefaultOption(ShipmentInterface $shipment, array $options) {
    $default_option_id = NULL;
    if (!empty($shipment->getShippingMethodId()) && !empty($shipment->getShippingService())) {
      $default_option_id = $shipment->getShippingMethodId() . '--' . $shipment->getShippingService();
    }
    // Returns the default option if the applied rate is no longer available
    // or if none was selected.
    if (!$default_option_id || !isset($options[$default_option_id])) {
      $option_ids = array_keys($options);
      $default_option_id = reset($option_ids);
    }

    return $options[$default_option_id];
  }

}
