<?php

namespace Drupal\Tests\permissions_by_term\Kernel;

use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;


/**
 * Class AccessCheckTest
 *
 * @package Drupal\Tests\permissions_by_term\Kernel
 * @group permissions_by_term
 */
class NodeAccessRecordsTest extends PBTKernelTestBase
{

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  public function testCreateNone(): void {
    $node = Node::create([
      'type'  => 'page',
      'title' => 'test_title',
    ]);
    $node->save();
    self::assertEmpty($this->isNodeAccessRecordCreatedInPBTRealm($node->id()));

    NodeType::create([
      'type' => 'without_taxonomy_term_relation',
    ])->save();

    $node = Node::create([
      'type'  => 'without_taxonomy_term_relation',
      'title' => 'test_title',
    ]);
    $node->save();
    self::assertFalse($this->isNodeAccessRecordCreatedInPBTRealm($node->id()));

    $term = Term::create([
      'name' => 'term2',
      'vid'  => 'test',
    ]);
    $term->save();

    $node = Node::create([
      'type' => 'page',
      'title' => 'test_title',
      'field_tags' => [
        [
          'target_id' => $term->id()
        ]
      ],
    ]);
    $node->save();
    self::assertFalse($this->isNodeAccessRecordCreatedInPBTRealm($node->id()));
  }

  public function testCreateIfTermHasPermission(): void {
    $term = Term::create([
      'name' => 'term2',
      'vid'  => 'test',
    ]);
    $term->save();
    $this->accessStorage->addTermPermissionsByUserIds([99], $term->id());

    $node = Node::create([
      'type' => 'page',
      'title' => 'test_title',
      'field_tags' => [
        [
          'target_id' => $term->id()
        ]
      ],
    ]);
    $node->save();
    self::assertTrue($this->isNodeAccessRecordCreatedInPBTRealm($node->id()));

    \Drupal::configFactory()->getEditable('permissions_by_term.settings')->set('permission_mode', TRUE)->save(TRUE);
    $term = Term::create([
      'name' => 'term2',
      'vid'  => 'test',
    ]);
    $term->save();

    $node = Node::create([
      'type' => 'page',
      'title' => 'test_title',
      'field_tags' => [
        [
          'target_id' => $term->id()
        ]
      ],
    ]);
    $node->save();
    self::assertTrue($this->isNodeAccessRecordCreatedInPBTRealm($node->id()));
  }

  public function testCreateIfTermHasNoPermissionButPermissionModeIsOn(): void {
    \Drupal::configFactory()->getEditable('permissions_by_term.settings')->set('permission_mode', TRUE)->save(TRUE);
    $term = Term::create([
      'name' => 'term2',
      'vid'  => 'test',
    ]);
    $term->save();

    $node = Node::create([
      'type' => 'page',
      'title' => 'test_title',
      'field_tags' => [
        [
          'target_id' => $term->id()
        ]
      ],
    ]);
    $node->save();
    self::assertTrue($this->isNodeAccessRecordCreatedInPBTRealm($node->id()));
  }

  private function isNodeAccessRecordCreatedInPBTRealm(string $nid): bool {
    /**
     * @var Connection $database
     */
    $database = \Drupal::service('database');
    $nodeAccessRecords = $database->query('SELECT nid, realm FROM {node_access} WHERE nid = :nid AND realm = :realm', [
      'nid'   => $nid,
      'realm' => 'permissions_by_term'
    ])->fetchAll();

    if (!empty($nodeAccessRecords)) {
      return TRUE;
    }

    return FALSE;
  }

}
