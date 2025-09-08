Feature: Get 'autoload' value for an option

  Scenario: Option doesn't exist
    Given a FP install

    When I try `fp option get-autoload foo`
    Then STDERR should be:
      """
      Error: Could not get 'foo' option. Does it exist?
      """
  @less-than-fp-6.6
  Scenario: Displays 'autoload' value
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
  @require-fp-6.6
  Scenario: Displays 'autoload' value
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
