<?php

namespace Drupal\Tests\unique_field_ajax\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * The base testing class for unique_field_ajax.
 *
 * @group unique_field_ajax
 */
class UniqueFieldAjaxBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'language',
    'language_test',
    'field_ui',
    'link',
    'unique_field_ajax',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The default content type name.
   *
   * @var string
   */
  protected $contentTypeName = 'node_unique_field_ajax';


  /**
   * The custom content type created.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The instance used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * The field types to test upon.
   *
   * @var \string[][]
   */
  protected $fieldTypes = [
    'link' => [
      'type' => 'link',
      'widget' => 'email_default',
      'value' => 'link',
      'effect' => 'uri',
      'settings' => [],
    ],
    'string' => [
      'type' => 'string',
      'widget' => 'string_textfield',
      'value' => 'string',
      'effect' => 'value',
      'settings' => [],
    ],
    'email' => [
      'type' => 'email',
      'widget' => 'email_default',
      'value' => 'email',
      'effect' => 'value',
      'settings' => [],
    ],
    'integer' => [
      'type' => 'integer',
      'widget' => 'number',
      'value' => 'integer',
      'effect' => 'value',
      'settings' => [],
    ],
    'decimal' => [
      'type' => 'decimal',
      'widget' => 'number',
      'value' => 'decimal',
      'effect' => 'value',
      'settings' => [],
    ],
  ];

  /**
   * Translation language options.
   *
   * @var string[]
   */
  protected $translationOptions = [
    'es' => 'spanish',
    'fr' => 'french',
    'de' => 'german',
  ];

  /**
   * Perform initial setup tasks that run before every test method.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp() {
    parent::setUp();
    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);
    $this->createCustomContentType();
  }

  /**
   * Create a new content type using the content type variable.
   */
  protected function createCustomContentType() {
    $this->contentType = $this->drupalCreateContentType(['type' => $this->contentTypeName]);
  }

  /**
   * Helper method to create a field to use.
   *
   * @param string $fieldType
   *   Type of field.
   * @param string $widgetType
   *   Type of field widget.
   * @param array $fieldConfigSettings
   *   Any extra field config settings.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createField(
    string $fieldType,
    string $widgetType,
    array $fieldConfigSettings = []
  ) {
    $field_name = $this->createRandomData();
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $fieldType,
    ]);
    $this->fieldStorage->save();

    $field_config = [
      'field_storage' => $this->fieldStorage,
      'bundle' => $this->contentTypeName,
      'label' => $field_name . '_label',
    ];
    if (!empty($fieldConfigSettings)) {
      $field_config['settings'] = $fieldConfigSettings;
    }
    $this->field = FieldConfig::create($field_config);
    $this->field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $display_repository->getFormDisplay('node', $this->contentTypeName)
      ->setComponent($field_name, [
        'type' => $widgetType,
      ])
      ->save();
    $display_repository->getViewDisplay('node', $this->contentTypeName, 'full')
      ->setComponent($field_name)
      ->save();
  }

  /**
   * Runs a test to see if a field can be saved.
   *
   * @param array $edit
   *   Edit data.
   * @param int|null $nid
   *   Node id.
   * @param bool $debug
   *   Adds debug information.
   *
   * @return int
   *   Saved/updated node id.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function itCanSaveField(
    array $edit,
    int $nid = NULL,
    bool $debug = FALSE
  ): int {
    $title = $edit['title[0][value]'];
    $method = $this->getSaveMethod($nid);
    $this->drupalPostForm($method, $edit, t('Save'));

    preg_match('|node/(\d+)|', $this->getUrl(), $match);

    if (!empty($match)) {
      $id = $match[1];
      if (!$nid) {
        $this->assertSession()->pageTextContains(t('@contentType @title has been created.',
            ['@title' => $title, '@contentType' => $this->contentTypeName])
        );
      }
      else {
        if ($debug) {
          var_dump($this->getSession()->getPage()->getHtml());
        }
        $this->assertSession()->pageTextContains(t('@contentType @title has been updated.',
            ['@title' => $title, '@contentType' => $this->contentTypeName])
        );
      }
      return (int) $id;
    }
    else {
      var_dump($this->getUrl());
      var_dump($this->getSession()->getPage()->getHtml());
      static::fail(t('Could not extract entity id from url'));
    }
    return -1;
  }

  /**
   * An Alias method for save field, requiring an nid.
   *
   * @param array $edit
   *   Edit data.
   * @param int $nid
   *   Node id.
   * @param bool $debug
   *   Adds debug information.
   *
   * @return int
   *   Saved/updated node id.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function itCanUpdateField(
    array $edit,
    int $nid,
    bool $debug = FALSE
  ): int {
    return $this->itCanSaveField($edit, $nid, $debug);
  }

  /**
   * Runs a test to see if a field cannot be saved.
   *
   * @param array $edit
   *   Edit data.
   * @param string|null $customMsg
   *   Custom save message.
   * @param string|null $nid
   *   Node id.
   * @param string|null $label
   *   Optional label name.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function itCannotSaveField(
    array $edit,
    string $customMsg = NULL,
    string $nid = NULL,
    string $label = NULL
  ) {
    $method = $this->getSaveMethod($nid);
    $label_name = $label ?: $this->field->label();

    $this->drupalPostForm($method, $edit, t('Save'));
    if ($customMsg) {
      $message = $customMsg;
    }
    else {
      $message = t('The field "@field" has to be unique.',
        ['@field' => $label_name]);
    }
    $this->assertSession()->pageTextContains($message);
  }

  /**
   * An Alias method for cannot updating field, requiring an nid.
   *
   * @param array $edit
   *   Edit data.
   * @param string $nid
   *   Node id.
   * @param string|null $customMsg
   *   Custom save message.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function itCannotEditField(
    array $edit,
    string $nid,
    string $customMsg = NULL
  ) {
    $this->itCannotSaveField($edit, $customMsg, $nid);
  }

  /**
   * Helper method to return the saving method of add or edit.
   *
   * @param string|null $id
   *   Node id.
   *
   * @return string
   *   Method path.
   */
  protected function getSaveMethod(string $id = NULL): string {
    return !$id ? 'node/add/' . $this->contentTypeName : 'node/' . $id . '/edit';
  }

  /**
   * Helper method to update third party field settings.
   *
   * @param string $key
   *   Third Party key.
   * @param string $value
   *   Third Party value.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateThirdPartyFieldSetting(string $key, string $value) {
    $this->field->setThirdPartySetting('unique_field_ajax', $key, $value);
    $this->field->save();
  }

  /**
   * Helper method to update third party entity settings.
   *
   * @param string $key
   *   Third Party key.
   * @param string $value
   *   Third Party value.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateThirdPartyEntitySetting(string $key, string $value) {
    $this->contentType->setThirdPartySetting('unique_field_ajax', $key, $value);
    $this->contentType->save();
  }

  /**
   * Helper method to create the basic update edit data.
   *
   * @param string|null $title
   *   Optional language title otherwise will be randomly generated.
   * @param string|null $body
   *   Optional language body otherwise will be randomly generated.
   *
   * @return string[]
   *   Edit data formatted for submit.
   */
  protected function createBasicUpdateData(string $title = NULL, string $body = NULL): array {
    $return = [];
    $return['title[0][value]'] = $title ?? $this->randomMachineName();
    $return['body[0][value]'] = $body ?? $this->randomMachineName();
    return $return;
  }

  /**
   * Helper method to create custom update edit data for fields.
   *
   * @param string $fieldName
   *   Field name.
   * @param string $value
   *   Field value.
   * @param string $effect
   *   Type of field.
   * @param string|null $language
   *   Optional language settings.
   * @param string|null $title
   *   Optional language title otherwise will be randomly generated.
   * @param string|null $body
   *   Optional language body otherwise will be randomly generated.
   *
   * @return string[]
   *   Edit data formatted for submit.
   */
  protected function createUpdateFieldData(string $fieldName, string $value, string $effect, string $language = NULL, string $title = NULL, string $body = NULL): array {
    $return = $this->createBasicUpdateData($title, $body);
    $return["{$fieldName}[0][{$effect}]"] = $this->createRandomData($value);
    if ($language) {
      $return['langcode[0][value]'] = $language;
    }
    return $return;
  }

  /**
   * Helper method to create random data.
   *
   * @param string $type
   *   Type of random data.
   *
   * @return false|string|string[]
   *   Random data.
   */
  protected function createRandomData(string $type = 'string') {
    $return = '';

    switch ($type) {
      case 'string':
        $return = mb_strtolower($this->randomMachineName());
        break;

      case 'sentence':
        $length = 200;
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $return = substr(str_shuffle(str_repeat($chars,
          ceil($length / strlen($chars)))), 1, $length);
        $return = wordwrap($return, rand(3, 10), ' ', TRUE);
        break;

      case 'link':
        $return = 'https://www.' . $this->createRandomData() . '.com/';
        break;

      case 'email':
        $return = $this->createRandomData() . '@' . $this->createRandomData() . '.com';
        break;

      case 'integer':
        try {
          $return = random_int(0, 9999);
        }
        catch (\Exception $e) {
          die('Could not generate random int');
        }
        break;

      case 'decimal':
        $min = 0;
        $max = 9999;
        $decimals = 2;
        $scale = pow(10, $decimals);
        $return = mt_rand($min * $scale, $max * $scale) / $scale;
        break;
    }

    return $return;
  }

}
