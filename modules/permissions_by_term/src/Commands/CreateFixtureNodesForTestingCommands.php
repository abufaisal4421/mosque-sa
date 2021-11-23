<?php

namespace Drupal\permissions_by_term\Commands;

use Drupal\Component\Utility\Random;
use Drupal\node\Entity\Node;
use Drupal\permissions_by_term\Service\AccessStorage;
use Drupal\taxonomy\Entity\Term;
use Drush\Commands\DrushCommands;


/**
 * A Drush commandfile.
 */
class CreateFixtureNodesForTestingCommands extends DrushCommands {

  /**
   * @var \Drupal\permissions_by_term\Service\AccessStorage
   */
  private $accessStorage;

  /**
   * @var \Drupal\Component\Utility\Random
   */
  private $random;

  public function __construct(AccessStorage $accessStorage) {
    $this->accessStorage = $accessStorage;
    $this->random = new Random();
  }

  /**
   * @command permissions-by-term:create-nodes-with-permissions
   * @aliases pbtcnwp
   */
  public function createNodesWithPermissions(int $numNodes = 1000): void {
    if ($this->io()->confirm('Do you really want to create ' . $numNodes . ' Drupal nodes with permissions? This is a testing feature for the permissions by term module.', FALSE)) {
      $this->io()->progressStart($numNodes);

      for ($i = 0; $i <= $numNodes; ++$i) {
        $term = Term::create([
          'name' => $this->random->word(10),
          'vid'  => 'tags',
        ]);
        $term->save();
        $this->accessStorage->addTermPermissionsByUserIds([1], $term->id());

        $node = Node::create([
          'type' => 'article',
          'title' => $this->random->word(10),
          'field_tags' => [
            [
              'target_id' => $term->id()
            ]
          ],
        ]);
        $node->save();

        $this->io()->progressAdvance();
      }

      $this->io()->progressFinish();
    }
  }

}
