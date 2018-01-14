<?php

namespace Drupal\matomo_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for system_test routes.
 */
class MatomoTestController extends ControllerBase {

  /**
   * Tests setting messages and removing one before it is displayed.
   *
   * @return string
   *   Empty string, we just test the setting of messages.
   */
  public function drupalAddMessageTest() {
    // Set some messages.
    $messenger = \Drupal::messenger();
    $messenger->addMessage($this->t('Example status message.'), 'status');
    $messenger->addMessage($this->t('Example warning message.'), 'warning');
    $messenger->addMessage($this->t('Example error message.'), 'error');
    $messenger->addMessage($this->t('Example error <em>message</em> with html tags and <a href="http://example.com/">link</a>.'), 'error');

    return [];
  }

}
