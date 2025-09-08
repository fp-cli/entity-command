Feature: Set 'autoload' value for an option

  Scenario: Option doesn't exist
    Given a FP install

    When I try `fp option set-autoload foo yes`
    Then STDERR should be:
      """
      Error: Could not get 'foo' option. Does it exist?
      """

  Scenario: Invalid 'autoload' value provided
    Given a FP install

    When I run `fp option add foo bar`
    Then STDOUT should contain:
      """
      Success:
      """

    When I try `fp option set-autoload foo invalid`
    Then STDERR should be:
      """
      Error: Invalid value specified for positional arg.
      """

  @less-than-fp-6.6
  Scenario: Successfully updates autoload value
    Given a FP install

    When I run `fp option add foo bar`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `fp option get-autoload foo`
    Then STDOUT should be:
      """
      yes
      """

    When I run `fp option set-autoload foo no`
    Then STDOUT should be:
      """
      Success: Updated autoload value for 'foo' option.
      """

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Autoload value passed for 'foo' option is unchanged.
      """

    When I run `fp option get-autoload foo`
    Then STDOUT should be:
      """
      no
      """

  @require-fp-6.6
  Scenario: Successfully updates autoload value
    Given a FP install

    When I run `fp option add foo bar`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `fp option get-autoload foo`
    Then STDOUT should be:
      """
      on
      """

    When I run `fp option set-autoload foo off`
    Then STDOUT should be:
      """
      Success: Updated autoload value for 'foo' option.
      """

    When I run the previous command again
    Then STDOUT should be:
      """
      Success: Autoload value passed for 'foo' option is unchanged.
      """

    When I run `fp option get-autoload foo`
    Then STDOUT should be:
      """
      off
      """
