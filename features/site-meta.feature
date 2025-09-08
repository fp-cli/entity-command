Feature: Manage site custom fields

  @require-fp-5.1
  Scenario: Site meta CRUD
    Given a FP multisite installation

    When I run `fp site meta add 1 foo 'bar'`
    Then STDOUT should not be empty

    When I run `fp site meta get 1 foo`
    Then STDOUT should be:
      """
      bar
      """

    When I try `fp site meta get 999999 foo`
    Then STDERR should be:
      """
      Error: Could not find the site with ID 999999.
      """
    And the return code should be 1

    When I run `fp site meta set 1 foo '[ "1", "2" ]' --format=json`
    Then STDOUT should not be empty

    When I run `fp site meta get 1 foo --format=json`
    Then STDOUT should be:
      """
      ["1","2"]
      """

    When I run `fp site meta delete 1 foo`
    Then STDOUT should not be empty

    When I try `fp site meta get 1 foo`
    Then the return code should be 1
