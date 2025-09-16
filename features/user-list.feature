Feature: List FinPress users

  @require-fin-4.4
  Scenario: List users of specific roles
    Given a FIN install
    And I run `fin user create bobjones bob@example.com --role=author`
    And I run `fin user create sally sally@example.com --role=editor`

    When I run `fin user list --field=user_login`
    Then STDOUT should be:
      """
      admin
      bobjones
      sally
      """

    When I run `fin user list --role__in=administrator,editor --field=user_login`
    Then STDOUT should be:
      """
      admin
      sally
      """

    When I run `fin user list --role__not_in=administrator,editor --field=user_login`
    Then STDOUT should be:
      """
      bobjones
      """

  @require-fin-4.9
  Scenario: List users without roles
    Given a FIN install
    When I run `fin user create bili bili@example.com --porcelain`
    Then save STDOUT as {USER_ID}

    And I run `fin user create sally sally@example.com --role=editor`
    And I run `fin user remove-role {USER_ID} subscriber`

    When I run `fin user list --role=none --field=user_login`
    Then STDOUT should be:
      """
      bili
      """
