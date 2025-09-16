<?php

use FIN_CLI\CommandWithDBObject;
use FIN_CLI\Utils;
use FIN_CLI\Fetchers\Signup as SignupFetcher;

/**
 * Manages signups on a multisite installation.
 *
 * ## EXAMPLES
 *
 *     # List signups.
 *     $ fin user signup list
 *     +-----------+------------+---------------------+---------------------+--------+------------------+
 *     | signup_id | user_login | user_email          | registered          | active | activation_key   |
 *     +-----------+------------+---------------------+---------------------+--------+------------------+
 *     | 1         | bobuser    | bobuser@example.com | 2024-03-13 05:46:53 | 1      | 7320b2f009266618 |
 *     | 2         | johndoe    | johndoe@example.com | 2024-03-13 06:24:44 | 0      | 9068d859186cd0b5 |
 *     +-----------+------------+---------------------+---------------------+--------+------------------+
 *
 *     # Activate signup.
 *     $ fin user signup activate 2
 *     Signup 2 activated. Password: bZFSGsfzb9xs
 *     Success: Activated 1 of 1 signups.
 *
 *     # Delete signup.
 *     $ fin user signup delete 3
 *     Signup 3 deleted.
 *     Success: Deleted 1 of 1 signups.
 *
 * @package fin-cli
 */
class Signup_Command extends CommandWithDBObject {

	protected $obj_type = 'signup';

	protected $obj_id_key = 'signup_id';

	protected $obj_fields = [
		'signup_id',
		'user_login',
		'user_email',
		'registered',
		'active',
		'activation_key',
	];

	private $fetcher;

	public function __construct() {
		$this->fetcher = new SignupFetcher();
	}

	/**
	 * Lists signups.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter the list by a specific field.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each signup.
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
	 *   - ids
	 *   - json
	 *   - count
	 *   - yaml
	 * ---
	 *
	 * [--per_page=<per_page>]
	 * : Limits the signups to the given number. Defaults to none.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each signup:
	 *
	 * * signup_id
	 * * user_login
	 * * user_email
	 * * registered
	 * * active
	 * * activation_key
	 *
	 * These fields are optionally available:
	 *
	 * * domain
	 * * path
	 * * title
	 * * activated
	 * * meta
	 *
	 * ## EXAMPLES
	 *
	 *     # List signup IDs.
	 *     $ fin user signup list --field=signup_id
	 *     1
	 *
	 *     # List all signups.
	 *     $ fin user signup list
	 *     +-----------+------------+---------------------+---------------------+--------+------------------+
	 *     | signup_id | user_login | user_email          | registered          | active | activation_key   |
	 *     +-----------+------------+---------------------+---------------------+--------+------------------+
	 *     | 1         | bobuser    | bobuser@example.com | 2024-03-13 05:46:53 | 1      | 7320b2f009266618 |
	 *     | 2         | johndoe    | johndoe@example.com | 2024-03-13 06:24:44 | 0      | 9068d859186cd0b5 |
	 *     +-----------+------------+---------------------+---------------------+--------+------------------+
	 *
	 * @subcommand list
	 *
	 * @package fin-cli
	 */
	public function list_( $args, $assoc_args ) {
		global $findb;

		if ( isset( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = explode( ',', $assoc_args['fields'] );
		} else {
			$assoc_args['fields'] = $this->obj_fields;
		}

		$signups = array();

		/**
		 * @var string|null $per_page
		 */
		$per_page = Utils\get_flag_value( $assoc_args, 'per_page' );

		$limit = $per_page ? $findb->prepare( 'LIMIT %d', (int) $per_page ) : '';

		$query = "SELECT * FROM $findb->signups {$limit}";

		// phpcs:ignore FinPress.DB.PreparedSQL.NotPrepared -- Prepared properly above.
		$results = $findb->get_results( $query, ARRAY_A );

		if ( $results ) {
			foreach ( $results as $item ) {
				// Support features like --active=0.
				foreach ( array_keys( $item ) as $field ) {
					if ( isset( $assoc_args[ $field ] ) && $assoc_args[ $field ] !== $item[ $field ] ) {
						continue 2;
					}
				}

				$signups[] = $item;
			}
		}

		$format = Utils\get_flag_value( $assoc_args, 'format', 'table' );

		$formatter = $this->get_formatter( $assoc_args );

		if ( 'ids' === $format ) {
			FIN_CLI::line( implode( ' ', fin_list_pluck( $signups, 'signup_id' ) ) );
		} else {
			$formatter->display_items( $signups );
		}
	}

	/**
	 * Gets details about a signup.
	 *
	 * ## OPTIONS
	 *
	 * <signup>
	 * : The signup ID, user login, user email, or activation key.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole signup, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get signup.
	 *     $ fin user signup get 1 --field=user_login
	 *     bobuser
	 *
	 *     # Get signup and export to JSON file.
	 *     $ fin user signup get bobuser --format=json > bobuser.json
	 *
	 * @package fin-cli
	 */
	public function get( $args, $assoc_args ) {
		$signup = $this->fetcher->get_check( $args[0] );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( (array) $signup );
		}

		$formatter = $this->get_formatter( $assoc_args );

		$formatter->display_items( array( $signup ) );
	}

