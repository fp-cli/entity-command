<?php

use FIN_CLI\Formatter;
use FIN_CLI\Traverser\RecursiveDataStructureTraverser;
use FIN_CLI\Utils;

/**
 * Retrieves and sets site options, including plugin and FinPress settings.
 *
 * See the [Plugin Settings API](https://developer.finpress.org/plugins/settings/settings-api/) and the [Theme Options](https://developer.finpress.org/themes/customize-api/) for more information on adding customized options.
 *
 * ## EXAMPLES
 *
 *     # Get site URL.
 *     $ fin option get siteurl
 *     http://example.com
 *
 *     # Add option.
 *     $ fin option add my_option foobar
 *     Success: Added 'my_option' option.
 *
 *     # Update option.
 *     $ fin option update my_option '{"foo": "bar"}' --format=json
 *     Success: Updated 'my_option' option.
 *
 *     # Delete option.
 *     $ fin option delete my_option
 *     Success: Deleted 'my_option' option.
 *
 * @package fin-cli
 */
class Option_Command extends FIN_CLI_Command {

	/**
	 * Gets the value for an option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Key for the option.
	 *
	 * [--format=<format>]
	 * : Get value in a particular format.
	 * ---
	 * default: var_export
	 * options:
	 *   - var_export
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get option.
	 *     $ fin option get home
	 *     http://example.com
	 *
	 *     # Get blog description.
	 *     $ fin option get blogdescription
	 *     A random blog description
	 *
	 *     # Get blog name
	 *     $ fin option get blogname
	 *     A random blog name
	 *
	 *     # Get admin email.
	 *     $ fin option get admin_email
	 *     someone@example.com
	 *
	 *     # Get option in JSON format.
	 *     $ fin option get active_plugins --format=json
	 *     {"0":"dynamically-dynamic-sidebar\/dynamically-dynamic-sidebar.php","1":"monster-widget\/monster-widget.php","2":"show-current-template\/show-current-template.php","3":"theme-check\/theme-check.php","5":"finpress-importer\/finpress-importer.php"}
	 */
	public function get( $args, $assoc_args ) {
		list( $key ) = $args;

		$value = get_option( $key );

		if ( false === $value ) {
			FIN_CLI::error( "Could not get '{$key}' option. Does it exist?" );
		}

		FIN_CLI::print_value( $value, $assoc_args );
	}

	/**
	 * Adds a new option value.
	 *
	 * Errors if the option already exists.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The name of the option to add.
	 *
	 * [<value>]
	 * : The value of the option to add. If omitted, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 *
	 * [--autoload=<autoload>]
	 * : Should this option be automatically loaded.
	 * ---
	 * options:
	 *   - 'on'
	 *   - 'off'
	 *   - 'yes'
	 *   - 'no'
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Create an option by reading a JSON file.
	 *     $ fin option add my_option --format=json < config.json
	 *     Success: Added 'my_option' option.
	 */
	public function add( $args, $assoc_args ) {
		$key = $args[0];

		$value = FIN_CLI::get_value_from_arg_or_stdin( $args, 1 );
		$value = FIN_CLI::read_value( $value, $assoc_args );

		if ( in_array( Utils\get_flag_value( $assoc_args, 'autoload' ), [ 'no', 'off' ], true ) ) {
			$autoload = 'no';
		} else {
			$autoload = 'yes';
		}

		// @phpstan-ignore argument.type
		if ( ! add_option( $key, $value, '', $autoload ) ) {
			FIN_CLI::error( "Could not add option '{$key}'. Does it already exist?" );
		} else {
			FIN_CLI::success( "Added '{$key}' option." );
		}
	}

