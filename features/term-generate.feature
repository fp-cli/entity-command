Feature: Generate FP terms

  Background:
    Given a FP install

  Scenario: Generating terms
    When I run `fp term generate category --count=10`
    And I run `fp term list category --format=count`
    Then STDOUT should be:
      """
      11
      """

  Scenario: Generating terms when terms already exist
    When I run `fp term generate category --count=10`
    And I run `fp term generate category --count=10`
    And I run `fp term list category --format=count`
    Then STDOUT should be:
      """
      21
      """

  Scenario: Generating terms and outputting ids
    When I run `fp term generate category --count=1 --format=ids`
    Then save STDOUT as {TERM_ID}

    When I run `fp term update category {TERM_ID} --name="foo"`
    Then STDOUT should contain:
      """
      Success:
      """
