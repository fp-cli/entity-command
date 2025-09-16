<?php

use FIN_CLI\Formatter;
use FIN_CLI\Utils;

/**
 * List, add, and delete items associated with a menu.
 *
 * ## EXAMPLES
 *
 *     # Add an existing post to an existing menu
 *     $ fin menu item add-post sidebar-menu 33 --title="Custom Test Post"
 *     Success: Menu item added.
 *
 *     # Create a new menu link item
 *     $ fin menu item add-custom sidebar-menu Apple http://apple.com
 *     Success: Menu item added.
 *
 *     # Delete menu item
 *     $ fin menu item delete 45
 *     Success: Deleted 1 of 1 menu items.
 */
class Menu_Item_Command extends FIN_CLI_Command {

	protected $obj_fields = [
		'db_id',
		'type',
		'title',
		'link',
		'position',
	];

	/**
	 * Gets a list of items associated with a menu.
	 *
	 * ## OPTIONS
	 *
	 * <menu>
	 * : The name, slug, or term ID for the menu.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - count
	 *   - ids
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each menu item:
	 *
	 * * db_id
	 * * type
	 * * title
	 * * link
	 * * position
	 *
	 * These fields are optionally available:
	 *
	 * * menu_item_parent
	 * * object_id
	 * * object
	 * * type
	 * * type_label
	 * * target
	 * * attr_title
	 * * description
	 * * classes
	 * * xfn
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin menu item list main-menu
	 *     +-------+-----------+-------------+---------------------------------+----------+
	 *     | db_id | type      | title       | link                            | position |
	 *     +-------+-----------+-------------+---------------------------------+----------+
	 *     | 5     | custom    | Home        | http://example.com              | 1        |
	 *     | 6     | post_type | Sample Page | http://example.com/sample-page/ | 2        |
	 *     +-------+-----------+-------------+---------------------------------+----------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		$items = fin_get_nav_menu_items( $args[0] );
		if ( false === $items ) {
			FIN_CLI::error( 'Invalid menu.' );
		}

		// Correct position inconsistency and
		// protected `url` param in FIN-CLI
		$items = array_map(
			function ( $item ) {
					$item->position = $item->menu_order;
					$item->link     = $item->url;
					return $item;
			},
			$items
		);

		if ( ! empty( $assoc_args['format'] ) && 'ids' === $assoc_args['format'] ) {
			$items = array_map(
				function ( $item ) {
						return $item->db_id;
				},
				$items
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Adds a post as a menu item.
	 *
	 * ## OPTIONS
	 *
	 * <menu>
	 * : The name, slug, or term ID for the menu.
	 *
	 * <post-id>
	 * : Post ID to add to the menu.
	 *
	 * [--title=<title>]
	 * : Set a custom title for the menu item.
	 *
	 * [--link=<link>]
	 * : Set a custom url for the menu item.
	 *
	 * [--description=<description>]
	 * : Set a custom description for the menu item.
	 *
	 * [--attr-title=<attr-title>]
	 * : Set a custom title attribute for the menu item.
	 *
	 * [--target=<target>]
	 * : Set a custom link target for the menu item.
	 *
	 * [--classes=<classes>]
	 * : Set a custom link classes for the menu item.
	 *
	 * [--position=<position>]
	 * : Specify the position of this menu item.
	 *
	 * [--parent-id=<parent-id>]
	 * : Make this menu item a child of another menu item.
	 *
	 * [--porcelain]
	 * : Output just the new menu item id.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin menu item add-post sidebar-menu 33 --title="Custom Test Post"
	 *     Success: Menu item added.
	 *
	 * @subcommand add-post
	 */
	public function add_post( $args, $assoc_args ) {

		$assoc_args['object-id'] = $args[1];
		unset( $args[1] );
		$post = get_post( $assoc_args['object-id'] );
		if ( ! $post ) {
			FIN_CLI::error( 'Invalid post.' );
		}
		$assoc_args['object'] = $post->post_type;

		$this->add_or_update_item( 'add', 'post_type', $args, $assoc_args );
	}

