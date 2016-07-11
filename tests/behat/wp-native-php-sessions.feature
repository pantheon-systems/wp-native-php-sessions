Feature: WP Native PHP Sessions plugin

  Scenario: Plugin is loaded
    When I go to "wp-admin/admin-ajax.php?action=wpnps_plugin_loaded"
    Then I should see "Plugin is loaded."

  Scenario: Plugin handles session CRUD operation
    When I go to "wp-admin/admin-ajax.php?action=wpnps_get_session&key=foo"
    Then I should see "(foo:)"

    When I go to "wp-admin/admin-ajax.php?action=wpnps_set_session&key=foo&value=bar"
    Then I should see "Session updated."

    When I go to "wp-admin/admin-ajax.php?action=wpnps_get_session&key=foo"
    Then I should see "(foo:bar)"

    When I go to "wp-admin/admin-ajax.php?action=wpnps_check_table&key=foo"
    Then I should see "0-foo|s:3:\"bar\";"

    When I go to "wp-admin/admin-ajax.php?action=wpnps_delete_session&key=foo"
    Then I should see "Session deleted."

    When I go to "wp-admin/admin-ajax.php?action=wpnps_get_session&key=foo"
    Then I should see "(foo:)"
