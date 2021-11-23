<?php

namespace Drupal\permissions_by_term\Service;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\permissions_by_term\Factory\NodeAccessRecordFactory;
use Drupal\permissions_by_term\Model\NodeAccessRecordModel;
use Drupal\user\Entity\User;


/**
 * Class NodeAccess
 *
 * @package Drupal\permissions_by_term
 */
class NodeAccess {

  /**
   * @var int $uniqueGid
   */
  private $uniqueGid = 0;

  /**
   * @var AccessStorage $accessStorage
   */
  private $accessStorage;

  /**
   * @var User $userEntityStorage
   */
  private $userEntityStorage;

  /**
   * @var Node $node
   */
  private $node;

  /**
   * @var EntityTypeManagerInterface $entityTypeManager
   */
  private $entityTypeManager;

  /**
   * @var AccessCheck $accessCheck
   */
  private $accessCheck;

  /**
   * @var int $loadedUid
   */
  private $loadedUid;

  /**
   * @var User $userInstance
   */
  private $userInstance;

  /**
   * The database connection.
   *
   * @var Connection
   */
  private $database;

  /**
   * NodeAccess constructor.
   *
   * @param AccessStorage           $accessStorage
   * @param NodeAccessRecordFactory $nodeAccessRecordFactory
   * @param EntityTypeManagerInterface           $entityTypeManager
   * @param AccessCheck             $accessCheck
   * @param Connection              $database
   */
  public function __construct(
    AccessStorage $accessStorage,
    NodeAccessRecordFactory $nodeAccessRecordFactory,
    EntityTypeManagerInterface $entityTypeManager,
    AccessCheck $accessCheck,
    Connection $database
  ) {
    $this->accessStorage = $accessStorage;
    $this->nodeAccessRecordFactory = $nodeAccessRecordFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->userEntityStorage = $this->entityTypeManager->getStorage('user');
    $this->node = $this->entityTypeManager->getStorage('node');
    $this->accessCheck = $accessCheck;
    $this->database = $database;
  }

  /**
   * @return NodeAccessRecordModel
   */
  public function createGrant($nid, $gid) {
    return $this->nodeAccessRecordFactory->create(
      AccessStorage::NODE_ACCESS_REALM,
      $gid,
      $nid,
      $this->accessStorage->getLangCode($nid),
      0,
      0
    );
  }

  /**
   * @return int
   */
  public function getUniqueGid() {
    return $this->uniqueGid;
  }

  /**
   * @param int $uniqueGid
   */
  public function setUniqueGid($uniqueGid) {
    $this->uniqueGid = $uniqueGid;
  }

  public function canUserBypassNodeAccess($uid) {
    $user = $this->getUserInstance($uid);
    if ($user->hasPermission('bypass node access')) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @param $uid
   * @param $nodeType
   * @param $nid
   *
   * @return bool
   */
  public function canUserDeleteNode($uid, $nodeType, $nid) {
    $user = $this->getUserInstance($uid);
    if ($user->hasPermission('delete any ' . $nodeType . ' content')) {
      return TRUE;
    }

    if ($this->isNodeOwner($nid, $uid) && $this->canDeleteOwnNode($uid, $nodeType)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @param $nid
   * @param $uid
   *
   * @return bool
   */
  public function isNodeOwner($nid, $uid) {
    $node = $this->node->load($nid);
    if ((int)$node->getOwnerId() == (int)$uid) {
      return TRUE;
    }

    return FALSE;
  }

  public function getNidsForAccessRebuild(): array {
//    We do not use taxonomy_index table here. The taxonomy_index table is
//    populated only, if an node is published. PbT has fetched all term
//    id to node id relations via this table. That's wrong! Because Permission
//    by Term is managing also permissions for unpublished nodes.
    return $this->database->select('node', 'n')
      ->fields('n', ['nid'])
      ->execute()
      ->fetchCol();
  }

  private function canUpdateOwnNode($uid, $nodeType) {
    $user = $this->getUserInstance($uid);
    if ($user->hasPermission('edit own ' . $nodeType . ' content')) {
      return 1;
    }

    return 0;
  }

  private function canDeleteOwnNode($uid, $nodeType) {
    $user = $this->getUserInstance($uid);
    if ($user->hasPermission('delete own ' . $nodeType . ' content')) {
      return 1;
    }

    return 0;
  }

  /**
   * @param $nid
   *
   * @return array
   */
  public function getGrantsByNid($nid) {
    $grants = [];
    foreach ($this->grants as $grant) {
      if ($grant->nid == $nid) {
        $grants[] = $grant;
      }
    }

    return $grants;
  }

  /**
   * @return int
   */
  public function getLoadedUid() {
    return $this->loadedUid;
  }

  /**
   * @param int $loadedUid
   */
  public function setLoadedUid($loadedUid) {
    $this->loadedUid = $loadedUid;
  }

  /**
   * @return User
   */
  public function getUserInstance($uid) {
    if ($this->getLoadedUid() !== $uid) {
      $user = $this->userEntityStorage->load($uid);
      $this->setUserInstance($user);
      return $user;
    }

    return $this->userInstance;
  }

  /**
   * @param User $userInstance
   */
  public function setUserInstance($userInstance) {
    $this->userInstance = $userInstance;
  }

  /**
   * @param int $nid
   *
   * @return bool
   */
  public function isAccessRecordExisting($nid) {
    $query = $this->database->select('node_access', 'na')
      ->fields('na', ['nid'])
      ->condition('na.nid', $nid)
      ->condition('na.realm', AccessStorage::NODE_ACCESS_REALM);

    $result = $query->execute()
      ->fetchCol();

    if (empty($result)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Rebuild node access for a single node.
   *
   * @param int $nid
   *   The node id for which node access records are to be recalculated.
   */
  public static function rebuildNodeAccessOne($nid) {
    // Delete existing grants for this node only.
    \Drupal::database()
      ->delete('node_access')
      ->condition('nid', $nid)
      ->execute();
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$nid]);
    $node = Node::load($nid);
    // To preserve database integrity, only write grants if the node
    // loads successfully.
    if (!empty($node)) {
      $grants = \Drupal::entityTypeManager()
        ->getAccessControlHandler('node')
        ->acquireGrants($node);
      \Drupal::service('node.grant_storage')->write($node, $grants);
    }

    return 'Processed node ' . $nid;
  }

  public function rebuildAccess(): void {
    $nids = $this->getNidsForAccessRebuild();

    if (count($nids) > 50) {
      $operations = array_map(function($id) {
        return ['Drupal\permissions_by_term\Service\NodeAccess::rebuildNodeAccessOne', [$id]];
      }, $nids);
      $batch = [
        'title' => t('Updating content access permissions'),
        'operations' => $operations,
        'finished' => 'Drupal\permissions_by_term\Service\NodeAccess::rebuildComplete',
      ];
      batch_set($batch);

      batch_process(\Drupal::service('path.current')->getPath());
    }
    else {
      // Try to allocate enough time to rebuild node grants
      Environment::setTimeLimit(240);

      // Rebuild newest nodes first so that recent content becomes available
      // quickly.
      rsort($nids);

      foreach ($nids as $nid) {
        $this->rebuildNodeAccessOne($nid);
      }
    }
  }

  /**
   * Rebuild is finished.
   */
  public static function rebuildComplete() {
    /**
     * @var \Drupal\permissions_by_term\Cache\CacheInvalidator $cacheInvalidator
     */
    $cacheInvalidator = \Drupal::service('permissions_by_term.cache_invalidator');
    $cacheInvalidator->invalidate();
  }

}
