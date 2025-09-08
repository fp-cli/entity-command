Feature: Manage network-wide custom fields.

  Scenario: Non-multisite
    Given a FP install

    When I run `fp network-meta`
    Then STDOUT should contain:
      """
      usage: fp network meta
      """

    When I try `fp network-meta get 1 site_admins`
    Then STDOUT should be empty
    And STDERR should contain:
      """
      This is not a multisite install
      """
    And the return code should be 1