	/**
	 * Lists options and their values.
	 *
	 * ## OPTIONS
	 *
	 * [--search=<pattern>]
	 * : Use wildcards ( * and ? ) to match option name.
	 *
	 * [--exclude=<pattern>]
	 * : Pattern to exclude. Use wildcards ( * and ? ) to match option name.
	 *
	 * [--autoload=<value>]
	 * : Match only autoload options when value is on, and only not-autoload option when off.
	 *
	 * [--transients]
	 * : List only transients. Use `--no-transients` to ignore all transients.
	 *
	 * [--unserialize]
	 * : Unserialize option values in output.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific object fields.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value. total_bytes displays the total size of matching options in bytes.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - count
	 *   - yaml
	 *   - total_bytes
	 * ---
	 *
	 * [--orderby=<fields>]
	 * : Set orderby which field.
	 * ---
	 * default: option_id
	 * options:
	 *  - option_id
	 *  - option_name
	 *  - option_value
	 * ---
	 *
	 * [--order=<order>]
	 * : Set ascending or descending order.
	 * ---
	 * default: asc
	 * options:
	 *  - asc
	 *  - desc
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * This field will be displayed by default for each matching option:
	 *
	 * * option_name
	 * * option_value
	 *
	 * These fields are optionally available:
	 *
	 * * autoload
	 * * size_bytes
	 *
	 * ## EXAMPLES
	 *
	 *     # Get the total size of all autoload options.
	 *     $ fin option list --autoload=on --format=total_bytes
	 *     33198
	 *
	 *     # Find biggest transients.
	 *     $ fin option list --search="*_transient_*" --fields=option_name,size_bytes | sort -n -k 2 | tail
	 *     option_name size_bytes
	 *     _site_transient_timeout_theme_roots 10
	 *     _site_transient_theme_roots 76
	 *     _site_transient_update_themes   181
	 *     _site_transient_update_core 808
	 *     _site_transient_update_plugins  6645
	 *
	 *     # List all options beginning with "i2f_".
	 *     $ fin option list --search="i2f_*"
	 *     +-------------+--------------+
	 *     | option_name | option_value |
	 *     +-------------+--------------+
	 *     | i2f_version | 0.1.0        |
	 *     +-------------+--------------+
	 *
	 *     # Delete all options beginning with "theme_mods_".
	 *     $ fin option list --search="theme_mods_*" --field=option_name | xargs -I % fin option delete %
	 *     Success: Deleted 'theme_mods_twentysixteen' option.
	 *     Success: Deleted 'theme_mods_twentyfifteen' option.
	 *     Success: Deleted 'theme_mods_twentyfourteen' option.
	 *
	 * @subcommand list
	 *
	 * @param string[] $args Positional arguments. Unused.
	 * @param array{search?: string, exclude: string, autoload: string, transients?: bool, unserialize?: bool, field?: string, fields: string, format: 'table'|'csv'|'json'|'yaml'|'count'|'total_bytes', orderby: 'option_id'|'option_name'|'option_value', order: 'asc'|'desc'} $assoc_args Associative arguments.
	 */
	public function list_( $args, $assoc_args ) {

		global $findb;
		$pattern        = '%';
		$exclude        = '';
		$fields         = array( 'option_name', 'option_value' );
		$size_query     = ',LENGTH(option_value) AS `size_bytes`';
		$autoload_query = '';

		if ( isset( $assoc_args['search'] ) ) {
			$pattern = self::esc_like( $assoc_args['search'] );
			// substitute wildcards
			$pattern = str_replace( '*', '%', $pattern );
			$pattern = str_replace( '?', '_', $pattern );
		}

		if ( isset( $assoc_args['exclude'] ) ) {
			$exclude = self::esc_like( $assoc_args['exclude'] );
			$exclude = str_replace( '*', '%', $exclude );
			$exclude = str_replace( '?', '_', $exclude );
		}

		if ( isset( $assoc_args['fields'] ) ) {
			$fields = explode( ',', $assoc_args['fields'] );
		}

		if ( Utils\get_flag_value( $assoc_args, 'format' ) === 'total_bytes' ) {
			$fields     = array( 'size_bytes' );
			$size_query = ',SUM(LENGTH(option_value)) AS `size_bytes`';
		}

		if ( isset( $assoc_args['autoload'] ) ) {
			$autoload = $assoc_args['autoload'];
			if ( 'on' === $autoload || 'yes' === $autoload ) {
				$autoload_query = " AND (autoload='on') OR (autoload='yes')";
			} elseif ( 'off' === $autoload || 'no' === $autoload ) {
				$autoload_query = " AND (autoload='off') OR (autoload='no')";
			} else {
				FIN_CLI::error( "Value of '--autoload' should be 'on', 'off', 'yes', or 'no'." );
			}
		}

		// By default we don't want to display transients.
		$show_transients = Utils\get_flag_value( $assoc_args, 'transients', false );

		if ( $show_transients ) {
			$transients_query = " AND option_name LIKE '\_transient\_%'
			OR option_name LIKE '\_site\_transient\_%'";
		} else {
			$transients_query = " AND option_name NOT LIKE '\_transient\_%'
			AND option_name NOT LIKE '\_site\_transient\_%'";
		}

		$where = '';
		if ( $pattern ) {
			$where .= $findb->prepare( 'WHERE `option_name` LIKE %s', $pattern );
		}

		if ( $exclude ) {
			$where .= $findb->prepare( ' AND `option_name` NOT LIKE %s', $exclude );
		}
		$where .= $autoload_query . $transients_query;

		// phpcs:disable FinPress.DB.PreparedSQL -- Hardcoded query parts without user input.
		$results = $findb->get_results(
			'SELECT `option_name`,`option_value`,`autoload`' . $size_query
			. " FROM `$findb->options` {$where}"
		);
		// phpcs:enable

		$orderby = Utils\get_flag_value( $assoc_args, 'orderby' );
		$order   = Utils\get_flag_value( $assoc_args, 'order' );

		// Sort result.
		if ( 'option_id' !== $orderby ) {
			usort(
				$results,
				function ( $a, $b ) use ( $orderby, $order ) {
					// Sort array.
					return 'asc' === $order
						? $a->$orderby <=> $b->$orderby
						: $b->$orderby <=> $a->$orderby;
				}
			);
		} elseif ( 'desc' === $order ) { // Sort by default descending.
			krsort( $results );
		}

		if ( true === Utils\get_flag_value( $assoc_args, 'unserialize', null ) ) {
			foreach ( $results as $k => &$v ) {
				if ( ! empty( $v->option_value ) ) {
					$v->option_value = maybe_unserialize( $v->option_value );
				}
			}
		}

		if ( Utils\get_flag_value( $assoc_args, 'format' ) === 'total_bytes' ) {
			FIN_CLI::line( $results[0]->size_bytes );
		} else {
			$formatter = new Formatter(
				$assoc_args,
				$fields
			);
			$formatter->display_items( $results );
		}
	}