	/**
	 * Adds a taxonomy term as a menu item.
	 *
	 * ## OPTIONS
	 *
	 * <menu>
	 * : The name, slug, or term ID for the menu.
	 *
	 * <taxonomy>
	 * : Taxonomy of the term to be added.
	 *
	 * <term-id>
	 * : Term ID of the term to be added.
	 *
	 * [--title=<title>]
	 * : Set a custom title for the menu item.
	 *
	 * [--link=<link>]
	 * : Set a custom url for the menu item.
	 *
	 * [--description=<description>]
	 * : Set a custom description for the menu item.
	 *
	 * [--attr-title=<attr-title>]
	 * : Set a custom title attribute for the menu item.
	 *
	 * [--target=<target>]
	 * : Set a custom link target for the menu item.
	 *
	 * [--classes=<classes>]
	 * : Set a custom link classes for the menu item.
	 *
	 * [--position=<position>]
	 * : Specify the position of this menu item.
	 *
	 * [--parent-id=<parent-id>]
	 * : Make this menu item a child of another menu item.
	 *
	 * [--porcelain]
	 * : Output just the new menu item id.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin menu item add-term sidebar-menu post_tag 24
	 *     Success: Menu item added.
	 *
	 * @subcommand add-term
	 */
	public function add_term( $args, $assoc_args ) {

		$assoc_args['object'] = $args[1];
		unset( $args[1] );
		$assoc_args['object-id'] = $args[2];
		unset( $args[2] );

		if ( ! get_term_by( 'id', $assoc_args['object-id'], $assoc_args['object'] ) ) {
			FIN_CLI::error( 'Invalid term.' );
		}

		$this->add_or_update_item( 'add', 'taxonomy', $args, $assoc_args );
	}

	/**
	 * Adds a custom menu item.
	 *
	 * ## OPTIONS
	 *
	 * <menu>
	 * : The name, slug, or term ID for the menu.
	 *
	 * <title>
	 * : Title for the link.
	 *
	 * <link>
	 * : Target URL for the link.
	 *
	 * [--description=<description>]
	 * : Set a custom description for the menu item.
	 *
	 * [--attr-title=<attr-title>]
	 * : Set a custom title attribute for the menu item.
	 *
	 * [--target=<target>]
	 * : Set a custom link target for the menu item.
	 *
	 * [--classes=<classes>]
	 * : Set a custom link classes for the menu item.
	 *
	 * [--position=<position>]
	 * : Specify the position of this menu item.
	 *
	 * [--parent-id=<parent-id>]
	 * : Make this menu item a child of another menu item.
	 *
	 * [--porcelain]
	 * : Output just the new menu item id.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin menu item add-custom sidebar-menu Apple http://apple.com
	 *     Success: Menu item added.
	 *
	 * @subcommand add-custom
	 */
	public function add_custom( $args, $assoc_args ) {

		$assoc_args['title'] = $args[1];
		unset( $args[1] );
		$assoc_args['link'] = $args[2];
		unset( $args[2] );
		$this->add_or_update_item( 'add', 'custom', $args, $assoc_args );
	}

	/**
	 * Updates a menu item.
	 *
	 * ## OPTIONS
	 *
	 * <db-id>
	 * : Database ID for the menu item.
	 *
	 * [--title=<title>]
	 * : Set a custom title for the menu item.
	 *
	 * [--link=<link>]
	 * : Set a custom url for the menu item.
	 *
	 * [--description=<description>]
	 * : Set a custom description for the menu item.
	 *
	 * [--attr-title=<attr-title>]
	 * : Set a custom title attribute for the menu item.
	 *
	 * [--target=<target>]
	 * : Set a custom link target for the menu item.
	 *
	 * [--classes=<classes>]
	 * : Set a custom link classes for the menu item.
	 *
	 * [--position=<position>]
	 * : Specify the position of this menu item.
	 *
	 * [--parent-id=<parent-id>]
	 * : Make this menu item a child of another menu item.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin menu item update 45 --title=FinPress --link='http://finpress.org' --target=_blank --position=2
	 *     Success: Menu item updated.
	 *
	 * @subcommand update
	 */
	public function update( $args, $assoc_args ) {

		// Shuffle the position of these.
		$args[1] = $args[0];
		$terms   = get_the_terms( $args[1], 'nav_menu' );
		if ( $terms && ! is_fin_error( $terms ) ) {
			$args[0] = (int) $terms[0]->term_id;
		} else {
			$args[0] = 0;
		}
		$type = get_post_meta( $args[1], '_menu_item_type', true );
		$this->add_or_update_item( 'update', $type, $args, $assoc_args );
	}

