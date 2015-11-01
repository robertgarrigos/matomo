<?php

/**
 * @file
 * Contains \Drupal\piwik\Tests\PiwikCustomVariablesTest.
 */

namespace Drupal\piwik\Tests;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\WebTestBase;

/**
 * Test custom variables functionality of Piwik module.
 *
 * @group Piwik
 */
class PiwikCustomVariablesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['piwik', 'token'];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer piwik',
    ];

    // User to set up piwik.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  function testPiwikCustomVariables() {
    $site_id = '3';
    $this->config('piwik.settings')->set('site_id', $site_id)->save();

    // Basic test if the feature works.
    $custom_vars = array(
      'slots' => array(
        1 => array(
          'slot' => 1,
          'name' => 'Foo 1',
          'value' => 'Bar 1',
          'scope' => 3,
        ),
        2 => array(
          'slot' => 2,
          'name' => 'Foo 2',
          'value' => 'Bar 2',
          'scope' => 2,
        ),
        3 => array(
          'slot' => 3,
          'name' => 'Foo 3',
          'value' => 'Bar 3',
          'scope' => 3,
        ),
        4 => array(
          'slot' => 4,
          'name' => 'Foo 4',
          'value' => 'Bar 4',
          'scope' => 2,
        ),
        5 => array(
          'slot' => 5,
          'name' => 'Foo 5',
          'value' => 'Bar 5',
          'scope' => 1,
        ),
      )
    );
    $this->config('piwik.settings')->set('custom.variable', $custom_vars)->save();
    $this->drupalGet('');

    foreach ($custom_vars['slots'] as $slot) {
      $this->assertRaw("_paq.push(['setCustomVariable', " . $slot['slot'] . ", \"" . $slot['name'] . "\", \"" . $slot['value'] . "\", " . $slot['scope'] . "]);", '[testPiwikCustomVariables]: setCustomVariable ' . $slot['slot'] . ' is shown.');
    }

    // Test whether tokens are replaced in custom variable names.
    $site_slogan = $this->randomName(16);
    $this->config('system.site')->set('slogan', $site_slogan)->save();

    $custom_vars = array(
      'slots' => array(
        1 => array(
          'slot' => 1,
          'name' => 'Name: [site:slogan]',
          'value' => 'Value: [site:slogan]',
          'scope' => 3,
        ),
        2 => array(
          'slot' => 2,
          'name' => '',
          'value' => $this->randomName(16),
          'scope' => 1,
        ),
        3 => array(
          'slot' => 3,
          'name' => $this->randomName(16),
          'value' => '',
          'scope' => 2,
        ),
        4 => array(
          'slot' => 4,
          'name' => '',
          'value' => '',
          'scope' => 3,
        ),
        5 => array(
          'slot' => 5,
          'name' => '',
          'value' => '',
          'scope' => 3,
        ),
      )
    );
    $this->config('piwik.settings')->set('custom.variable', $custom_vars)->save();
    $this->verbose('<pre>' . print_r($custom_vars, TRUE) . '</pre>');

    $this->drupalGet('');
    $this->assertRaw("_paq.push(['setCustomVariable', 1, \"Name: $site_slogan\", \"Value: $site_slogan\", 3]", '[testPiwikCustomVariables]: Tokens have been replaced in custom variable.');
    $this->assertNoRaw("_paq.push(['setCustomVariable', 2,", '[testPiwikCustomVariables]: Value with empty name is not shown.');
    $this->assertNoRaw("_paq.push(['setCustomVariable', 3,", '[testPiwikCustomVariables]: Name with empty value is not shown.');
    $this->assertNoRaw("_paq.push(['setCustomVariable', 4,", '[testPiwikCustomVariables]: Empty name and value is not shown.');
    $this->assertNoRaw("_paq.push(['setCustomVariable', 5,", '[testPiwikCustomVariables]: Empty name and value is not shown.');
  }
}