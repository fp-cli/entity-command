Feature: Recount terms on a taxonomy

  Background:
    Given a FIN install

  Scenario: Term recount with an invalid taxonomy
    When I try `fin term recount some-fake-taxonomy`
    Then STDERR should be:
      """
      Warning: Taxonomy some-fake-taxonomy does not exist.
      """
    And the return code should be 0

  Scenario: Term recount with a valid taxonomy
    When I run `fin term recount category`
    Then STDOUT should be:
      """
      Success: Updated category term count.
      """

  Scenario: Term recount with a multiple taxonomies
    When I run `fin term recount category post_tag`
    Then STDOUT should be:
      """
      Success: Updated category term count.
      Success: Updated post_tag term count.
      """

  Scenario: Fixes an invalid term count for a taxonomy
    When I run `fin term create category "Term Recount Category" --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {TERM_ID}

    When I run `fin post create --post_title='Term Recount Test' --post_status=publish --post_category={TERM_ID} --porcelain`
    Then STDOUT should be a number
    And save STDOUT as {POST_ID}

    When I run `fin term get category {TERM_ID} --field=count`
    Then STDOUT should be:
      """
      1
      """
    When I run `fin eval 'global $findb; $findb->update( $findb->term_taxonomy, array( "count" => 3 ), array( "term_id" => {TERM_ID} ) );'`
    And I run `fin term get category {TERM_ID} --field=count`
    Then STDOUT should be:
      """
      3
      """

    When I run `fin term recount category`
    And I run `fin term get category {TERM_ID} --field=count`
      """
      1
      """
