Feature: Empty a FinPress site of its data

  @require-mysql
  Scenario: Empty a site
    Given a FIN installation
    And I run `fin option update uploads_use_yearmonth_folders 0`
    And download:
      | path                        | url                                              |
      | {CACHE_DIR}/large-image.jpg | http://fin-cli.org/behat-data/large-image.jpg     |
    And a insert_link_data.sql file:
      """
      INSERT INTO `fin_links` (`link_url`, `link_name`, `link_image`, `link_target`, `link_description`, `link_visible`, `link_owner`, `link_rating`, `link_rel`, `link_notes`, `link_rss`)
      VALUES ('http://finpress.org/', 'test', '', '', 'test', 'Y', 1, 0, '', '', '')
      """

    When I run `fin db query "SOURCE insert_link_data.sql;"`
    Then STDERR should be empty

    When I run `fin db query "SELECT COUNT(link_id) FROM fin_links;"`
    Then STDOUT should be:
      """
      COUNT(link_id)
      1
      """

    When I run `fin media import {CACHE_DIR}/large-image.jpg --post_id=1`
    Then the fin-content/uploads/large-image.jpg file should exist

    When I try `fin site url 1`
    Then STDERR should be:
      """
      Error: This is not a multisite installation.
      """
    And the return code should be 1

    When I run `fin post create --post_title='Test post' --post_content='Test content.'`
    Then STDOUT should contain:
      """
      Success: Created post
      """

    When I run `fin term create post_tag 'Test term' --slug=test --description='This is a test term'`
    Then STDOUT should be:
      """
      Success: Created post_tag 2.
      """

    When I run `fin post create --post_type=page --post_title='Sample Privacy Page' --post_content='Sample Privacy Terms' --porcelain`
    Then save STDOUT as {PAGE_ID}

    When I run `fin option set fin_page_for_privacy_policy {PAGE_ID}`
    Then STDOUT should be:
      """
      Success: Updated 'fin_page_for_privacy_policy' option.
      """

    When I run `fin option get fin_page_for_privacy_policy`
    Then STDOUT should be:
      """
      {PAGE_ID}
      """

    When I run `fin post create --post_title='Sticky Post' --post_content='This is just a sticky post' --porcelain`
    Then save STDOUT as {STICKY_POST_ID}

    When I run `fin option set sticky_posts '[{STICKY_POST_ID}]' --format=json`
    Then STDOUT should be:
      """
      Success: Updated 'sticky_posts' option.
      """

    When I run `fin site empty --yes`
    Then STDOUT should be:
      """
      Success: The site at 'https://example.com' was emptied.
      """
    And the fin-content/uploads/large-image.jpg file should exist

    When I run `fin post list --format=ids`
    Then STDOUT should be empty

    When I run `fin term list post_tag --format=ids`
    Then STDOUT should be empty

    When I run `fin option get fin_page_for_privacy_policy`
    Then STDOUT should be:
      """
      0
      """

    When I run `fin option get sticky_posts --format=json`
    Then STDOUT should be:
      """
      []
      """

    When I run `fin db query "SELECT COUNT(link_id) FROM fin_links;"`
    Then STDOUT should be:
      """
      COUNT(link_id)
      0
      """

  Scenario: Empty a site and its uploads directory
    Given a FIN multisite installation
    And I run `fin site create --slug=foo`
    And I run `fin --url=example.com/foo option update uploads_use_yearmonth_folders 0`
    And download:
      | path                        | url                                              |
      | {CACHE_DIR}/large-image.jpg | http://fin-cli.org/behat-data/large-image.jpg     |

    When I run `fin --url=example.com/foo media import {CACHE_DIR}/large-image.jpg --post_id=1`
    Then the fin-content/uploads/sites/2/large-image.jpg file should exist

    When I run `fin site empty --uploads --yes`
    Then STDOUT should not be empty
    And the fin-content/uploads/sites/2/large-image.jpg file should exist

    When I run `fin post list --format=ids`
    Then STDOUT should be empty

    When I run `fin --url=example.com/foo site empty --uploads --yes`
    Then STDOUT should contain:
      """
      ://example.com/foo' was emptied.
      """
    And the fin-content/uploads/sites/2/large-image.jpg file should not exist

    When I run `fin --url=example.com/foo post list --format=ids`
    Then STDOUT should be empty
