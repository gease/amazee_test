<?php


namespace Drupal\arithmetic\Plugin\Field\FieldFormatter;


use Drupal\arithmetic\ArithmeticException;
use Drupal\arithmetic\CalculatorInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the arithmetic formatter.
 *
 * @FieldFormatter(
 *   id = "arithmetic",
 *   label = @Translation("Calculated value"),
 *   field_types = {
 *     "string",
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class ArithmeticFormatter extends FormatterBase implements ContainerFactoryPluginInterface{

  protected $calculator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('arithmetic.calculator'),
      $container->get('logger.factory')
    );
  }

  /**
   * Constructs an ArithmeticFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\arithmetic\CalculatorInterface $calculator
   *   Parser for arithmetical expressions.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, CalculatorInterface $calculator, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->calculator = $calculator;
    $this->logger = $logger_factory->get('widget');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();
    $settings['notation'] = 'infix';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['notation'] = [
      '#type' => 'select',
      '#options' => [
        'infix' => t('Infix'),
        'postfix' => t('Postfix'),
      ],
      '#default_value' => $this->getSetting('notation'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      try {
        if ($this->getSetting('notation') == 'infix') {
          $markup = $this->calculator->calculateInfix($item->value);
        }
        elseif ($this->getSetting('notation') == 'postfix') {
          $markup = $this->calculator->calculatePostfix($item->value);
        }
        else {
          throw new \Exception('No notation defined');
        }
      }
      catch (ArithmeticException $e) {
        $markup = t('Malformed expression.');
        $this->logger->error($e->getMessage());
      }
      $elements[$delta] = [
        '#theme' => 'arithmetic_infix',
        '#source' => $item->value,
        '#result' => $markup,
      ];
    }

    return $elements;
  }

}
