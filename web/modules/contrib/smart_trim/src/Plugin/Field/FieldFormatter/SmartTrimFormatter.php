<?php

namespace Drupal\smart_trim\Plugin\Field\FieldFormatter;

 use Drupal\Core\Field\FieldDefinitionInterface;
 use Drupal\Core\Field\FieldItemListInterface;
 use Drupal\Core\Field\FormatterBase;
 use Drupal\Core\Form\FormStateInterface;
 use Drupal\Core\Link;
 use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
 use Drupal\Component\Utility\Unicode;
 use Drupal\smart_trim\TruncateHTML;
 use Symfony\Component\DependencyInjection\ContainerInterface;
 use Drupal\Core\Utility\Token;

/**
 * Plugin implementation of the 'smart_trim' formatter.
 *
 * @FieldFormatter(
 *   id = "smart_trim",
 *   label = @Translation("Smart trimmed"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string",
 *     "string_long"
 *   },
 *   settings = {
 *     "trim_length" = "300",
 *     "trim_type" = "chars",
 *     "trim_suffix" = "...",
 *     "more_link" = FALSE,
 *     "more_text" = "Read more",
 *     "summary_handler" = "full",
 *     "trim_options" = ""
 *   }
 * )
 */
class SmartTrimFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The truncate HTML service.
   *
   * @var \Drupal\smart_trim\TruncateHTML
   */
  protected $truncateHtml;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
   protected $token;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a FormatterBase object.
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
   *   Any third party settings.
   * @param \Drupal\smart_trim\TruncateHTML $truncate_html
   *   The truncate HTML service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, TruncateHTML $truncate_html, Token $token) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->truncateHtml = $truncate_html;
    $this->token = $token;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('smart_trim.truncate_html'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'trim_length' => '600',
      'trim_type' => 'chars',
      'trim_suffix' => '',
      'wrap_output' => 0,
      'wrap_class' => 'trimmed',
      'more_link' => 0,
      'more_class' => 'more-link',
      'more_text' => 'More',
      'more_aria_label' => 'Read more about [node:title]',
      'summary_handler' => 'full',
      'trim_options' => [],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $field_name = $this->fieldDefinition->getFieldStorageDefinition()->getName();

    $element['trim_length'] = [
      '#title' => $this->t('Trim length'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->getSetting('trim_length'),
      '#min' => 0,
      '#required' => TRUE,
    ];

    $element['trim_type'] = [
      '#title' => $this->t('Trim units'),
      '#type' => 'select',
      '#options' => [
        'chars' => $this->t("Characters"),
        'words' => $this->t("Words"),
      ],
      '#default_value' => $this->getSetting('trim_type'),
    ];

    $element['trim_suffix'] = [
      '#title' => $this->t('Suffix'),
      '#type' => 'textfield',
      '#size' => 10,
      '#default_value' => $this->getSetting('trim_suffix'),
    ];

    $element['wrap_output'] = [
      '#title' => $this->t('Wrap trimmed content?'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('wrap_output'),
      '#description' => $this->t('Adds a wrapper div to trimmed content.'),
    ];

    $element['wrap_class'] = [
      '#title' => $this->t('Wrapped content class.'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('wrap_class'),
      '#description' => $this->t('If wrapping, define the class name here.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][wrap_output]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['more_link'] = [
      '#title' => $this->t('Display more link?'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('more_link'),
      '#description' => $this->t('Displays a link to the entity (if one exists)'),
    ];

    $element['more_text'] = [
      '#title' => $this->t('More link text'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('more_text'),
      '#description' => $this->t('If displaying more link, enter the text for the link. This field supports tokens.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][more_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['more_aria_label'] = [
      '#title' => $this->t('More link aria-label'),
      '#type' => 'textfield',
      '#size' => 30,
      '#default_value' => $this->getSetting('more_aria_label'),
      '#description' => $this->t('If displaying more link, provide additional context for screen-reader users. In most cases, the aria-label value will be announced instead of the link text. This field supports tokens.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[body][settings_edit_form][settings][more_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['token_browser'] = [
      '#type' => 'item',
      '#theme' => 'token_tree_link',
      '#token_types' => [$this->fieldDefinition->getTargetEntityTypeId()],
      '#states' => [
        'visible' => [
          ':input[name="fields[body][settings_edit_form][settings][more_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['more_class'] = [
      '#title' => $this->t('More link class'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('more_class'),
      '#description' => $this->t('If displaying more link, add a custom class for formatting.'),
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][more_link]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    if ($this->fieldDefinition->getType() == 'text_with_summary') {
      $element['summary_handler'] = [
        '#title' => $this->t('Summary'),
        '#type' => 'select',
        '#options' => [
          'full' => $this->t("Use summary if present, and do not trim"),
          'trim' => $this->t("Use summary if present, honor trim settings"),
          'ignore' => $this->t("Do not use summary"),
        ],
        '#default_value' => $this->getSetting('summary_handler'),
      ];
    }

    $trim_options_value = $this->getSetting('trim_options');
    $element['trim_options'] = [
      '#title' => $this->t('Additional options'),
      '#type' => 'checkboxes',
      '#options' => [
        'text' => $this->t('Strip HTML'),
        'trim_zero' => $this->t('Honor a zero trim length'),
        'replace_tokens' => $this->t('Replace tokens before trimming'),
      ],
      '#default_value' => empty($trim_options_value) ? [] : array_keys(array_filter($trim_options_value)),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $type = $this->t('words');
    if ($this->getSetting('trim_type') == 'chars') {
      $type = $this->t('characters');
    }
    $trim_string = $this->getSetting('trim_length') . ' ' . $type;

    if (mb_strlen((trim($this->getSetting('trim_suffix'))))) {
      $trim_string .= " " . $this->t("with suffix");
    }
    if ($this->getSetting('more_link')) {
      $trim_string .= ", " . $this->t("with more link");
    }
    $summary[] = $trim_string;

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $element = [];
    $setting_trim_options = $this->getSetting('trim_options');
    $settings_summary_handler = $this->getSetting('summary_handler');
    $entity = $items->getEntity();
    $tokenData = [$entity->getEntityTypeId() => $entity];

    foreach ($items as $delta => $item) {
      if ($settings_summary_handler != 'ignore' && !empty($item->summary)) {
        $output = $item->summary;
      }
      else {
        $output = $item->value;
      }

      // Process additional options (currently only HTML on/off).
      if (!empty($setting_trim_options)) {
        // Allow a zero length trim.
        if (!empty($setting_trim_options['trim_zero']) && $this->getSetting('trim_length') == 0) {
          // If the summary is empty, trim to zero length.
          if (empty($item->summary)) {
            $output = '';
          }
          elseif ($settings_summary_handler != 'full') {
            $output = '';
          }
        }

        // Replace tokens before trimming.
        if (!empty($setting_trim_options['replace_tokens'])) {
          $output = $this->token->replace($output, $tokenData, [
            'langcode' => $langcode
          ]);
        }


        if (!empty($setting_trim_options['text'])) {
          // Strip caption.
          $output = preg_replace('/<figcaption[^>]*>.*?<\/figcaption>/is', ' ', $output);

          // Strip script.
          $output = preg_replace('/<script[^>]*>.*?<\/script>/is', ' ', $output);

          // Strip style.
          $output = preg_replace('/<style[^>]*>.*?<\/style>/is', ' ', $output);

          // Strip tags.
          $output = strip_tags($output);

          // Strip out line breaks.
          $output = preg_replace('/\n|\r|\t/m', ' ', $output);

          // Strip out non-breaking spaces.
          $output = str_replace('&nbsp;', ' ', $output);
          $output = str_replace("\xc2\xa0", ' ', $output);

          // Strip out extra spaces.
          $output = trim(preg_replace('/\s\s+/', ' ', $output));
        }
      }

      // Make the trim, provided we're not showing a full summary.
      if ($this->getSetting('summary_handler') != 'full' || empty($item->summary)) {
        $length = $this->getSetting('trim_length');
        $ellipse = $this->getSetting('trim_suffix');
        if ($this->getSetting('trim_type') == 'words') {
          $output = $this->truncateHtml->truncateWords($output, $length, $ellipse);
        }
        else {
          $output = $this->truncateHtml->truncateChars($output, $length, $ellipse);
        }
      }
      $element[$delta] = [
        '#type' => 'processed_text',
        '#text' => $output,
        '#format' => $item->format,
      ];

      // Wrap content in container div.
      if ($this->getSetting('wrap_output')) {
        $element[$delta]['#prefix'] = '<div class="' . $this->getSetting('wrap_class') . '">';
        $element[$delta]['#suffix'] = '</div>';
      }

      // Add the link, if there is one!
      // The entity must have an id already. Content entities usually get their
      // IDs by saving them. In some cases, eg: Inline Entity Form preview there
      // is no ID until everything is saved.
      // https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Entity!Entity.php/function/Entity%3A%3AtoUrl/8.2.x
      if ($this->getSetting('more_link') && $entity->id() && $entity->hasLinkTemplate('canonical')) {
        // But wait! Don't add a more link if the field ends in <!--break-->.
        if (strpos(strrev($output), strrev('<!--break-->')) !== 0) {
          $more = $this->t($this->getSetting('more_text'));
          $this->token->replace($more, $tokenData, [
            'langcode' => $langcode
          ]);
          $class = $this->getSetting('more_class');

          // Allow other modules to modify the read more link before it's created.
          \Drupal::moduleHandler()->invokeAll('smart_trim_link_modify', array($entity, &$more, &$uri));
          $project_link = $entity->toLink($more)->toRenderable();
          $project_link['#attributes'] = [
            'class' => [
              $class,
            ],
          ];
          // Ensure we don't create an empty aria-label attribute.
          $aria_label = $this->t($this->getSetting('more_aria_label'));
          if ($aria_label) {
            $project_link['#attributes']['aria-label'] = $this->token->replace($aria_label, $tokenData, [
              'langcode' => $langcode
            ]);
          }
          $project_link['#prefix'] = '<div class="' . $class . '">';
          $project_link['#suffix'] = '</div>';
          $element[$delta]['more_link'] = $project_link;
        }
      }
    }
    return $element;
  }

}
