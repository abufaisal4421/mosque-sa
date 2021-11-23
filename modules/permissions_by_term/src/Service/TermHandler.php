<?php

namespace Drupal\permissions_by_term\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;


class TermHandler {

  /**
   * The database connection.
   *
   * @var Connection
   */
  private $database;

  /**
   * @var Term
   */
  private $term;

  /**
   * @var EntityFieldManager
   */
  private $entityFieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $termStorage;

  public function __construct(
    Connection $database,
    EntityFieldManager $entityFieldManager,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->database = $database;
    $this->entityFieldManager = $entityFieldManager;
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
  }

  public function getTidsByNid(string $nid, $node = null): ?array {
    if ($node === NULL) {
      $node = Node::load($nid);
    }

    if (!$node instanceof NodeInterface) {
      return NULL;
    }

    $fieldNamesWithTaxonomyTerms = [];
    $fieldDefinitons = $this->entityFieldManager->getFieldDefinitions('node', $node->getType());
    foreach ($fieldDefinitons as $fieldDefiniton) {
      if ($fieldDefiniton->getType() === 'entity_reference' && is_numeric(strpos($fieldDefiniton->getSetting('handler'), 'taxonomy_term'))) {
        $fieldNamesWithTaxonomyTerms[] = $fieldDefiniton->getName();
      }
    }

    $tids = [];
    foreach ($fieldNamesWithTaxonomyTerms as $fieldName) {
      $termTargetIdsForField = $node->get($fieldName)->getValue();
      foreach ($termTargetIdsForField as $key => $termTargetId) {
        if (isset($termTargetId['target_id'])) {
          $tids[] = $termTargetId['target_id'];
        }
      }
    }

    if (!empty($tids)) {
      return $tids;
    }

    return NULL;
  }

  public function getTidsBoundToAllNidsForPublishedNodes(): array {
    $query = $this->database->select('taxonomy_index', 'ti')
      ->fields('ti', ['tid', 'nid']);

    $nidToTids = [];

    $ret = $query->execute()
      ->fetchAll();

    foreach ($ret as $returnObject) {
      $nidToTids[$returnObject->nid][] = $returnObject->tid;
    }

    return $nidToTids;
  }

  /**
   * @param array $tids
   *
   * @return array
   */
  public function getNidsByTidsForPublishedNodes($tids) {
    if (!empty($tids)) {
      $query = $this->database->select('taxonomy_index', 'ti')
          ->fields('ti', ['nid'])
          ->condition('ti.tid', $tids, 'IN');

      $nids = $query->execute()
        ->fetchCol();

      return array_unique($nids);
    }
    else {
      return [];
    }
  }

  /**
   * @param string $sTermName
   *
   * @return int|null
   */
  public function getTermIdByName($sTermName) {
    $resultArray = $this->termStorage->getQuery()
      ->condition('name', $sTermName)
      ->execute();

    if (empty($resultArray)) {
      return NULL;
    }

    return current($resultArray);
  }

  /**
   * @param int $term_id
   *
   * @return string
   */
  public function getTermNameById($term_id) {
    $term_name = $this->termStorage->getQuery()
      ->condition('id', $term_id)
      ->execute();
    return key($term_name);
  }

  public function setTerm(Term $term) {
    $this->term = $term;
  }

  /**
   * @return Term
   */
  public function getTerm() {
    return $this->term;
  }

}
