<?php

namespace Drupal\permissions_by_term\Cache;

use Drupal\Core\Cache\CacheTagsInvalidator;

class CacheInvalidator {

  /**
   * @var CacheTagsInvalidator
   */
  private $cacheTagsInvalidator;

  public function __construct(CacheTagsInvalidator $cacheTagsInvalidator) {
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
  }

  public function invalidate(): void {
    $this->cacheTagsInvalidator->invalidateTags([
      'search_index:node_search',
      'permissions_by_term:access_result_cache',
      'permissions_by_term:key_value_cache'
    ]);
  }

}
