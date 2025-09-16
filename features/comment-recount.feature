Feature: Recount comments on a post

  Scenario: Recount comments on a post
    Given a FIN install

    When I run `fin comment create --comment_post_ID=1 --comment_approved=1 --porcelain`
    And I run `fin comment create --comment_post_ID=1 --comment_approved=1 --porcelain`
    And I run `fin post get 1 --field=comment_count`
    Then STDOUT should be:
      """
      3
      """

    When I run `fin eval 'global $findb; $findb->update( $findb->posts, array( "comment_count" => 1 ), array( "ID" => 1 ) );'`
    And I run `fin post get 1 --field=comment_count`
    Then STDOUT should be:
      """
      1
      """

    When I run `fin comment recount 1`
    Then STDOUT should be:
      """
      Updated post 1 comment count to 3.
      """

    When I try `fin comment recount 99999999`
    Then STDERR should be:
      """
      Warning: Post 99999999 doesn't exist.
      """
