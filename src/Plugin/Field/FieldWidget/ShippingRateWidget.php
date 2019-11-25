<?php

namespace Drupal\commerce_shipping\Plugin\Field\FieldWidget;

use Drupal\commerce_shipping\ShippingRateOption;
use Drupal\commerce_shipping\ShippingRateOptionsBuilderInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Plugin implementation of 'commerce_shipping_rate'.
 *
 * @FieldWidget(
 *   id = "commerce_shipping_rate",
 *   label = @Translation("Shipping rate"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class ShippingRateWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The shipping rate options builder.
   *
   * @var \Drupal\commerce_shipping\ShippingRateOptionsBuilderInterface
   */
  protected $shippingRateOptionsBuilder;

  /**
   * Constructs a new ShippingRateWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_shipping\ShippingRateOptionsBuilderInterface $shipping_rate_options_builder
   *   The shipping rate options builder.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, ShippingRateOptionsBuilderInterface $shipping_rate_options_builder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->shippingRateOptionsBuilder = $shipping_rate_options_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('commerce_shipping.rate_options_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
    $shipment = $items[$delta]->getEntity();
    $options = $this->shippingRateOptionsBuilder->buildOptions($shipment);

    if (!$options) {
      $element = [
        '#markup' => $this->t('There are no shipping rates available for this address.'),
      ];
      return $element;
    }
    $default_option = $this->shippingRateOptionsBuilder->selectDefaultOption($shipment, $options);
    $option_labels = array_map(function (ShippingRateOption $option) {
      return $option->getLabel();
    }, $options);
    $element['#type'] = 'radios';
    $element['#options'] = $option_labels;
    $element['#default_value'] = $default_option->getId();

    // Store relevant data for extractFormValues().
    foreach ($options as $option_id => $option) {
      $element[$option_id]['#shipping_method_id'] = $option->getShippingMethodId();
      $element[$option_id]['#shipping_rate'] = $option->getShippingRate();
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = array_merge($form['#parents'], [$field_name, 0]);
    $element = NestedArray::getValue($form, [$field_name, 'widget', 0]);
    $selected_value = NestedArray::getValue($form_state->getValues(), $parents, $key_exists);
    if ($selected_value) {
      $shipping_method_id = $element[$selected_value]['#shipping_method_id'];
      /** @var \Drupal\commerce_shipping\ShippingRate $shipping_rate */
      $shipping_rate = $element[$selected_value]['#shipping_rate'];
      /** @var \Drupal\commerce_shipping\Entity\ShipmentInterface $shipment */
      $shipment = $items[0]->getEntity();
      // @todo This should be done by selectRate() but the plugin doesn't
      // have access to the parent entity ID yet.
      $shipment->setShippingMethodId($shipping_method_id);

      $shipping_method_storage = $this->entityTypeManager->getStorage('commerce_shipping_method');
      /** @var \Drupal\commerce_shipping\Entity\ShippingMethodInterface $shipping_method */
      $shipping_method = $shipping_method_storage->load($shipping_method_id);
      $shipping_method_plugin = $shipping_method->getPlugin();
      if (empty($shipment->getPackageType())) {
        $shipment->setPackageType($shipping_method_plugin->getDefaultPackageType());
      }
      $shipping_method_plugin->selectRate($shipment, $shipping_rate);

      // Put delta mapping in $form_state, so that flagErrors() can use it.
      $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
      foreach ($items as $delta => $item) {
        $field_state['original_deltas'][$delta] = $delta;
      }
      static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'commerce_shipment' && $field_name == 'shipping_method';
  }

  /**
   * {@inheritdoc}
   */
  public function flagErrors(FieldItemListInterface $items, ConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    foreach ($violations as $offset => $violation) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      if ($violation->getCode() == NotNullConstraint::IS_NULL_ERROR) {
        // There are no setters on ConstraintValidation.
        $new = new ConstraintViolation(
          $this->t('A valid shipping method must be selected in order to check out.'),
          $violation->getMessageTemplate(),
          $violation->getParameters(),
          $violation->getRoot(),
          $violation->getPropertyPath(),
          $violation->getInvalidValue(),
          $violation->getPlural(),
          $violation->getCode(),
          new NotNullConstraint()
        );
        $violations->remove($offset);
        $violations->add($new);
      }
    }
    return parent::flagErrors($items, $violations, $form, $form_state);
  }

}
