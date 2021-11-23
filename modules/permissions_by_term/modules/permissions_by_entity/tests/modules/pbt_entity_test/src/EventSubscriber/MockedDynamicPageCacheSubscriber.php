<?php

namespace Drupal\pbt_entity_test\EventSubscriber;

use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Mocked dynamic page cache subscriber.
 *
 * Ensures the requests are cached.
 */
class MockedDynamicPageCacheSubscriber extends DynamicPageCacheSubscriber {

  /**
   * {@inheritdoc}
   */
  public function onRouteMatch(RequestEvent $event): void {
    // Sets the response for the current route, if cached.
    $cached = $this->renderCache->get($this->dynamicPageCacheRedirectRenderArray);
    if ($cached) {
      $response = $this->renderArrayToResponse($cached);
      $response->headers->set(self::HEADER, 'HIT');
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onResponse(ResponseEvent $event): void {
    $response = $event->getResponse();

    // Embed the response object in a render array so that RenderCache is able
    // to cache it, handling cache redirection for us.
    $response_as_render_array = $this->responseToRenderArray($response);
    $this->renderCache->set($response_as_render_array, $this->dynamicPageCacheRedirectRenderArray);

    // The response was generated, mark the response as a cache miss. The next
    // time, it will be a cache hit.
    $response->headers->set(self::HEADER, 'MISS');
  }
}
