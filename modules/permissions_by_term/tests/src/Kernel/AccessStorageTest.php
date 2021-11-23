<?php

namespace Drupal\Tests\permissions_by_term\Kernel;

use Drupal\Core\Form\FormStateInterface;
use Drupal\permissions_by_term\Service\TermHandler;

/**
 * @group permissions_by_term
 */
class AccessStorageTest extends PBTKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  private function mockFormState(string $langcode, array $accessOutput) {
    $formStateStub = $this->getMockBuilder(FormStateInterface::class)
      ->getMock();

    $map = [
      [
        'langcode',
        NULL,
        [
          ['value' => $langcode]
        ]
      ],
      [
        'access',
        NULL,
        $accessOutput
      ]
    ];
    $formStateStub->expects($this->any())
      ->method('getValue')
      ->will($this->returnValueMap($map));

    return $formStateStub;
  }

  public function testSaveMultipleLanguageCodes() {
    $_REQUEST = array (
      'access' =>
        array (
          'user' => 'admin (1), editor (45)',
          'role' =>
            array (
              'authenticated' => 'authenticated',
            ),
        ),
    );

    $formStateStub = $this->mockFormState('en', [
      'role' =>
        [
          'authenticated' => 'authenticated',
          'anonymous'     => 0,
          'administrator' => 0,
        ],
    ]);

    $this->assertEquals(array (
      'UserIdPermissionsToRemove' =>
        array (
        ),
      'UserIdPermissionsToAdd' =>
        array(
          '0' => '1',
          '1' => '45',
        ),
      'UserRolePermissionsToRemove' =>
        array (
        ),
      'aRoleIdPermissionsToAdd' =>
        array (
          0 => 'authenticated',
        ),
    ), $this->accessStorage->saveTermPermissions($formStateStub, 1));

    $formStateStub = $this->mockFormState('de', [
      'role' =>
        [
          'authenticated' => 'authenticated',
          'anonymous'     => 0,
          'administrator' => 0,
        ],
    ]);

    $this->assertEquals(array (
      'UserIdPermissionsToRemove' =>
        array (
        ),
      'UserIdPermissionsToAdd' =>
        array(
          '0' => '1',
          '1' => '45',
        ),
      'UserRolePermissionsToRemove' =>
        array (
        ),
      'aRoleIdPermissionsToAdd' =>
        array (
          0 => 'authenticated',
        ),
    ), $this->accessStorage->saveTermPermissions($formStateStub, 1));
  }

  public function testTidsByNidRetrieval() {
    $this->createRelationOneGrantedTerm();
    /**
     * @var TermHandler $termHandler
     */
    $termHandler = \Drupal::service('permissions_by_term.term_handler');

    self::assertCount(3, $termHandler->getTidsByNid('1'));
    self::assertNull($termHandler->getTidsByNid('99'));
  }

}
