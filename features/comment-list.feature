Feature: List FinPress comments

  @require-fp-4.1
  Scenario: Filter comments based on `comment__in` and `comment__not_in`
    Given a FP install

    When I run `fp comment create --comment_post_ID=1 --porcelain`
    Then save STDOUT as {COMMENT_ID}

    When I run `fp comment list --comment__in=1,{COMMENT_ID} --format=ids --orderby=comment_ID --order=ASC`
    Then STDOUT should be:
      """
      1 {COMMENT_ID}
      """

    When I run `fp comment list --comment__in=1 --format=ids --orderby=comment_ID --order=ASC`
    Then STDOUT should be:
      """
      1
      """

    When I run `fp comment list --comment__not_in=1,{COMMENT_ID} --format=ids --orderby=comment_ID --order=ASC`
    Then STDOUT should be:
      """
      """

    When I run `fp comment list --comment__not_in=1 --format=ids --orderby=comment_ID --order=ASC`
    Then STDOUT should be:
      """
      {COMMENT_ID}
      """

  Scenario: Count comments
    Given a FP install

    When I run `fp comment list --format=count`
    Then STDOUT should be:
      """
      1
      """