	/**
	 * Activates one or more signups.
	 *
	 * ## OPTIONS
	 *
	 * <signup>...
	 * : The signup ID, user login, user email, or activation key of the signup(s) to activate.
	 *
	 * ## EXAMPLES
	 *
	 *     # Activate signup.
	 *     $ fin user signup activate 2
	 *     Signup 2 activated. Password: bZFSGsfzb9xs
	 *     Success: Activated 1 of 1 signups.
	 *
	 * @package fin-cli
	 */
	public function activate( $args, $assoc_args ) {
		$signups = $this->fetcher->get_many( $args );

		$successes = 0;
		$errors    = 0;

		foreach ( $signups as $signup ) {
			$result = finmu_activate_signup( $signup->activation_key );

			if ( is_fin_error( $result ) ) {
				FIN_CLI::warning( "Failed activating signup {$signup->signup_id}." );
				++$errors;
			} else {
				FIN_CLI::log( "Signup {$signup->signup_id} activated. Password: {$result['password']}" );
				++$successes;
			}
		}

		Utils\report_batch_operation_results( 'signup', 'activate', count( $args ), $successes, $errors );
	}

	/**
	 * Deletes one or more signups.
	 *
	 * ## OPTIONS
	 *
	 * [<signup>...]
	 * : The signup ID, user login, user email, or activation key of the signup(s) to delete.
	 *
	 * [--all]
	 * : If set, all signups will be deleted.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete signup.
	 *     $ fin user signup delete 3
	 *     Signup 3 deleted.
	 *     Success: Deleted 1 of 1 signups.
	 *
	 * @package fin-cli
	 */
	public function delete( $args, $assoc_args ) {
		$count = count( $args );

		$all = Utils\get_flag_value( $assoc_args, 'all', false );

		if ( ( 0 < $count && true === $all ) || ( 0 === $count && true !== $all ) ) {
			FIN_CLI::error( 'You need to specify either one or more signups or provide the --all flag.' );
		}

		if ( true === $all ) {
			if ( ! $this->delete_all_signups() ) {
				FIN_CLI::error( 'Error deleting signups.' );
			}

			FIN_CLI::success( 'Deleted all signups.' );
			FIN_CLI::halt( 0 );
		}

		$signups = $this->fetcher->get_many( $args );

		$successes = 0;
		$errors    = 0;

		foreach ( $signups as $signup ) {
			if ( $this->delete_signup( $signup ) ) {
				FIN_CLI::log( "Signup {$signup->signup_id} deleted." );
				++$successes;
			} else {
				FIN_CLI::warning( "Failed deleting signup {$signup->signup_id}." );
				++$errors;
			}
		}

		Utils\report_batch_operation_results( 'signup', 'delete', $count, $successes, $errors );
	}

	/**
	 * Deletes signup.
	 *
	 * @param object{signup_id: int|string} $signup
	 * @return bool True if success; otherwise false.
	 */
	private function delete_signup( $signup ) {
		global $findb;

		$signup_id = $signup->signup_id;

		$result = $findb->delete( $findb->signups, array( 'signup_id' => $signup_id ), array( '%d' ) );

		return $result ? true : false;
	}

	/**
	 * Deletes all signup.
	 *
	 * @return bool True if success; otherwise false.
	 */
	private function delete_all_signups() {
		global $findb;

		$results = $findb->query( 'DELETE FROM ' . $findb->signups );

		return $results ? true : false;
	}
}
