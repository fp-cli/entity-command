<?php

use FP_CLI\Formatter;
use FP_CLI\Utils;

/**
 * Retrieves information about registered taxonomies.
 *
 * See references for [built-in taxonomies](https://developer.finpress.org/themes/basics/categories-tags-custom-taxonomies/) and [custom taxonomies](https://developer.finpress.org/plugins/taxonomies/working-with-custom-taxonomies/).
 *
 * ## EXAMPLES
 *
 *     # List all taxonomies with 'post' object type.
 *     $ fp taxonomy list --object_type=post --fields=name,public
 *     +-------------+--------+
 *     | name        | public |
 *     +-------------+--------+
 *     | category    | 1      |
 *     | post_tag    | 1      |
 *     | post_format | 1      |
 *     +-------------+--------+
 *
 *     # Get capabilities of 'post_tag' taxonomy.
 *     $ fp taxonomy get post_tag --field=cap
 *     {"manage_terms":"manage_categories","edit_terms":"manage_categories","delete_terms":"manage_categories","assign_terms":"edit_posts"}
 *
 * @package fp-cli
 */
class Taxonomy_Command extends FP_CLI_Command {

	private $fields = array(
		'name',
		'label',
		'description',
		'object_type',
		'show_tagcloud',
		'hierarchical',
		'public',
	);

	/**
	 * Gets the term counts for each supplied taxonomy.
	 *
	 * @param array $taxonomies Taxonomies to fetch counts for.
	 * @return array Associative array of term counts keyed by taxonomy.
	 */
	protected function get_counts( $taxonomies ) {
		global $fpdb;

		if ( count( $taxonomies ) <= 0 ) {
			return [];
		}

		$query = $fpdb->prepare(
			"SELECT `taxonomy`, COUNT(*) AS `count`
			FROM $fpdb->term_taxonomy
			WHERE `taxonomy` IN (" . implode( ',', array_fill( 0, count( $taxonomies ), '%s' ) ) . ')
			GROUP BY `taxonomy`',
			$taxonomies
		);
		// phpcs:ignore FinPress.DB.PreparedSQL.NotPrepared -- $query is already prepared above.
		$counts = $fpdb->get_results( $query );

		// Make sure there's a count for every item.
		$counts = array_merge(
			array_fill_keys( $taxonomies, 0 ),
			fp_list_pluck( $counts, 'count', 'taxonomy' )
		);

		return $counts;
	}

	/**
	 * Lists registered taxonomies.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter by one or more fields (see get_taxonomies() first parameter for a list of available fields).
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each taxonomy.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific taxonomy fields.
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
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each term:
	 *
	 * * name
	 * * label
	 * * description
	 * * object_type
	 * * show_tagcloud
	 * * hierarchical
	 * * public
	 *
	 * These fields are optionally available:
	 *
	 * * count
	 *
	 * ## EXAMPLES
	 *
	 *     # List all taxonomies.
	 *     $ fp taxonomy list --format=csv
	 *     name,label,description,object_type,show_tagcloud,hierarchical,public
	 *     category,Categories,,post,1,1,1
	 *     post_tag,Tags,,post,1,,1
	 *     nav_menu,"Navigation Menus",,nav_menu_item,,,
	 *     link_category,"Link Categories",,link,1,,
	 *     post_format,Format,,post,,,1
	 *
	 *     # List all taxonomies with 'post' object type.
	 *     $ fp taxonomy list --object_type=post --fields=name,public
	 *     +-------------+--------+
	 *     | name        | public |
	 *     +-------------+--------+
	 *     | category    | 1      |
	 *     | post_tag    | 1      |
	 *     | post_format | 1      |
	 *     +-------------+--------+
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		// Check if it's strict mode or not.
		$strict = Utils\get_flag_value( $assoc_args, 'strict', false );

		unset( $assoc_args['strict'] );

		if ( isset( $assoc_args['object_type'] ) ) {
			$assoc_args['object_type'] = array( $assoc_args['object_type'] );
			$taxonomy_object           = $assoc_args['object_type'];
		} else {
			$taxonomy_object = get_post_types();
		}

		$fields     = $formatter->fields;
		$taxonomies = ( isset( $taxonomy_object ) && ! $strict )
			? get_object_taxonomies( $taxonomy_object, 'objects' )
			: get_taxonomies( $assoc_args, 'objects' );

		$counts = [];

		if ( count( $taxonomies ) > 0 && in_array( 'count', $fields, true ) ) {
			$counts = $this->get_counts( fp_list_pluck( $taxonomies, 'name' ) );
		}

		$taxonomies = array_map(
			function ( $taxonomy ) use ( $counts ) {
					// @phpstan-ignore assign.propertyType
					$taxonomy->object_type = implode( ', ', $taxonomy->object_type );

					// @phpstan-ignore property.notFound
					$taxonomy->count = isset( $counts[ $taxonomy->name ] ) ? $counts[ $taxonomy->name ] : 0;
					return $taxonomy;
			},
			$taxonomies
		);

		$formatter->display_items( $taxonomies );
	}

	/**
	 * Gets details about a registered taxonomy.
	 *
	 * ## OPTIONS
	 *
	 * <taxonomy>
	 * : Taxonomy slug.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole taxonomy, returns the value of a single field.
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
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for the specified taxonomy:
	 *
	 * * name
	 * * label
	 * * description
	 * * object_type
	 * * show_tagcloud
	 * * hierarchical
	 * * public
	 * * labels
	 * * cap
	 *
	 * These fields are optionally available:
	 *
	 * * count
	 *
	 * ## EXAMPLES
	 *
	 *     # Get details of `category` taxonomy.
	 *     $ fp taxonomy get category --fields=name,label,object_type
	 *     +-------------+------------+
	 *     | Field       | Value      |
	 *     +-------------+------------+
	 *     | name        | category   |
	 *     | label       | Categories |
	 *     | object_type | ["post"]   |
	 *     +-------------+------------+
	 *
	 *     # Get capabilities of 'post_tag' taxonomy.
	 *     $ fp taxonomy get post_tag --field=cap
	 *     {"manage_terms":"manage_categories","edit_terms":"manage_categories","delete_terms":"manage_categories","assign_terms":"edit_posts"}
	 */
	public function get( $args, $assoc_args ) {
		$taxonomy = get_taxonomy( $args[0] );

		if ( ! $taxonomy ) {
			FP_CLI::error( "Taxonomy {$args[0]} doesn't exist." );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$default_fields = array_merge(
				$this->fields,
				array(
					'labels',
					'cap',
				)
			);

			$assoc_args['fields'] = $default_fields;
		}

		$formatter = $this->get_formatter( $assoc_args );
		$fields    = $formatter->fields;
		$count     = 0;

		if ( in_array( 'count', $fields, true ) ) {
			$count = $this->get_counts( [ $taxonomy->name ] );
			$count = $count[ $taxonomy->name ];
		}

		$data = array(
			'name'          => $taxonomy->name,
			'label'         => $taxonomy->label,
			'description'   => $taxonomy->description,
			'object_type'   => $taxonomy->object_type,
			'show_tagcloud' => $taxonomy->show_tagcloud,
			'hierarchical'  => $taxonomy->hierarchical,
			'public'        => $taxonomy->public,
			'labels'        => $taxonomy->labels,
			'cap'           => $taxonomy->cap,
			'count'         => $count,
		);
		$formatter->display_item( $data );
	}

	private function get_formatter( &$assoc_args ) {
		return new Formatter( $assoc_args, $this->fields, 'taxonomy' );
	}
}
