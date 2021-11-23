<?php

namespace Drupal\permissions_by_term\Commands;

use Drupal\permissions_by_term\Service\NodeAccess;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
class PermissionsByTermRebuildCommands extends DrushCommands {

  /**
   * @var \Drupal\permissions_by_term\Service\NodeAccess
   */
  private $nodeAccess;

  public function __construct(NodeAccess $nodeAccess) {
    $this->nodeAccess = $nodeAccess;
  }

  /**
   * Rebuild node access for terms related to permissions_by_term.
   *
   * @command permissions-by-term:rebuild
   * @aliases pbtr
   */
  public function accessRebuild() {
    if ($this->io()->confirm('Do you really want to rebuild all Drupal node access records, which the Permissions by Term module manages?', FALSE)) {
      $nids = $this->nodeAccess->getNidsForAccessRebuild();
      $this->io()->progressStart(count($nids));

      foreach ($nids as $nid) {
        $this->nodeAccess->rebuildNodeAccessOne($nid);
        $this->io()->progressAdvance();
      }

      $this->io()->progressFinish();
    }
  }

}
