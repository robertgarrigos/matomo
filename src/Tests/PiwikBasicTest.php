<?php

/**
 * @file
 * Test file for Piwik module.
 */
class PiwikBasicTest extends DrupalWebTestCase {

  public static function getInfo() {
    return array(
      'name' => t('Piwik basic tests'),
      'description' => t('Test basic functionality of Piwik module.'),
      'group' => 'Piwik',
    );
  }

  function setUp() {
    parent::setUp('piwik');

    $permissions = array(
      'access administration pages',
      'administer piwik',
    );

    // User to set up piwik.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  function testPiwikConfiguration() {
    // Check for setting page's presence.
    $this->drupalGet('admin/config/system/piwik');
    $this->assertRaw(t('Piwik site ID'), '[testPiwikConfiguration]: Settings page displayed.');

    // Check for account code validation.
    $edit['piwik_site_id'] = $this->randomName(2);
    $this->drupalPost('admin/config/system/piwik', $edit, 'Save configuration');
    $this->assertRaw(t('A valid Piwik site ID is an integer only.'), '[testPiwikConfiguration]: Invalid Piwik site ID number validated.');
  }

  function testPiwikPageVisibility() {
    $ua_code = '1';
    variable_set('piwik_site_id', $ua_code);
    variable_get('piwik_url_http', 'http://example.com/piwik/');
    variable_get('piwik_url_https', 'https://example.com/piwik/');

    // Show tracking on "every page except the listed pages".
    variable_set('piwik_visibility_pages', 0);
    // Disable tracking one "admin*" pages only.
    variable_set('piwik_pages', "admin\nadmin/*");
    // Enable tracking only for authenticated users only.
    variable_set('piwik_roles', array(DRUPAL_AUTHENTICATED_RID => DRUPAL_AUTHENTICATED_RID));

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is displayed for authenticated users.');

    // Test whether tracking code is not included on pages to omit.
    $this->drupalGet('admin');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is not displayed on admin page.');
    $this->drupalGet('admin/config/system/piwik');
    // Checking for tracking code URI here, as $ua_code is displayed in the form.
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is not displayed on admin subpage.');

    // Test whether tracking code display is properly flipped.
    variable_set('piwik_visibility_pages', 1);
    $this->drupalGet('admin');
    $this->assertRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is displayed on admin page.');
    $this->drupalGet('admin/config/system/piwik');
    // Checking for tracking code URI here, as $ua_code is displayed in the form.
    $this->assertRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is displayed on admin subpage.');
    $this->drupalGet('');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is NOT displayed on front page.');

    // Test whether tracking code is not display for anonymous.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertNoRaw('u+"piwik.php"', '[testPiwikPageVisibility]: Tracking code is NOT displayed for anonymous.');

    // Switch back to every page except the listed pages.
    variable_set('piwik_visibility_pages', 0);
    // Enable tracking code for all user roles.
    variable_set('piwik_roles', array());

    // Test whether 403 forbidden tracking code is shown if user has no access.
    $this->drupalGet('admin');
    $this->assertRaw('"403/URL = "', '[testPiwikPageVisibility]: 403 Forbidden tracking code shown if user has no access.');

    // Test whether 404 not found tracking code is shown on non-existent pages.
    $this->drupalGet($this->randomName(64));
    $this->assertRaw('"404/URL = "', '[testPiwikPageVisibility]: 404 Not Found tracking code shown on non-existent page.');
  }

  function testPiwikTrackingCode() {
    $ua_code = '2';
    variable_set('piwik_site_id', $ua_code);
    variable_get('piwik_url_http', 'http://example.com/piwik/');
    variable_get('piwik_url_https', 'https://example.com/piwik/');

    // Show tracking code on every page except the listed pages.
    variable_set('piwik_visibility_pages', 0);
    // Enable tracking code for all user roles.
    variable_set('piwik_roles', array());

    /* Sample JS code as added to page:
    <script type="text/javascript">
    var _paq = _paq || [];
    (function(){
        var u=(("https:" == document.location.protocol) ? "https://{$PIWIK_URL}" : "http://{$PIWIK_URL}");
        _paq.push(['setSiteId', {$IDSITE}]);
        _paq.push(['setTrackerUrl', u+'piwik.php']);
        _paq.push(['trackPageView']);
        var d=document,
            g=d.createElement('script'),
            s=d.getElementsByTagName('script')[0];
            g.type='text/javascript';
            g.defer=true;
            g.async=true;
            g.src=u+'piwik.js';
            s.parentNode.insertBefore(g,s);
    })();
    </script>
    */

    // Test whether tracking code uses latest JS.
    variable_set('piwik_cache', 0);
    $this->drupalGet('');
    $this->assertRaw('u+"piwik.php"', '[testPiwikTrackingCode]: Latest tracking code used.');

    // Test if tracking of User ID is enabled.
    variable_set('piwik_trackuserid', 1);
    $this->drupalGet('');
    $this->assertRaw('_paq.push(["setUserId", ', '[testPiwikTrackingCode]: Tracking code for User ID is enabled.');

    // Test if tracking of User ID is disabled.
    variable_set('piwik_trackuserid', 0);
    $this->drupalGet('');
    $this->assertNoRaw('_paq.push(["setUserId", ', '[testPiwikTrackingCode]: Tracking code for User ID is disabled.');

    // Test whether single domain tracking is active.
    $this->drupalGet('');
    $this->assertNoRaw('_paq.push(["setCookieDomain"', '[testPiwikTrackingCode]: Single domain tracking is active.');

    // Enable "One domain with multiple subdomains".
    variable_set('piwik_domain_mode', 1);
    $this->drupalGet('');

    // Test may run on localhost, an ipaddress or real domain name.
    // TODO: Workaround to run tests successfully. This feature cannot tested reliable.
    global $cookie_domain;
    if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
      $this->assertRaw('_paq.push(["setCookieDomain"', '[testPiwikTrackingCode]: One domain with multiple subdomains is active on real host.');
    }
    else {
      // Special cases, Localhost and IP addresses don't show 'setCookieDomain'.
      $this->assertNoRaw('_paq.push(["setCookieDomain"', '[testPiwikTrackingCode]: One domain with multiple subdomains may be active on localhost (test result is not reliable).');
    }

    // Test whether the BEFORE and AFTER code is added to the tracker.
    variable_set('piwik_codesnippet_before', '_paq.push(["setLinkTrackingTimer", 250]);');
    variable_set('piwik_codesnippet_after', '_paq.push(["t2.setSiteId", 2]);_gaq.push(["t2.trackPageView"]);');
    $this->drupalGet('');
    $this->assertRaw('setLinkTrackingTimer', '[testPiwikTrackingCode]: Before codesnippet has been found with "setLinkTrackingTimer" set.');
    $this->assertRaw('t2.trackPageView', '[testPiwikTrackingCode]: After codesnippet with "t2" tracker has been found.');
  }

}
