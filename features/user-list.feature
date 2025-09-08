Feature: List FinPress users

  @require-fp-4.4
  Scenario: List users of specific roles
    Given a FP install
    And I run `fp user create bobjones bob@example.com --role=author`
    And I run `fp user create sally sally@example.com --role=editor`

    When I run `fp user list --field=user_login`
    Then STDOUT should be:
      """
      admin
      bobjones
      sally
      """

    When I run `fp user list --role__in=administrator,editor --field=user_login`
    Then STDOUT should be:
      """
      admin
      sally
      """

    When I run `fp user list --role__not_in=administrator,editor --field=user_login`
    Then STDOUT should be:
      """
      bobjones
      """

  @require-fp-4.9
  Scenario: List users without roles
    Given a FP install
    When I run `fp user create bili bili@example.com --porcelain`
    Then save STDOUT as {USER_ID}

    And I run `fp user create sally sally@example.com --role=editor`
    And I run `fp user remove-role {USER_ID} subscriber`

    When I run `fp user list --role=none --field=user_login`
    Then STDOUT should be:
      """
      bili
      """
