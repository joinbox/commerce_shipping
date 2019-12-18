<?php

namespace Drupal\Tests\commerce_shipping\Kernel;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\Shipment;
use Drupal\commerce_shipping\Entity\ShippingMethod;
use Drupal\commerce_shipping\ShipmentItem;
use Drupal\physical\Weight;
use Drupal\profile\Entity\Profile;

/**
 * Tests the shipping rate options builder.
 *
 * @coversDefaultClass \Drupal\commerce_shipping\ShippingRateOptionsBuilder
 *
 * @group commerce_shipping
 */
class ShippingRateOptionsBuilderTest extends ShippingKernelTestBase {

  /**
   * The shipping rate options builder.
   *
   * @var \Drupal\commerce_shipping\ShippingRateOptionsBuilderInterface
   */
  protected $shippingRateOptionsBuilder;

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

    $this->shippingRateOptionsBuilder = $this->container->get('commerce_shipping.rate_options_builder');
    $user = $this->createUser(['mail' => $this->randomString() . '@example.com']);
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => $user->id(),
      'address' => [
        'country_code' => 'US',
        'administrative_area' => 'CA',
        'locality' => 'Mountain View',
        'postal_code' => '94043',
        'address_line1' => '1098 Alta Ave',
        'organization' => 'Google Inc.',
        'given_name' => 'John',
        'family_name' => 'Smith',
      ],
    ]);
    $profile->save();
    $profile = $this->reloadEntity($profile);
    $order_item = OrderItem::create([
      'type' => 'test',
      'quantity' => 1,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'default',
      'mail' => $user->getEmail(),
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
      'order_number' => '6',
      'billing_profile' => $profile,
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
      'shipping_method' => $shipping_method,
      'shipping_profile' => $profile,
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
   * Tests building options for all available rates.
   *
   * @covers ::buildOptions
   */
  public function testBuildOptions() {
    $options = $this->shippingRateOptionsBuilder->buildOptions($this->shipment);
    $this->assertNotEmpty($options);
    /** @var \Drupal\commerce_shipping\ShippingRateOption[] $options */
    $options = array_values($options);
    $this->assertCount(2, $options);

    // The second shipping method should be the first one returned, because of
    // the weight.
    $shipping_rate = $options[0]->getShippingRate();
    $this->assertEquals('2--default', $options[0]->getId());
    $this->assertEquals('Flat rate: $20.00', $options[0]->getLabel());
    $this->assertEquals('2', $options[0]->getShippingMethodId());
    $this->assertEquals(new Price('20.00', 'USD'), $shipping_rate->getAmount());

    $shipping_rate = $options[1]->getShippingRate();
    $this->assertEquals('1--default', $options[1]->getId());
    $this->assertEquals('Flat rate: $5.00', $options[1]->getLabel());
    $this->assertEquals('1', $options[1]->getShippingMethodId());
    $this->assertEquals(new Price('5.00', 'USD'), $shipping_rate->getAmount());
  }

  /**
   * Tests selecting the default option.
   *
   * @covers ::selectDefaultOption
   */
  public function testSelectDefaultOption() {
    $options = $this->shippingRateOptionsBuilder->buildOptions($this->shipment);

    // The selected shipping rate should be returned as the default option.
    $this->shipment->setShippingService('default');
    $default_option = $this->shippingRateOptionsBuilder->selectDefaultOption($this->shipment, $options);
    $this->assertEquals($options['1--default'], $default_option);
    $this->assertNotEquals(reset($options), $default_option);

    // The selected default option should be the first one (as a fallback).
    $this->shipment->set('shipping_method', NULL);
    $this->shipment->set('shipping_service', NULL);
    $default_option = $this->shippingRateOptionsBuilder->selectDefaultOption($this->shipment, $options);
    $this->assertEquals(reset($options), $default_option);
  }

  /**
   * Tests that the shipping rate is altered.
   */
  public function testEvent() {
    $options = $this->shippingRateOptionsBuilder->buildOptions($this->shipment);
    $this->assertCount(2, $options);
    $shipping_rate = $options['2--default']->getShippingRate();
    $this->assertEquals(new Price('20.00', 'USD'), $shipping_rate->getAmount());
    $shipping_rate = $options['1--default']->getShippingRate();
    $this->assertEquals(new Price('5.00', 'USD'), $shipping_rate->getAmount());

    $this->shipment->setData('alter_rate', TRUE);
    $options = $this->shippingRateOptionsBuilder->buildOptions($this->shipment);
    $this->assertCount(2, $options);
    $shipping_rate = $options['2--default']->getShippingRate();
    $this->assertEquals(new Price('40.00', 'USD'), $shipping_rate->getAmount());
    $shipping_rate = $options['1--default']->getShippingRate();
    $this->assertEquals(new Price('10.00', 'USD'), $shipping_rate->getAmount());
  }

}
