<?php
// $Id$

/**
 * @file
 * Administrative page callbacks for the piwik module.
 */

/**
 * Implementation of hook_admin_settings() for configuring the module
 */
function piwik_admin_settings_form(&$form_state) {
  $form['account'] = array(
    '#type' => 'fieldset',
    '#title' => t('General settings'),
    '#collapsible' => FALSE,
  );

  $form['account']['piwik_site_id'] = array(
    '#type' => 'textfield',
    '#title' => t('Piwik site ID'),
    '#default_value' => variable_get('piwik_site_id', ''),
    '#size' => 15,
    '#maxlength' => 20,
    '#required' => TRUE,
    '#description' => t('The user account number is unique to the websites domain. Click the <strong>Settings</strong> link in your Piwik account, then the <strong>Sites</strong> tab and enter the <strong>ID</strong> into this field.'),
  );
  $form['account']['piwik_url_http'] = array(
    '#type' => 'textfield',
    '#title' => t('Piwik HTTP URL'),
    '#default_value' => variable_get('piwik_url_http', ''),
    '#size' => 80,
    '#maxlength' => 255,
    '#required' => TRUE,
    '#description' => t('The URL to your Piwik base directory. Example: "http://www.example.com/piwik".'),
  );
  $form['account']['piwik_url_https'] = array(
    '#type' => 'textfield',
    '#title' => t('Piwik HTTPS URL'),
    '#default_value' => variable_get('piwik_url_https', ''),
    '#size' => 80,
    '#maxlength' => 255,
    '#description' => t('The URL to your Piwik base directory with SSL certificate installed. Required if you track a SSL enabled website. Example: "https://www.example.com/piwik".'),
  );

  $piwik_reports_dependencies = '<div class="admin-dependencies">';
  $piwik_reports_dependencies .= t('Depends on: !dependencies', array('!dependencies' => (module_exists('piwik_reports') ? t('@module (<span class="admin-enabled">enabled</span>)', array('@module' => 'Piwik - Reports')) : t('@module (<span class="admin-disabled">disabled</span>)', array('@module' => 'Piwik - Reports')))));
  $piwik_reports_dependencies .= '</div>';

  $form['account']['piwik_auth'] = array(
    '#type' => 'textfield',
    '#title' => t('Piwik authentication string'),
    '#default_value' => variable_get('piwik_auth', ''),
    '#size' => 40,
    '#maxlength' => 40,
    '#disabled' => module_exists('piwik_reports') ? FALSE : TRUE,
    '#description' => t('Click the <strong>Settings</strong> link in your Piwik account, then the <strong>Users</strong> tab and create a new user via <strong>Add a new user</strong>. Grant this user <strong>view</strong> permissions. Enter the users <strong>token_auth</strong> value into this field.') . $piwik_reports_dependencies,
  );

  // Standard tracking configurations.
  $form['user_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('User specific tracking settings'),
    '#collapsible' => TRUE,
  );
  $form['user_vis_settings']['piwik_custom'] = array(
    '#type' => 'radios',
    '#title' => t('Custom tracking settings'),
    '#options' => array(
      t('Users cannot control whether they are tracked or not.'),
      t('Track users by default, but let individual users to opt out.'),
      t('Do not track users by default, but let individual users to opt in.')
    ),
    '#description' => t('Allow individual users to customize the visibility of tracking in their account settings. Only users with %permission permission are allowed to set their own preference.', array('%permission' => t('opt-in or out of tracking'))),
    '#default_value' => variable_get('piwik_custom', 0),
  );

  // Render the role overview.
  $form['role_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Role specific tracking settings'),
    '#collapsible' => TRUE,
  );

  $roles = user_roles();
  $role_options = array();
  foreach ($roles as $rid => $name) {
    $role_options[$rid] = $name;
  }
  $form['role_vis_settings']['piwik_roles'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Add tracking for specific roles'),
    '#default_value' => variable_get('piwik_roles', array()),
    '#options' => $role_options,
    '#description' => t('Add tracking only for the selected role(s). If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked.'),
  );

  // Page specific visibility configurations.
  $form['page_vis_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Page specific tracking settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $access = user_access('use PHP for tracking visibility');
  $visibility = variable_get('piwik_visibility', 0);
  $pages = variable_get('piwik_pages', '');

  if ($visibility == 2 && !$access) {
    $form['page_vis_settings'] = array();
    $form['page_vis_settings']['visibility'] = array('#type' => 'value', '#value' => 2);
    $form['page_vis_settings']['pages'] = array('#type' => 'value', '#value' => $pages);
  }
  else {
    $options = array(t('Add to every page except the listed pages.'), t('Add to the listed pages only.'));
    $description = t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>'));

    if ($access) {
      $options[] = t('Add if the following PHP code returns <code>TRUE</code> (PHP-mode, experts only).');
      $description .= ' '. t('If the PHP-mode is chosen, enter PHP code between %php. Note that executing incorrect PHP-code can break your Drupal site.', array('%php' => '<?php ?>'));
    }
    $form['page_vis_settings']['piwik_visibility'] = array(
      '#type' => 'radios',
      '#title' => t('Add tracking to specific pages'),
      '#options' => $options,
      '#default_value' => $visibility,
    );
    $form['page_vis_settings']['piwik_pages'] = array(
      '#type' => 'textarea',
      '#title' => t('Pages'),
      '#default_value' => $pages,
      '#description' => $description,
      '#wysiwyg' => FALSE,
    );
  }

  // Link specific configurations.
  $form['linktracking'] = array(
    '#type' => 'fieldset',
    '#title' => t('Link tracking settings'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['linktracking']['piwik_track'] = array(
    '#type' => 'checkbox',
    '#title' => t('Track download and outgoing links'),
    '#default_value' => variable_get('piwik_track', 1),
    '#description' => t('Enables tracking of clicks on download and outgoing links.')
  );
  $form['linktracking']['piwik_trackfiles_extensions'] = array(
    '#type' => 'textfield',
    '#title' => t('File extensions to track'),
    '#default_value' => variable_get('piwik_trackfiles_extensions', PK_TRACKFILES_EXTENSIONS),
    '#description' => t('A pipe separated list of file extensions that should be tracked when clicked with regular expression support. Example: !extensions', array('!extensions' => PK_TRACKFILES_EXTENSIONS))
  );

  // Advanced feature configurations.
  $form['advanced'] = array(
    '#type' => 'fieldset',
    '#title' => t('Advanced settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['advanced']['piwik_cache'] = array(
    '#type' => 'checkbox',
    '#title' => t('Cache tracking code file locally'),
    '#description' => t("If checked, the tracking code file is retrieved from Piwik and cached locally. It is updated daily from your Piwik servers to ensure updates to tracking code are reflected in the local copy."),
    '#default_value' => variable_get('piwik_cache', 0),
  );
  if (variable_get('file_downloads', FILE_DOWNLOADS_PUBLIC) == FILE_DOWNLOADS_PRIVATE) {
    $form['advanced']['piwik_cache']['#disabled'] = TRUE;
    $form['advanced']['piwik_cache']['#description'] .= ' '. t('<a href="@url">Public file transfers</a> must be enabled to allow local caching.', array('@url' => url('admin/settings/file-system', array('query' => drupal_get_destination()))));
  }

  $form['advanced']['piwik_codesnippet'] = array(
    '#type' => 'textarea',
    '#title' => t('Custom JavaScript code'),
    '#default_value' => variable_get('piwik_codesnippet', ''),
    '#rows' => 5,
    '#wysiwyg' => FALSE,
    '#description' => t('You can add custom Piwik <a href="@snippets">code snippets</a> here. These will be added to every page that Piwik appears on. <strong>Do not include the &lt;script&gt; tags</strong>, and always end your code with a semicolon (;).', array('@snippets' => 'http://piwik.org/javascript-tag-documentation/'))
  );

  $form['advanced']['piwik_js_scope'] = array(
    '#type' => 'select',
    '#title' => t('JavaScript scope'),
    '#description' => t("<strong>Warning:</strong> Adding the external JavaScript files to the footer region is recommended for performance reasons."),
    '#options' => array(
      'footer' => t('Footer'),
      'header' => t('Header'),
    ),
    '#default_value' => variable_get('piwik_js_scope', 'footer'),
  );

  return system_settings_form($form);
}

function piwik_admin_settings_form_validate($form, &$form_state) {
  if (!preg_match('/^\d{1,}$/', $form_state['values']['piwik_site_id'])) {
    form_set_error('piwik_site_id', t('A valid Piwik site ID is an integer only.'));
  }

  $url = $form_state['values']['piwik_url_http'] .'/piwik.php';
  $result = drupal_http_request($url);
  if ($result->code != 200) {
    form_set_error('piwik_url_http', t('The validation of "@url" failed with error "@error" (HTTP code @code).', array('@url' => check_url($url), '@error' => $result->error, '@code' => $result->code)));
  }

  if (!empty($form_state['values']['piwik_url_https'])) {
    $url = $form_state['values']['piwik_url_https'] .'/piwik.php';
    $result = drupal_http_request($url);
    if ($result->code != 200) {
      form_set_error('piwik_url_https', t('The validation of "@url" failed with error "@error" (HTTP code @code).', array('@url' => check_url($url), '@error' => $result->error, '@code' => $result->code)));
    }
  }

  // This is for the Newbie's who cannot read a text area description.
  if (preg_match('/(.*)<\/?script(.*)>(.*)/i', $form_state['values']['piwik_codesnippet'])) {
    form_set_error('piwik_codesnippet', t('Do not include the &lt;script&gt; tags in this field.'));
  }

  // Trim some text area values.
  $form_state['values']['piwik_pages'] = trim($form_state['values']['piwik_pages']);
  $form_state['values']['piwik_codesnippet'] = trim($form_state['values']['piwik_codesnippet']);
}