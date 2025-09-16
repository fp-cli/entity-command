<?php

use FIN_CLI\Formatter;
use FIN_CLI\Utils;

/**
 * Lists, creates, assigns, and deletes the active theme's navigation menus.
 *
 * See the [Navigation Menus](https://developer.finpress.org/themes/functionality/navigation-menus/) reference in the Theme Handbook.
 *
 * ## EXAMPLES
 *
 *     # Create a new menu
 *     $ fin menu create "My Menu"
 *     Success: Created menu 200.
 *
 *     # List existing menus
 *     $ fin menu list
 *     +---------+----------+----------+-----------+-------+
 *     | term_id | name     | slug     | locations | count |
 *     +---------+----------+----------+-----------+-------+
 *     | 200     | My Menu  | my-menu  |           | 0     |
 *     | 177     | Top Menu | top-menu | primary   | 7     |
 *     +---------+----------+----------+-----------+-------+
 *
 *     # Create a new menu link item
 *     $ fin menu item add-custom my-menu Apple http://apple.com --porcelain
 *     1922
 *
 *     # Assign the 'my-menu' menu to the 'primary' location
 *     $ fin menu location assign my-menu primary
 *     Success: Assigned location primary to menu my-menu.
 *
 * @package fin-cli
 */
class Menu_Command extends FIN_CLI_Command {

	protected $obj_type   = 'nav_menu';
	protected $obj_fields = [
		'term_id',
		'name',
		'slug',
		'locations',
		'count',
	];

	/**
	 * Creates a new menu.
	 *
	 * ## OPTIONS
	 *
	 * <menu-name>
	 * : A descriptive name for the menu.
	 *
	 * [--porcelain]
	 * : Output just the new menu id.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin menu create "My Menu"
	 *     Success: Created menu 200.
	 */
	public function create( $args, $assoc_args ) {

		$menu_id = fin_create_nav_menu( $args[0] );

		if ( is_fin_error( $menu_id ) ) {

			FIN_CLI::error( $menu_id->get_error_message() );

		} elseif ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {

				FIN_CLI::line( (string) $menu_id );
		} else {
			FIN_CLI::success( "Created menu {$menu_id}." );
		}
	}

	/**
	 * Deletes one or more menus.
	 *
	 * ## OPTIONS
	 *
	 * <menu>...
	 * : The name, slug, or term ID for the menu(s).
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin menu delete "My Menu"
	 *     Deleted menu 'My Menu'.
	 *     Success: Deleted 1 of 1 menus.
	 */
	public function delete( $args, $assoc_args ) {

		$count  = 0;
		$errors = 0;
		foreach ( $args as $arg ) {
			$ret = fin_delete_nav_menu( $arg );
			if ( ! $ret || is_fin_error( $ret ) ) {
				FIN_CLI::warning( "Couldn't delete menu '{$arg}'." );
				++$errors;
			} else {
				FIN_CLI::log( "Deleted menu '{$arg}'." );
				++$count;
			}
		}

		Utils\report_batch_operation_results( 'menu', 'delete', count( $args ), $count, $errors );
	}

	/**
	 * Gets a list of menus.
	 *
	 * ## OPTIONS
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
	 * These fields will be displayed by default for each menu:
	 *
	 * * term_id
	 * * name
	 * * slug
	 * * count
	 *
	 * These fields are optionally available:
	 *
	 * * term_group
	 * * term_taxonomy_id
	 * * taxonomy
	 * * description
	 * * parent
	 * * locations
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin menu list
	 *     +---------+----------+----------+-----------+-------+
	 *     | term_id | name     | slug     | locations | count |
	 *     +---------+----------+----------+-----------+-------+
	 *     | 200     | My Menu  | my-menu  |           | 0     |
	 *     | 177     | Top Menu | top-menu | primary   | 7     |
	 *     +---------+----------+----------+-----------+-------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		$menus = fin_get_nav_menus();

		$menu_locations = get_nav_menu_locations();
		foreach ( $menus as &$menu ) {

			// @phpstan-ignore property.notFound
			$menu->locations = [];
			foreach ( $menu_locations as $location => $term_id ) {

				if ( $term_id === $menu->term_id ) {
					$menu->locations[] = $location;
				}
			}

			// Normalize the data for some output formats.
			if ( ! isset( $assoc_args['format'] ) || in_array( $assoc_args['format'], [ 'csv', 'table' ], true ) ) {
				$menu->locations = implode( ',', $menu->locations );
			}
		}

		$formatter = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$ids = array_map(
				function ( $o ) {
					return $o->term_id;
				},
				$menus
			);
			$formatter->display_items( $ids );
		} else {
			$formatter->display_items( $menus );
		}
	}

	protected function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->obj_fields, $this->obj_type );
	}
}
