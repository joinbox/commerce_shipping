<?php

namespace Drupal\Tests\commerce_shipping\Unit;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\commerce_shipping\ShippingRate
 * @group commerce_shipping
 */
class ShippingRateTest extends UnitTestCase {

  /**
   * The shipping rate.
   *
   * @var \Drupal\commerce_shipping\ShippingRate
   */
  protected $rate;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests the constructor and definition checks.
   *
   * @covers ::__construct
   *
   * @dataProvider invalidDefinitionProvider
   */
  public function testInvalidDefinition($definition, $message) {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    new ShippingRate($definition);
  }

  /**
   * Invalid constructor definitions.
   *
   * @return array
   *   The definitions.
   */
  public function invalidDefinitionProvider() {
    return [
      [[], 'Missing required property shipping_method_id'],
      [['shipping_method_id' => 'standard'], 'Missing required property service'],
      [
        [
          'shipping_method_id' => 'standard',
          'service' => new ShippingService('test', 'Test'),
        ],
        'Missing required property amount',
      ],
      [
        [
          'shipping_method_id' => 'standard',
          'service' => 'Test',
          'amount' => '10 USD',
        ],
        sprintf('Property "service" should be an instance of %s.', ShippingService::class),
      ],
      [
        [
          'shipping_method_id' => 'standard',
          'service' => new ShippingService('test', 'Test'),
          'amount' => '10 USD',
        ],
        sprintf('Property "amount" should be an instance of %s.', Price::class),
      ],
    ];
  }

  /**
   * @covers ::getId
   * @covers ::getShippingMethodId
   * @covers ::getService
   * @covers ::getAmount
   * @covers ::getDeliveryDate
   * @covers ::getDeliveryTerms
   * @covers ::toArray
   */
  public function testGetters() {
    $definition = [
      'id' => '717c2f9',
      'shipping_method_id' => 'standard',
      'service' => new ShippingService('test', 'Test'),
      'amount' => new Price('10.00', 'USD'),
      'delivery_date' => new DrupalDateTime('2016-11-24', 'UTC', ['langcode' => 'en']),
      'delivery_terms' => 'Arrives right away',
    ];

    $shipping_rate = new ShippingRate($definition);
    $this->assertEquals($definition['id'], $shipping_rate->getId());
    $this->assertEquals($definition['shipping_method_id'], $shipping_rate->getShippingMethodId());
    $this->assertEquals($definition['service'], $shipping_rate->getService());
    $this->assertEquals($definition['amount'], $shipping_rate->getAmount());
    $this->assertEquals($definition['delivery_date'], $shipping_rate->getDeliveryDate());
    $this->assertEquals($definition['delivery_terms'], $shipping_rate->getDeliveryTerms());
    $this->assertEquals($definition, $shipping_rate->toArray());
  }

  /**
   * @covers ::getId
   */
  public function testDefaultId() {
    $definition = [
      'shipping_method_id' => 'standard',
      'service' => new ShippingService('test', 'Test'),
      'amount' => new Price('10.00', 'USD'),
    ];

    $shipping_rate = new ShippingRate($definition);
    $this->assertEquals('standard--test', $shipping_rate->getId());
  }

}