	/**
	 * Deletes one or more items from a menu.
	 *
	 * ## OPTIONS
	 *
	 * <db-id>...
	 * : Database ID for the menu item(s).
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin menu item delete 45
	 *     Success: Deleted 1 of 1 menu items.
	 *
	 * @subcommand delete
	 */
	public function delete( $args, $assoc_args ) {
		global $findb;

		$count  = 0;
		$errors = 0;

		foreach ( $args as $arg ) {

			$post      = get_post( $arg );
			$menu_term = get_the_terms( $arg, 'nav_menu' );

			// @phpstan-ignore cast.int
			$parent_menu_id = (int) get_post_meta( $arg, '_menu_item_menu_item_parent', true );
			$result         = fin_delete_post( $arg, true );
			if ( ! $result ) {
				FIN_CLI::warning( "Couldn't delete menu item {$arg}." );
				++$errors;
			} else {

				if ( is_array( $menu_term ) && ! empty( $menu_term ) && $post ) {
					$this->reorder_menu_items( $menu_term[0]->term_id, $post->menu_order, -1, 0 );
				}

				if ( $parent_menu_id ) {
					$children = $findb->get_results( $findb->prepare( "SELECT post_id FROM $findb->postmeta WHERE meta_key='_menu_item_menu_item_parent' AND meta_value=%s", (int) $arg ) );
					if ( $children ) {
						$children_query = $findb->prepare( "UPDATE $findb->postmeta SET meta_value = %d WHERE meta_key = '_menu_item_menu_item_parent' AND meta_value=%s", $parent_menu_id, (int) $arg );
						// phpcs:ignore FinPress.DB.PreparedSQL.NotPrepared -- $children_query is already prepared above.
						$findb->query( $children_query );
						foreach ( $children as $child ) {
							clean_post_cache( $child );
						}
					}
				}
			}

			// phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- Will increase count for non existent menu.
			if ( false != $result ) {
				++$count;
			}
		}

		Utils\report_batch_operation_results( 'menu item', 'delete', count( $args ), $count, $errors );
	}

