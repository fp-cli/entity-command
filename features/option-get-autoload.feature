Feature: Get 'autoload' value for an option

  Scenario: Option doesn't exist
    Given a FIN install

    When I try `fin option get-autoload foo`
    Then STDERR should be:
      """
      Error: Could not get 'foo' option. Does it exist?
      """
  @less-than-fin-6.6
  Scenario: Displays 'autoload' value
    Given a FIN install

    When I run `fin option add foo bar`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `fin option get-autoload foo`
    Then STDOUT should be:
      """
      yes
      """
  @require-fin-6.6
  Scenario: Displays 'autoload' value
    Given a FIN install

    When I run `fin option add foo bar`
    Then STDOUT should contain:
      """
      Success:
      """

    When I run `fin option get-autoload foo`
    Then STDOUT should be:
      """
      on
      """
