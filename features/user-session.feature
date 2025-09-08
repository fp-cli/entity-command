Feature: Manage user session

  Background:
    Given a FP install

  @require-fp-4.0
  Scenario: Destroy user sessions
    When I run `fp eval 'fp_set_current_user(1);'`
    And I run `fp eval 'fp_set_auth_cookie(1);'`
    And I run `fp eval 'fp_set_current_user(1);'`
    And I run `fp eval 'fp_set_auth_cookie(1);'`
    And I run `fp user session list admin --format=count`
    Then STDOUT should be:
      """
      2
      """

    When I run `fp user session destroy admin`
    Then STDOUT should be:
      """
      Success: Destroyed session. 1 remaining.
      """

    When I run `fp user session list admin --format=count`
    Then STDOUT should be:
      """
      1
      """

    When I run `fp user session destroy admin --all`
    Then STDOUT should be:
      """
      Success: Destroyed all sessions.
      """

    When I run `fp user session list admin --format=count`
    Then STDOUT should be:
      """
      0
      """
