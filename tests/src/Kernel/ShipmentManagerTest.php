<?php

namespace Drupal\Tests\commerce_shipping\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\Shipment;
use Drupal\commerce_shipping\Entity\ShippingMethod;
use Drupal\commerce_shipping\ShipmentItem;
use Drupal\physical\Weight;

/**
 * Tests the shipment manager.
 *
 * @coversDefaultClass \Drupal\commerce_shipping\ShipmentManager
 * @group commerce_shipping
 */
class ShipmentManagerTest extends ShippingKernelTestBase {

  /**
   * The shipment manager.
   *
   * @var \Drupal\commerce_shipping\ShipmentManagerInterface
   */
  protected $shipmentManager;

  /**
   * A sample shipment.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $shipment;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->shipmentManager = $this->container->get('commerce_shipping.shipment_manager');
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'order_number' => '6',
      'store_id' => $this->store->id(),
      'state' => 'completed',
      'order_items' => [$order_item],
    ]);
    $order->save();

    $shipping_method = ShippingMethod::create([
      'stores' => $this->store->id(),
      'name' => 'Example',
      'plugin' => [
        'target_plugin_id' => 'flat_rate',
        'target_plugin_configuration' => [
          'rate_label' => 'Flat rate',
          'rate_amount' => [
            'number' => '5',
            'currency_code' => 'USD',
          ],
        ],
      ],
      'status' => TRUE,
      'weight' => 1,
    ]);
    $shipping_method->save();

    $another_shipping_method = ShippingMethod::create([
      'stores' => $this->store->id(),
      'name' => 'Another shipping method',
      'plugin' => [
        'target_plugin_id' => 'flat_rate',
        'target_plugin_configuration' => [
          'rate_label' => 'Flat rate',
          'rate_amount' => [
            'number' => '20',
            'currency_code' => 'USD',
          ],
        ],
      ],
      'status' => TRUE,
      'weight' => 0,
    ]);
    $another_shipping_method->save();

    $shipment = Shipment::create([
      'type' => 'default',
      'order_id' => $order->id(),
      'title' => 'Shipment',
      'tracking_code' => 'ABC123',
      'items' => [
        new ShipmentItem([
          'order_item_id' => 1,
          'title' => 'T-shirt (red, large)',
          'quantity' => 2,
          'weight' => new Weight('40', 'kg'),
          'declared_value' => new Price('30', 'USD'),
        ]),
      ],
      'amount' => new Price('5', 'USD'),
      'state' => 'draft',
    ]);
    $shipment->save();
    $this->shipment = $this->reloadEntity($shipment);
  }

  /**
   * Tests calculating rates.
   *
   * @covers ::calculateRates
   */
  public function testCalculateRates() {
    $rates = $this->shipmentManager->calculateRates($this->shipment);
    $this->assertNotEmpty($rates);
    $rates = array_values($rates);
    $this->assertCount(2, $rates);
    list($first_shipping_rate, $second_shipping_rate) = $rates;
    $this->assertEquals(new Price('20.00', 'USD'), $first_shipping_rate->getAmount());
    $this->assertEquals(new Price('5.00', 'USD'), $second_shipping_rate->getAmount());
  }

}
