<?php

/**
 * @file
 * Contains \Drupal\piwik\Tests\PiwikUninstallTest.
 */

namespace Drupal\piwik\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test uninstall functionality of Piwik module.
 *
 * @group Piwik
 */
class PiwikUninstallTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['piwik'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer piwik',
      'administer modules',
    ];

    // User to set up piwik.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  function testPiwikUninstall() {
    $cache_path = 'public://piwik';
    $site_id = '1';
    $this->config('piwik.settings')->set('site_id', $site_id)->save();

    // Enable local caching of piwik.js
    $this->config('piwik.settings')->set('cache', 1)->save();

    // Load page to get the piwik.js downloaded into local cache.
    $this->drupalGet('');

    // Test if the directory and piwik.js exists.
    $this->assertTrue(file_prepare_directory($cache_path), 'Cache directory "public://piwik" has been found.');
    $this->assertTrue(file_exists($cache_path . '/piwik.js'), 'Cached piwik.js tracking file has been found.');

    // Uninstall the module.
    $edit = [];
    $edit['uninstall[piwik]'] = TRUE;
    $this->drupalPostForm('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->assertNoText(\Drupal::translation()->translate('Configuration deletions'), 'No configuration deletions listed on the module install confirmation page.');
    $this->drupalPostForm(NULL, NULL, t('Uninstall'));
    $this->assertText(t('The selected modules have been uninstalled.'), 'Modules status has been updated.');

    // Test if the directory and all files have been removed.
    $this->assertFalse(file_scan_directory($cache_path, '/.*/'), 'Cached JavaScript files have been removed.');
    $this->assertFalse(file_prepare_directory($cache_path), 'Cache directory "public://piwik" has been removed.');
  }

}