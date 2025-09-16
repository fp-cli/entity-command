Feature: Manage network-wide custom fields.

  Scenario: Non-multisite
    Given a FIN install

    When I run `fin network-meta`
    Then STDOUT should contain:
      """
      usage: fin network meta
      """

    When I try `fin network-meta get 1 site_admins`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      This is not a multisite install
      """
    And the return code should be 1
