Feature: Generate FP users

  Background:
    Given a FP install

  Scenario: Generating and deleting users
    When I run `fp user list --role=editor --format=count`
    Then STDOUT should be:
      """
      0
      """

    When I run `fp user generate --count=10 --role=editor`
    And I run `fp user list --role=editor --format=count`
    Then STDOUT should be:
      """
      10
      """

    When I try `fp user list --field=ID | xargs fp user delete invalid-user --yes`
    Then STDOUT should contain:
      """
      Success: Removed user
      """
    And STDERR should be:
      """
      Warning: Invalid user ID, email or login: 'invalid-user'
      """
    And the return code should be 0

    When I run `fp user list --format=count`
    Then STDOUT should be:
      """
      0
      """

  Scenario: Generating users and outputting ids
    When I run `fp user generate --count=1 --format=ids`
    Then save STDOUT as {USER_ID}

    When I run `fp user update {USER_ID} --display_name="foo"`
    Then STDOUT should contain:
      """
      Success:
      """
