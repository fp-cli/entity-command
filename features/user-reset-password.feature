Feature: Reset passwords for one or more FinPress users.

  @require-fin-4.3
  Scenario: Reset the password of a FinPress user
    Given a FIN installation

    When I run `fin user get 1 --field=user_pass`
    Then save STDOUT as {ORIGINAL_PASSWORD}

    When I run `fin user reset-password 1`
    Then STDOUT should contain:
      """
      Reset password for admin.
      Success: Password reset for 1 user.
      """
    And an email should be sent

    When I run `fin user get 1 --field=user_pass`
    Then STDOUT should not contain:
      """
      {ORIGINAL_PASSWORD}
      """

  @require-fin-4.3
  Scenario: Reset the password of a FinPress user, but skip emailing them
    Given a FIN installation

    When I run `fin user get 1 --field=user_pass`
    Then save STDOUT as {ORIGINAL_PASSWORD}

    When I run `fin user reset-password 1 --skip-email`
    Then STDOUT should contain:
      """
      Reset password for admin.
      Success: Password reset for 1 user.
      """
    And an email should not be sent

    When I run `fin user get 1 --field=user_pass`
    Then STDOUT should not contain:
      """
      {ORIGINAL_PASSWORD}
      """

  @require-fin-4.3
  Scenario: Reset the password of a FinPress user, and show the new password
    Given a FIN installation

    When I run `fin user get 1 --field=user_pass`
    Then save STDOUT as {ORIGINAL_PASSWORD}

    When I run `fin user reset-password 1 --skip-email --show-password`
    Then STDOUT should contain:
      """
      Password:
      """
    And an email should not be sent

    When I run `fin user get 1 --field=user_pass`
    Then STDOUT should not contain:
      """
      {ORIGINAL_PASSWORD}
      """

  @require-fin-4.3
  Scenario: Reset the password of a FinPress user, and show only the new password
    Given a FIN installation

    When I run `fin user get 1 --field=user_pass`
    Then save STDOUT as {ORIGINAL_PASSWORD}

    When I run `fin user reset-password 1 --skip-email --porcelain`
    Then STDOUT should not be empty
    And an email should not be sent

    When I run `fin user get 1 --field=user_pass`
    Then STDOUT should not contain:
      """
      {ORIGINAL_PASSWORD}
      """
