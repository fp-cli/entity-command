Feature: Manage FinPress taxonomies

  Background:
    Given a FP install

  @require-fp-3.7
  Scenario: Listing taxonomies
    When I run `fp taxonomy list --format=csv`
    Then STDOUT should be CSV containing:
      | name     | label      | description | object_type | show_tagcloud | hierarchical | public |
      | category | Categories |             | post        | 1             | 1            | 1      |
      | post_tag | Tags       |             | post        | 1             |              | 1      |

    When I run `fp taxonomy list --object_type=nav_menu_item --format=csv`
    Then STDOUT should be CSV containing:
      | name     | label            | description | object_type   | show_tagcloud | hierarchical | public |
      | nav_menu | Navigation Menus |             | nav_menu_item |               |              |        |

  @require-fp-5.0
  Scenario: Listing taxonomies with counts
    When I run `fp taxonomy list --fields=name,count --format=csv`
    Then STDOUT should be CSV containing:
      | name     | count      |
      | category | 1          |
      | post_tag | 0          |

  Scenario: Get taxonomy
    When I try `fp taxonomy get invalid-taxonomy`
    Then STDERR should be:
      """
      Error: Taxonomy invalid-taxonomy doesn't exist.
      """
    And the return code should be 1

    When I run `fp taxonomy get category`
    Then STDOUT should be a table containing rows:
      | Field       | Value      |
      | name        | category   |
      | object_type | ["post"]   |
      | label       | Categories |

  @require-fp-5.0
  Scenario: Get taxonomy with count
    When I run `fp taxonomy get category --fields=name,count`
    Then STDOUT should be a table containing rows:
      | Field       | Value      |
      | name        | category   |
      | count       | 1          |

  @require-fp-5.1
  Scenario: Listing taxonomies with strict/no-strict mode
    Given a FP installation
    And a fp-content/mu-plugins/test-taxonomy-list.php file:
      """
      <?php
      // Plugin Name: Test Taxonomy Strict/No-Strict Mode

      add_action( 'init', function() {
        $args = array(
          'hierarchical'          => true,
          'show_ui'               => true,
          'show_admin_column'     => true,
          'update_count_callback' => '_update_post_term_count',
          'query_var'             => true,
          'labels'                => array(
            'name' => _x( 'Genres', 'taxonomy general name', 'textdomain' ),
          ),

        );

        register_taxonomy( 'genres', array( 'post','page' ), $args );
      } );
      """

    When I run `fp taxonomy list --object_type=post --strict`
    Then STDOUT should be a table containing rows:
      | name        | label      | description | object_type | show_tagcloud | hierarchical | public |
      | category    | Categories |             | post        | 1             | 1            | 1      |
      | post_tag    | Tags       |             | post        | 1             |              | 1      |
      | post_format | Formats    |             | post        |               |              | 1      |

    When I run `fp taxonomy list --object_type=post --no-strict`
    Then STDOUT should be a table containing rows:
      | name        | label      | description | object_type | show_tagcloud | hierarchical | public |
      | category    | Categories |             | post        | 1             | 1            | 1      |
      | post_tag    | Tags       |             | post        | 1             |              | 1      |
      | post_format | Formats    |             | post        |               |              | 1      |
      | genres      | Genres     |             | post, page  | 1             | 1            | 1      |

  @less-than-fp-5.1
  Scenario: Listing taxonomies with strict/no-strict mode (for FP < 5.1)
    Given a FP installation
    And a fp-content/mu-plugins/test-taxonomy-list.php file:
      """
      <?php
      // Plugin Name: Test Taxonomy Strict/No-Strict Mode

      add_action( 'init', function() {
        $args = array(
          'hierarchical'          => true,
          'show_ui'               => true,
          'show_admin_column'     => true,
          'update_count_callback' => '_update_post_term_count',
          'query_var'             => true,
          'labels'                => array(
            'name' => _x( 'Genres', 'taxonomy general name', 'textdomain' ),
          ),

        );

        register_taxonomy( 'genres', array( 'post','page' ), $args );
      } );
      """

    When I run `fp taxonomy list --object_type=post --strict`
    Then STDOUT should be a table containing rows:
      | name        | label      | description | object_type | show_tagcloud | hierarchical | public |
      | category    | Categories |             | post        | 1             | 1            | 1      |
      | post_tag    | Tags       |             | post        | 1             |              | 1      |
      | post_format | Format     |             | post        |               |              | 1      |

    When I run `fp taxonomy list --object_type=post --no-strict`
    Then STDOUT should be a table containing rows:
      | name        | label      | description | object_type | show_tagcloud | hierarchical | public |
      | category    | Categories |             | post        | 1             | 1            | 1      |
      | post_tag    | Tags       |             | post        | 1             |              | 1      |
      | post_format | Format     |             | post        |               |              | 1      |
      | genres      | Genres     |             | post, page  | 1             | 1            | 1      |
