Feature: Generate FIN users

  Background:
    Given a FIN install

  Scenario: Generating and deleting users
    When I run `fin user list --role=editor --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `fin user generate --count=10 --role=editor`
    And I run `fin user list --role=editor --format=count`
    Then STDOUT should be:
      """
      10
      """

    When I try `fin user list --field=ID | xargs fin user delete invalid-user --yes`
    Then STDOUT should contain:
      """
      Success: Removed user
      """
    And STDERR should be:
      """
      Warning: Invalid user ID, email or login: 'invalid-user'
      """
    And the return code should be 0

    When I run `fin user list --format=count`
    Then STDOUT should be:
      """
      0
      """

  Scenario: Generating users and outputting ids
    When I run `fin user generate --count=1 --format=ids`
    Then save STDOUT as {USER_ID}

    When I run `fin user update {USER_ID} --display_name="foo"`
    Then STDOUT should contain:
      """
      Success:
      """