	/**
	 * Worker method to create new items or update existing ones.
	 */
	private function add_or_update_item( $method, $type, $args, $assoc_args ) {

		$menu            = $args[0];
		$menu_item_db_id = $args[1] ?? 0;

		$menu = fin_get_nav_menu_object( $menu );
		if ( false === $menu ) {
			FIN_CLI::error( 'Invalid menu.' );
		}

		// `url` is protected in FIN-CLI, so we use `link` instead
		$assoc_args['url'] = Utils\get_flag_value( $assoc_args, 'link' );

		// Need to persist the menu item data. See https://core.trac.finpress.org/ticket/28138
		if ( 'update' === $method ) {

			$menu_item_obj = get_post( $menu_item_db_id );

			if ( ! $menu_item_obj ) {
				FIN_CLI::error( 'Invalid menu.' );
			}

			/**
			 * @var object{title: string, url: string, description: string, object: string, object_id: int, menu_item_parent: int, attr_title: string, target: string, classes: string[], xfn: string, post_status: string, menu_order: int} $menu_item_obj
			 */
			$menu_item_obj = fin_setup_nav_menu_item( $menu_item_obj );

			// Correct the menu position if this was the first item. See https://core.trac.finpress.org/ticket/28140
			$position = ( 0 === $menu_item_obj->menu_order ) ? 1 : $menu_item_obj->menu_order;

			$default_args = [
				'position'    => $position,
				'title'       => $menu_item_obj->title,
				'url'         => $menu_item_obj->url,
				'description' => $menu_item_obj->description,
				'object'      => $menu_item_obj->object,
				'object-id'   => $menu_item_obj->object_id,
				'parent-id'   => $menu_item_obj->menu_item_parent,
				'attr-title'  => $menu_item_obj->attr_title,
				'target'      => $menu_item_obj->target,
				'classes'     => implode( ' ', $menu_item_obj->classes ), // stored in the database as array
				'xfn'         => $menu_item_obj->xfn,
				'status'      => $menu_item_obj->post_status,
			];

		} else {

			$default_args = [
				'position'    => 0,
				'title'       => '',
				'url'         => '',
				'description' => '',
				'object'      => '',
				'object-id'   => 0,
				'parent-id'   => 0,
				'attr-title'  => '',
				'target'      => '',
				'classes'     => '',
				'xfn'         => '',
				// Core oddly defaults to 'draft' for create,
				// and 'publish' for update
				// Easiest to always work with publish
				'status'      => 'publish',
			];

		}

		$menu_item_args = [];
		foreach ( $default_args as $key => $default_value ) {
			// fin_update_nav_menu_item() has a weird argument prefix
			$new_key                    = 'menu-item-' . $key;
			$menu_item_args[ $new_key ] = Utils\get_flag_value( $assoc_args, $key, $default_value );
		}

		$menu_item_args['menu-item-type'] = $type;
		$result                           = fin_update_nav_menu_item( $menu->term_id, $menu_item_db_id, $menu_item_args );

		if ( is_fin_error( $result ) ) {
			FIN_CLI::error( $result->get_error_message() );
		} elseif ( ! $result ) {
			if ( 'add' === $method ) {
				FIN_CLI::error( "Couldn't add menu item." );
			} elseif ( 'update' === $method ) {
				FIN_CLI::error( "Couldn't update menu item." );
			}
		} else {

			if ( ( 'add' === $method ) && $menu_item_args['menu-item-position'] ) {
				$this->reorder_menu_items( $menu->term_id, $menu_item_args['menu-item-position'], +1, $result );
			}

			/**
			 * Set the menu
			 *
			 * fin_update_nav_menu_item() *should* take care of this, but
			 * depends on fin_insert_post()'s "tax_input" argument, which
			 * is ignored if the user can't edit the taxonomy
			 *
			 * @see https://core.trac.finpress.org/ticket/27113
			 */
			if ( ! is_object_in_term( $result, 'nav_menu', (int) $menu->term_id ) ) {
				fin_set_object_terms( $result, [ (int) $menu->term_id ], 'nav_menu' );
			}

			if ( 'add' === $method && ! empty( $assoc_args['porcelain'] ) ) {
				FIN_CLI::line( (string) $result );
			} elseif ( 'add' === $method ) {
					FIN_CLI::success( 'Menu item added.' );
			} elseif ( 'update' === $method ) {
				FIN_CLI::success( 'Menu item updated.' );
			}
		}
	}

	/**
	 * Move block of items in one nav_menu up or down by incrementing/decrementing their menu_order field.
	 * Expects the menu items to have proper menu_orders (i.e. doesn't fix errors from previous incorrect operations).
	 *
	 * @param int $menu_id ID of the nav_menu
	 * @param int $min_position minimal menu_order to touch
	 * @param int $increment how much to change menu_order: +1 to move down, -1 to move up
	 * @param int $ignore_item_id menu item that should be ignored by the change (e.g. newly created menu item)
	 * @return int number of rows affected
	 */
	private function reorder_menu_items( $menu_id, $min_position, $increment, $ignore_item_id = 0 ) {
		global $findb;
		return $findb->query( $findb->prepare( "UPDATE $findb->posts SET `menu_order`=`menu_order`+(%d) WHERE `menu_order`>=%d AND ID IN (SELECT object_id FROM $findb->term_relationships WHERE term_taxonomy_id=%d) AND ID<>%d", (int) $increment, (int) $min_position, (int) $menu_id, (int) $ignore_item_id ) );
	}

	protected function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->obj_fields );
	}
}
