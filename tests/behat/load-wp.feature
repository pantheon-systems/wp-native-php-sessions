Feature: Load WordPress

  Scenario: Verify that WordPress loads with the plugin active
    Given I am on the homepage
    Then I should see "Hello World"

  Scenario: Verify that a user can update the site's title
    When I go to the homepage
    Then print current URL
    And the ".wp-block-site-title" element should not contain "Pantheon WordPress Site"

    When I go to "wp-login.php"
    Then print current URL

    Given I log in as an admin

    When I go to "/wp-admin/options-general.php"
    And I fill in "blogname" with "Pantheon WordPress Site"
    And I press "submit"
    Then print current URL
    And I should see "Settings saved."

    When I go to the homepage
    Then the ".wp-block-site-title" element should contain "Pantheon WordPress Site"
