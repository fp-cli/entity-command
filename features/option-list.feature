Feature: List FinPress options

  Scenario: Using the `--transients` flag
    Given a FP install
    And I run `fp transient set fp_transient_flag fp_transient_flag`

    When I run `fp option list --no-transients`
    Then STDOUT should not contain:
      """
      fp_transient_flag
      """
    And STDOUT should not contain:
      """
      _transient
      """
    And STDOUT should contain:
      """
      siteurl
      """

    When I run `fp option list --transients`
    Then STDOUT should contain:
      """
      fp_transient_flag
      """
    And STDOUT should contain:
      """
      _transient
      """
    And STDOUT should not contain:
      """
      siteurl
      """

  Scenario: List option with exclude pattern
    Given a FP install

    When I run `fp option add sample_test_field_one sample_test_field_value_one`
    And I run `fp option add sample_test_field_two sample_test_field_value_two`
    And I run `fp option list --search="sample_test_field_*" --format=csv`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_one,sample_test_field_value_one
      sample_test_field_two,sample_test_field_value_two
      """

    When I run `fp option list --search="sample_test_field_*" --exclude="*field_one" --format=csv`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_two,sample_test_field_value_two
      """

    When I run `fp option list`
    Then STDOUT should contain:
      """
      sample_test_field_one
      """

    When I run `fp option list --exclude="sample_test_field_one"`
    Then STDOUT should not contain:
      """
      sample_test_field_one
      """

  Scenario: List option with sorting option
    Given a FP install
    And I run `fp option add sample_test_field_one sample_test_field_value_one`
    And I run `fp option add sample_test_field_two sample_test_field_value_two`

    When I run `fp option list --search="sample_test_field_*" --format=csv --orderby=option_id --order=asc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_one,sample_test_field_value_one
      sample_test_field_two,sample_test_field_value_two
      """

    When I run `fp option list --search="sample_test_field_*" --format=csv --orderby=option_id --order=desc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_two,sample_test_field_value_two
      sample_test_field_one,sample_test_field_value_one
      """

    When I run `fp option list --search="sample_test_field_*" --format=csv --orderby=option_name --order=asc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_one,sample_test_field_value_one
      sample_test_field_two,sample_test_field_value_two
      """

    When I run `fp option list --search="sample_test_field_*" --format=csv --orderby=option_name --order=desc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_two,sample_test_field_value_two
      sample_test_field_one,sample_test_field_value_one
      """

    When I run `fp option list --search="sample_test_field_*" --format=csv --orderby=option_value --order=asc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_one,sample_test_field_value_one
      sample_test_field_two,sample_test_field_value_two
      """

    When I run `fp option list --search="sample_test_field_*" --format=csv --orderby=option_value --order=desc`
    Then STDOUT should be:
      """
      option_name,option_value
      sample_test_field_two,sample_test_field_value_two
      sample_test_field_one,sample_test_field_value_one
      """

  Scenario: Default list option without transient
    Given a FP install
    And I run `fp transient set fp_transient_flag fp_transient_flag`

    When I run `fp option list`
    Then STDOUT should not contain:
      """
      fp_transient_flag
      """
    And STDOUT should not contain:
      """
      _transient
      """
    And STDOUT should contain:
      """
      siteurl
      """

  Scenario: Using the `--unserialize` flag
    Given a FP install

    When I run `fp option add --format=json sample_test_field_one '{"value": 1}'`
    And I run `fp option list --search="sample_test_field_*" --format=yaml --unserialize`
    Then STDOUT should be:
      """
      ---
      - 
        option_name: sample_test_field_one
        option_value:
          value: 1
      """

  Scenario: Using the `--autoload=on` flag
    Given a FP install
    And I run `fp option add sample_autoload_one 'sample_value_one' --autoload=yes`
    And I run `fp option add sample_autoload_two 'sample_value_two' --autoload=no`
    And I run `fp option add sample_autoload_three 'sample_value_three' --autoload=on`
    And I run `fp option add sample_autoload_four 'sample_value_four' --autoload=off`

    When I run `fp option list --autoload=on`
    Then STDOUT should not contain:
      """
      sample_value_two
      """
    And STDOUT should not contain:
      """
      sample_value_four
      """
    And STDOUT should contain:
      """
      sample_value_one
      """
    And STDOUT should contain:
      """
      sample_value_three
      """

  Scenario: Using the `--autoload=off` flag
    Given a FP install
    And I run `fp option add sample_autoload_one 'sample_value_one' --autoload=yes`
    And I run `fp option add sample_autoload_two 'sample_value_two' --autoload=no`
    And I run `fp option add sample_autoload_three 'sample_value_three' --autoload=on`
    And I run `fp option add sample_autoload_four 'sample_value_four' --autoload=off`

    When I run `fp option list --autoload=off`
    Then STDOUT should not contain:
      """
      sample_value_one
      """
    And STDOUT should not contain:
      """
      sample_value_three
      """
    And STDOUT should contain:
      """
      sample_value_two
      """
    And STDOUT should contain:
      """
      sample_value_four
      """