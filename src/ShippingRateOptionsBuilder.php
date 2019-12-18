<?php

namespace Drupal\commerce_shipping;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Event\ShippingEvents;
use Drupal\commerce_shipping\Event\ShippingRatesEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShippingRateOptionsBuilder implements ShippingRateOptionsBuilderInterface {

  use StringTranslationTrait;

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new ShippingRateOptionsBuilder object.
   *
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(CurrencyFormatterInterface $currency_formatter, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher, TranslationInterface $string_translation) {
    $this->currencyFormatter = $currency_formatter;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptions(ShipmentInterface $shipment) {
    /** @var \Drupal\commerce_shipping\ShippingMethodStorageInterface $shipping_method_storage */
    $shipping_method_storage = $this->entityTypeManager->getStorage('commerce_shipping_method');
    $shipping_methods = $shipping_method_storage->loadMultipleForShipment($shipment);
    $options = [];
    foreach ($shipping_methods as $shipping_method) {
      $shipping_method_plugin = $shipping_method->getPlugin();
      $shipping_rates = $shipping_method_plugin->calculateRates($shipment);
      // Allow the shipping rates to be altered.
      $event = new ShippingRatesEvent($shipping_rates, $shipping_method, $shipment);
      $this->eventDispatcher->dispatch(ShippingEvents::SHIPPING_RATES, $event);
      $shipping_rates = $event->getRates();

      foreach ($shipping_rates as $shipping_rate) {
        $service = $shipping_rate->getService();
        $amount = $shipping_rate->getAmount();

        $option_id = $shipping_method->id() . '--' . $service->getId();
        $option_label = $this->t('@service: @amount', [
          '@service' => $service->getLabel(),
          '@amount' => $this->currencyFormatter->format($amount->getNumber(), $amount->getCurrencyCode()),
        ]);
        $options[$option_id] = new ShippingRateOption([
          'id' => $option_id,
          'label' => $option_label,
          'shipping_method_id' => $shipping_method->id(),
          'shipping_rate' => $shipping_rate,
        ]);
      }
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