	/**
	 * Updates an option value.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The name of the option to update.
	 *
	 * [<value>]
	 * : The new value. If omitted, the value is read from STDIN.
	 *
	 * [--autoload=<autoload>]
	 * : Requires FIN 4.2. Should this option be automatically loaded.
	 * ---
	 * options:
	 *   - 'on'
	 *   - 'off'
	 *   - 'yes'
	 *   - 'no'
	 * ---
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Update an option by reading from a file.
	 *     $ fin option update my_option < value.txt
	 *     Success: Updated 'my_option' option.
	 *
	 *     # Update one option on multiple sites using xargs.
	 *     $ fin site list --field=url | xargs -n1 -I {} sh -c 'fin --url={} option update my_option my_value'
	 *     Success: Updated 'my_option' option.
	 *     Success: Updated 'my_option' option.
	 *
	 *     # Update site blog name.
	 *     $ fin option update blogname "Random blog name"
	 *     Success: Updated 'blogname' option.
	 *
	 *     # Update site blog description.
	 *     $ fin option update blogdescription "Some random blog description"
	 *     Success: Updated 'blogdescription' option.
	 *
	 *     # Update admin email address.
	 *     $ fin option update admin_email someone@example.com
	 *     Success: Updated 'admin_email' option.
	 *
	 *     # Set the default role.
	 *     $ fin option update default_role author
	 *     Success: Updated 'default_role' option.
	 *
	 *     # Set the timezone string.
	 *     $ fin option update timezone_string "America/New_York"
	 *     Success: Updated 'timezone_string' option.
	 *
	 * @alias set
	 */
	public function update( $args, $assoc_args ) {
		$key = $args[0];

		$value = FIN_CLI::get_value_from_arg_or_stdin( $args, 1 );
		$value = FIN_CLI::read_value( $value, $assoc_args );

		$autoload = Utils\get_flag_value( $assoc_args, 'autoload' );
		if ( ! in_array( $autoload, [ 'on', 'off', 'yes', 'no' ], true ) ) {
			$autoload = null;
		}

		/**
		 * @var string $value
		 */
		$value = sanitize_option( $key, $value );

		// Sanitization FinPress normally performs when getting an option
		if ( in_array( $key, [ 'siteurl', 'home', 'category_base', 'tag_base' ], true ) ) {
			$value = untrailingslashit( $value );
		}
		$old_value = sanitize_option( $key, get_option( $key ) );

		if ( $value === $old_value && null === $autoload ) {
			FIN_CLI::success( "Value passed for '{$key}' option is unchanged." );
			// @phpstan-ignore argument.type
		} elseif ( update_option( $key, $value, $autoload ) ) {
				FIN_CLI::success( "Updated '{$key}' option." );
		} else {
			FIN_CLI::error( "Could not update option '{$key}'." );
		}
	}

