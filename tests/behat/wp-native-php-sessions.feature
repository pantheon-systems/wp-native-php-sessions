Feature: WP Native PHP Sessions plugin

  Scenario: Plugin is loaded
    When I go to "/?action=wpnps_plugin_loaded"
    Then I should see "Plugin is loaded."

  Scenario: Plugin handles session CRUD operation (logged out)
    When I go to "/?action=wpnps_get_session&key=foo"
    Then I should see "(foo:)"

    When I go to "/?action=wpnps_set_session&key=foo&value=bar"
    Then I should see "Session updated."

    When I go to "/?action=wpnps_get_session&key=foo"
    Then I should see "(foo:bar)"

    When I go to "/?action=wpnps_check_table&key=foo"
    Then I should see "0-foo|s:3:\"bar\";"

    When I go to "/?action=wpnps_delete_session&key=foo"
    Then I should see "Session deleted."

    When I go to "/?action=wpnps_get_session&key=foo"
    Then I should see "(foo:)"

    When I run `echo "foo"`
    Then STDOUT should CONTAIN
      """
      foo
      """

  Scenario: Plugin handles session CRUD operation (logged in)
    Given I log in as an admin

    When I go to "/?action=wpnps_get_session&key=foo"
    Then I should see "(foo:)"

    When I go to "/?action=wpnps_set_session&key=foo&value=bar"
    Then I should see "Session updated."

    When I go to "/?action=wpnps_get_session&key=foo"
    Then I should see "(foo:bar)"

    When I go to "/?action=wpnps_check_table&key=foo"
    Then I should see "1-foo|s:3:\"bar\";"

    When I go to "/?action=wpnps_delete_session&key=foo"
    Then I should see "Session deleted."

    When I go to "/?action=wpnps_get_session&key=foo"
    Then I should see "(foo:)"