	/**
	 * Gets the 'autoload' value for an option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The name of the option to get 'autoload' of.
	 *
	 * ## EXAMPLES
	 *
	 *     # Get the 'autoload' value for an option.
	 *     $ fin option get-autoload blogname
	 *     yes
	 *
	 * @subcommand get-autoload
	 */
	public function get_autoload( $args ) {
		global $findb;

		list( $option ) = $args;

		$existing = $findb->get_row(
			$findb->prepare(
				"SELECT autoload FROM $findb->options WHERE option_name=%s",
				$option
			)
		);
		if ( ! $existing ) {
			FIN_CLI::error( "Could not get '{$option}' option. Does it exist?" );

		}
		FIN_CLI::log( $existing->autoload );
	}

	/**
	 * Sets the 'autoload' value for an option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The name of the option to set 'autoload' for.
	 *
	 * <autoload>
	 * : Should this option be automatically loaded.
	 * ---
	 * options:
	 *   - 'on'
	 *   - 'off'
	 *   - 'yes'
	 *   - 'no'
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Set the 'autoload' value for an option.
	 *     $ fin option set-autoload abc_options no
	 *     Success: Updated autoload value for 'abc_options' option.
	 *
	 * @subcommand set-autoload
	 */
	public function set_autoload( $args ) {
		global $findb;

		list( $option, $autoload ) = $args;

		$previous = $findb->get_row(
			$findb->prepare(
				"SELECT autoload, option_value FROM $findb->options WHERE option_name=%s",
				$option
			)
		);
		if ( ! $previous ) {
			FIN_CLI::error( "Could not get '{$option}' option. Does it exist?" );

		}

		if ( $previous->autoload === $autoload ) {
			FIN_CLI::success( "Autoload value passed for '{$option}' option is unchanged." );
			return;
		}

		$findb->update(
			$findb->options,
			array( 'autoload' => $autoload ),
			array( 'option_name' => $option )
		);

		// Recreate cache refreshing from update_option().
		$notoptions = fin_cache_get( 'notoptions', 'options' );

		if ( is_array( $notoptions ) && isset( $notoptions[ $option ] ) ) {
			unset( $notoptions[ $option ] );
			fin_cache_set( 'notoptions', $notoptions, 'options' );
		}

		if ( ! defined( 'FIN_INSTALLING' ) ) {
			$alloptions = fin_load_alloptions( true );
			if ( isset( $alloptions[ $option ] ) ) {
				$alloptions[ $option ] = $previous->option_value;
				fin_cache_set( 'alloptions', $alloptions, 'options' );
			} else {
				fin_cache_set( $option, $previous->option_value, 'options' );
			}
		}

		FIN_CLI::success( "Updated autoload value for '{$option}' option." );
	}

	/**
	 * Deletes an option.
	 *
	 * ## OPTIONS
	 *
	 * <key>...
	 * : Key for the option.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete an option.
	 *     $ fin option delete my_option
	 *     Success: Deleted 'my_option' option.
	 *
	 *     # Delete multiple options.
	 *     $ fin option delete option_one option_two option_three
	 *     Success: Deleted 'option_one' option.
	 *     Success: Deleted 'option_two' option.
	 *     Warning: Could not delete 'option_three' option. Does it exist?
	 */
	public function delete( $args ) {
		foreach ( $args as $arg ) {
			if ( ! delete_option( $arg ) ) {
				FIN_CLI::warning( "Could not delete '{$arg}' option. Does it exist?" );
			} else {
				FIN_CLI::success( "Deleted '{$arg}' option." );
			}
		}
	}

	/**
	 * Gets a nested value from an option.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The option name.
	 *
	 * <key-path>...
	 * : The name(s) of the keys within the value to locate the value to pluck.
	 *
	 * [--format=<format>]
	 * : The output format of the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 *   - yaml
	 * ---
	 */
	public function pluck( $args, $assoc_args ) {
		list( $key ) = $args;

		$value = get_option( $key );

		if ( false === $value ) {
			FIN_CLI::halt( 1 );
		}

		$key_path = array_map(
			function ( $key ) {
				if ( is_numeric( $key ) && ( (string) intval( $key ) === $key ) ) {
					return (int) $key;
				}
					return $key;
			},
			array_slice( $args, 1 )
		);

		$traverser = new RecursiveDataStructureTraverser( $value );

		try {
			$value = $traverser->get( $key_path );
		} catch ( Exception $exception ) {
			die( 1 );
		}

		FIN_CLI::print_value( $value, $assoc_args );
	}

	/**
	 * Updates a nested value in an option.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : Patch action to perform.
	 * ---
	 * options:
	 *   - insert
	 *   - update
	 *   - delete
	 * ---
	 *
	 * <key>
	 * : The option name.
	 *
	 * <key-path>...
	 * : The name(s) of the keys within the value to locate the value to patch.
	 *
	 * [<value>]
	 * : The new value. If omitted, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Add 'bar' to the 'foo' key on an option with name 'option_name'
	 *     $ fin option patch insert option_name foo bar
	 *     Success: Updated 'option_name' option.
	 *
	 *     # Update the value of 'foo' key to 'new' on an option with name 'option_name'
	 *     $ fin option patch update option_name foo new
	 *     Success: Updated 'option_name' option.
	 *
	 *     # Set nested value of 'bar' key to value we have in the patch file on an option with name 'option_name'.
	 *     $ fin option patch update option_name foo bar < patch
	 *     Success: Updated 'option_name' option.
	 *
	 *     # Update the value for the key 'not-a-key' which is not exist on an option with name 'option_name'.
	 *     $ fin option patch update option_name foo not-a-key new-value
	 *     Error: No data exists for key "not-a-key"
	 *
	 *     # Update the value for the key 'foo' without passing value on an option with name 'option_name'.
	 *     $ fin option patch update option_name foo
	 *     Error: Please provide value to update.
	 *
	 *     # Delete the nested key 'bar' under 'foo' key on an option with name 'option_name'.
	 *     $ fin option patch delete option_name foo bar
	 *     Success: Updated 'option_name' option.
	 */
	public function patch( $args, $assoc_args ) {
		list( $action, $key ) = $args;
		$key_path             = array_map(
			function ( $key ) {
				if ( is_numeric( $key ) && ( (string) intval( $key ) === $key ) ) {
					return (int) $key;
				}
					return $key;
			},
			array_slice( $args, 2 )
		);

		if ( 'delete' === $action ) {
			$patch_value = null;
		} else {
			$stdin_value = Utils\has_stdin()
				? trim( FIN_CLI::get_value_from_arg_or_stdin( $args, -1 ) )
				: null;

			if ( ! empty( $stdin_value ) ) {
				$patch_value = FIN_CLI::read_value( $stdin_value, $assoc_args );
			} elseif ( count( $key_path ) > 1 ) {
					$patch_value = FIN_CLI::read_value( array_pop( $key_path ), $assoc_args );
			} else {
				$patch_value = null;
			}

			if ( null === $patch_value ) {
				FIN_CLI::error( 'Please provide value to update.' );
			}
		}

		/* Need to make a copy of $current_value here as it is modified by reference */
		$old_value     = sanitize_option( $key, get_option( $key ) );
		$current_value = $old_value;
		if ( is_object( $current_value ) ) {
			$old_value = clone $current_value;
		}

		$traverser = new RecursiveDataStructureTraverser( $current_value );

		try {
			$traverser->$action( $key_path, $patch_value );
		} catch ( Exception $exception ) {
			FIN_CLI::error( $exception->getMessage() );
		}

		$patched_value = sanitize_option( $key, $traverser->value() );

		if ( $patched_value === $old_value ) {
			FIN_CLI::success( "Value passed for '{$key}' option is unchanged." );
		} elseif ( update_option( $key, $patched_value ) ) {
				FIN_CLI::success( "Updated '{$key}' option." );
		} else {
			FIN_CLI::error( "Could not update option '{$key}'." );
		}
	}

	private static function esc_like( $old ) {
		/**
		 * @var \findb $findb
		 */
		global $findb;

		return $findb->esc_like( $old );
	}
}
