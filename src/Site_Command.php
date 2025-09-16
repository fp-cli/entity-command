<?php

use FIN_CLI\CommandWithDBObject;
use FIN_CLI\ExitException;
use FIN_CLI\Fetchers\Site as SiteFetcher;
use FIN_CLI\Iterators\Query as QueryIterator;
use FIN_CLI\Iterators\Table as TableIterator;
use FIN_CLI\Utils;
use FIN_CLI\Formatter;
use FIN_CLI\Fetchers\User as UserFetcher;

/**
 * Creates, deletes, empties, moderates, and lists one or more sites on a multisite installation.
 *
 * ## EXAMPLES
 *
 *     # Create site
 *     $ fin site create --slug=example
 *     Success: Site 3 created: www.example.com/example/
 *
 *     # Output a simple list of site URLs
 *     $ fin site list --field=url
 *     http://www.example.com/
 *     http://www.example.com/subdir/
 *
 *     # Delete site
 *     $ fin site delete 123
 *     Are you sure you want to delete the 'http://www.example.com/example' site? [y/n] y
 *     Success: The site at 'http://www.example.com/example' was deleted.
 *
 * @package fin-cli
 *
 * @phpstan-type UserSite object{userblog_id: int, blogname: string, domain: string, path: string, site_id: int, siteurl: string, archived: int, spam: int, deleted: int}
 */
class Site_Command extends CommandWithDBObject {

	protected $obj_type   = 'site';
	protected $obj_id_key = 'blog_id';

	private $fetcher;

	public function __construct() {
		$this->fetcher = new SiteFetcher();
	}

	/**
	 * Delete comments.
	 */
	private function empty_comments() {
		global $findb;

		// Empty comments and comment cache
		$comment_ids = $findb->get_col( "SELECT comment_ID FROM $findb->comments" );
		foreach ( $comment_ids as $comment_id ) {
			fin_cache_delete( $comment_id, 'comment' );
			fin_cache_delete( $comment_id, 'comment_meta' );
		}
		$findb->query( "TRUNCATE TABLE $findb->comments" );
		$findb->query( "TRUNCATE TABLE $findb->commentmeta" );
	}

	/**
	 * Delete all posts.
	 */
	private function empty_posts() {
		global $findb;

		// Empty posts and post cache
		$posts_query = "SELECT ID FROM $findb->posts";
		$posts       = new QueryIterator( $posts_query, 10000 );

		$taxonomies = get_taxonomies();

		while ( $posts->valid() ) {
			/**
			 * @var object{ID: int} $post
			 */
			$post = $posts->current();

			$post_id = $post->ID;

			fin_cache_delete( $post_id, 'posts' );
			fin_cache_delete( $post_id, 'post_meta' );
			foreach ( $taxonomies as $taxonomy ) {
				fin_cache_delete( $post_id, "{$taxonomy}_relationships" );
			}
			fin_cache_delete( $findb->blogid . '-' . $post_id, 'global-posts' );

			$posts->next();
		}
		$findb->query( "TRUNCATE TABLE $findb->posts" );
		$findb->query( "TRUNCATE TABLE $findb->postmeta" );
	}

	/**
	 * Delete terms, taxonomies, and tax relationships.
	 */
	private function empty_taxonomies() {
		/**
		 * @var \findb $findb
		 */
		global $findb;

		// Empty taxonomies and terms
		$terms      = $findb->get_results( "SELECT term_id, taxonomy FROM $findb->term_taxonomy" );
		$taxonomies = [];
		foreach ( (array) $terms as $term ) {
			$taxonomies[] = $term->taxonomy;
			fin_cache_delete( $term->term_id, $term->taxonomy );
		}

		$taxonomies = array_unique( $taxonomies );
		$cleaned    = [];
		foreach ( $taxonomies as $taxonomy ) {
			if ( isset( $cleaned[ $taxonomy ] ) ) {
				continue;
			}
			$cleaned[ $taxonomy ] = true;

			fin_cache_delete( 'all_ids', $taxonomy );
			fin_cache_delete( 'get', $taxonomy );
			delete_option( "{$taxonomy}_children" );
		}
		$findb->query( "TRUNCATE TABLE $findb->terms" );
		$findb->query( "TRUNCATE TABLE $findb->term_taxonomy" );
		$findb->query( "TRUNCATE TABLE $findb->term_relationships" );
		if ( ! empty( $findb->termmeta ) ) {
			$findb->query( "TRUNCATE TABLE $findb->termmeta" );
		}
	}

	/**
	 * Delete all links, link_category terms, and related cache.
	 */
	private function empty_links() {
		global $findb;

		// Remove links and related cached data.
		$links_query = "SELECT link_id FROM {$findb->links}";
		$links       = new QueryIterator( $links_query, 10000 );

		// Remove bookmarks cache group.
		fin_cache_delete( 'get_bookmarks', 'bookmark' );

		while ( $links->valid() ) {
			/**
			 * @var object{link_id: int} $link
			 */
			$link = $links->current();

			$link_id = $link->link_id;

			// Remove cache for the link.
			fin_delete_object_term_relationships( $link_id, 'link_category' );
			fin_cache_delete( $link_id, 'bookmark' );
			clean_object_term_cache( $link_id, 'link' );

			$links->next();
		}

		// Empty the table once link related cache and term is removed.
		$findb->query( "TRUNCATE TABLE {$findb->links}" );
	}

	/**
	 * Insert default terms.
	 */
	private function insert_default_terms() {
		global $findb;

		// Default category
		$cat_name = __( 'Uncategorized' );

		/* translators: Default category slug */
		$cat_slug = sanitize_title( _x( 'Uncategorized', 'Default category slug' ) );

		// @phpstan-ignore function.deprecated
		if ( global_terms_enabled() ) { // phpcs:ignore FinPress.FIN.DeprecatedFunctions.global_terms_enabledFound -- Required for backwards compatibility.
			$cat_id = $findb->get_var( $findb->prepare( "SELECT cat_ID FROM {$findb->sitecategories} WHERE category_nicename = %s", $cat_slug ) );
			if ( null === $cat_id ) {
				$findb->insert(
					$findb->sitecategories,
					[
						'cat_ID'            => 0,
						'cat_name'          => $cat_name,
						'category_nicename' => $cat_slug,
						'last_updated'      => current_time(
							'mysql',
							true
						),
					]
				);
				$cat_id = $findb->insert_id;
			}
			update_option( 'default_category', $cat_id );
		} else {
			$cat_id = 1;
		}

		$findb->insert(
			$findb->terms,
			[
				'term_id'    => $cat_id,
				'name'       => $cat_name,
				'slug'       => $cat_slug,
				'term_group' => 0,
			]
		);
		$findb->insert(
			$findb->term_taxonomy,
			[
				'term_id'     => $cat_id,
				'taxonomy'    => 'category',
				'description' => '',
				'parent'      => 0,
				'count'       => 0,
			]
		);
	}

	/**
	 * Reset option values to default.
	 */
	private function reset_options() {
		// Reset Privacy Policy value to prevent error.
		update_option( 'fin_page_for_privacy_policy', 0 );

		// Reset sticky posts option.
		update_option( 'sticky_posts', [] );
	}

	/**
	 * Empties a site of its content (posts, comments, terms, and meta).
	 *
	 * Truncates posts, comments, and terms tables to empty a site of its
	 * content. Doesn't affect site configuration (options) or users.
	 *
	 * If running a persistent object cache, make sure to flush the cache
	 * after emptying the site, as the cache values will be invalid otherwise.
	 *
	 * To also empty custom database tables, you'll need to hook into command
	 * execution:
	 *
	 * ```
	 * FIN_CLI::add_hook( 'after_invoke:site empty', function(){
	 *     global $findb;
	 *     foreach( array( 'p2p', 'p2pmeta' ) as $table ) {
	 *         $table = $findb->$table;
	 *         $findb->query( "TRUNCATE $table" );
	 *     }
	 * });
	 * ```
	 *
	 * ## OPTIONS
	 *
	 * [--uploads]
	 * : Also delete *all* files in the site's uploads directory.
	 *
	 * [--yes]
	 * : Proceed to empty the site without a confirmation prompt.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site empty
	 *     Are you sure you want to empty the site at http://www.example.com of all posts, links, comments, and terms? [y/n] y
	 *     Success: The site at 'http://www.example.com' was emptied.
	 *
	 * @subcommand empty
	 */
	public function empty_( $args, $assoc_args ) {

		$upload_message = '';
		if ( Utils\get_flag_value( $assoc_args, 'uploads' ) ) {
			$upload_message = ', and delete its uploads directory';
		}

		FIN_CLI::confirm( "Are you sure you want to empty the site at '" . site_url() . "' of all posts, links, comments, and terms" . $upload_message . '?', $assoc_args );

		$this->empty_posts();
		$this->empty_links();
		$this->empty_comments();
		$this->empty_taxonomies();
		$this->insert_default_terms();
		$this->reset_options();

		if ( ! empty( $upload_message ) ) {
			$upload_dir = fin_upload_dir();
			$files      = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $upload_dir['basedir'], RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);

			$files_to_unlink       = [];
			$directories_to_delete = [];
			$is_main_site          = is_main_site();

			/**
			 * @var \SplFileInfo $fileinfo
			 */
			foreach ( $files as $fileinfo ) {
				$realpath = $fileinfo->getRealPath();
				// Don't clobber subsites when operating on the main site
				if ( $is_main_site && false !== stripos( $realpath, '/sites/' ) ) {
					continue;
				}
				if ( $fileinfo->isDir() ) {
					$directories_to_delete[] = $realpath;
				} else {
					$files_to_unlink[] = $realpath;
				}
			}
			foreach ( $files_to_unlink as $file ) {
				unlink( $file );
			}
			foreach ( $directories_to_delete as $directory ) {
				// Directory could be main sites directory '/sites' which may be non-empty.
				@rmdir( $directory );
			}
			// May be non-empty if '/sites' still around.
			@rmdir( $upload_dir['basedir'] );
		}

		FIN_CLI::success( "The site at '" . site_url() . "' was emptied." );
	}

	/**
	 * Deletes a site in a multisite installation.
	 *
	 * ## OPTIONS
	 *
	 * [<site-id>]
	 * : The id of the site to delete. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be deleted. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * [--yes]
	 * : Answer yes to the confirmation message.
	 *
	 * [--keep-tables]
	 * : Delete the blog from the list, but don't drop its tables.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site delete 123
	 *     Are you sure you want to delete the http://www.example.com/example site? [y/n] y
	 *     Success: The site at 'http://www.example.com/example' was deleted.
	 */
	public function delete( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			FIN_CLI::error( 'This is not a multisite installation.' );
		}

		if ( isset( $assoc_args['slug'] ) ) {
			$blog_id = get_id_from_blogname( $assoc_args['slug'] );
			if ( null === $blog_id ) {
				FIN_CLI::error( sprintf( 'Could not find site with slug \'%s\'.', $assoc_args['slug'] ) );
			}
			$blog = get_blog_details( $blog_id );
		} else {
			if ( empty( $args ) ) {
				FIN_CLI::error( 'Need to specify a blog id.' );
			}

			$blog_id = $args[0];

			if ( is_main_site( $blog_id ) ) {
				FIN_CLI::error( 'You cannot delete the root site.' );
			}

			$blog = get_blog_details( $blog_id );
		}

		if ( ! $blog ) {
			FIN_CLI::error( 'Site not found.' );
		}

		$site_url = trailingslashit( $blog->siteurl );

		FIN_CLI::confirm( "Are you sure you want to delete the '{$site_url}' site?", $assoc_args );

		finmu_delete_blog( (int) $blog->blog_id, ! Utils\get_flag_value( $assoc_args, 'keep-tables' ) );

		FIN_CLI::success( "The site at '{$site_url}' was deleted." );
	}

	/**
	 * Creates a site in a multisite installation.
	 *
	 * ## OPTIONS
	 *
	 * --slug=<slug>
	 * : Path for the new site. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * [--title=<title>]
	 * : Title of the new site. Default: prettified slug.
	 *
	 * [--email=<email>]
	 * : Email for admin user. User will be created if none exists. Assignment to super admin if not included.
	 *
	 * [--network_id=<network-id>]
	 * : Network to associate new site with. Defaults to current network (typically 1).
	 *
	 * [--private]
	 * : If set, the new site will be non-public (not indexed)
	 *
	 * [--porcelain]
	 * : If set, only the site id will be output on success.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site create --slug=example
	 *     Success: Site 3 created: http://www.example.com/example/
	 */
	public function create( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			FIN_CLI::error( 'This is not a multisite installation.' );
		}

		global $findb, $current_site;

		$base = $assoc_args['slug'];

		/**
		 * @var string $title
		 */
		$title = Utils\get_flag_value( $assoc_args, 'title', ucfirst( $base ) );

		$email = empty( $assoc_args['email'] ) ? '' : $assoc_args['email'];

		// Network
		if ( ! empty( $assoc_args['network_id'] ) ) {
			$network = $this->get_network( $assoc_args['network_id'] );
			if ( false === $network ) {
				FIN_CLI::error( "Network with id {$assoc_args['network_id']} does not exist." );
			}
		} else {
			$network = $current_site;
		}

		$public = ! Utils\get_flag_value( $assoc_args, 'private' );

		// Sanitize
		if ( preg_match( '|^([a-zA-Z0-9-])+$|', $base ) ) {
			$base = strtolower( $base );
		}

		// If not a subdomain install, make sure the domain isn't a reserved word
		if ( ! is_subdomain_install() ) {
			$subdirectory_reserved_names = $this->get_subdirectory_reserved_names();
			if ( in_array( $base, $subdirectory_reserved_names, true ) ) {
				FIN_CLI::error( 'The following words are reserved and cannot be used as blog names: ' . implode( ', ', $subdirectory_reserved_names ) );
			}
		}

		// Check for valid email, if not, use the first super admin found
		// Probably a more efficient way to do this so we dont query for the
		// User twice if super admin
		$email = sanitize_email( $email );
		if ( empty( $email ) || ! is_email( $email ) ) {
			$super_admins = get_super_admins();
			$email        = '';
			if ( ! empty( $super_admins ) && is_array( $super_admins ) ) {
				// Just get the first one
				$super_login = reset( $super_admins );
				$super_user  = get_user_by( 'login', $super_login );
				if ( $super_user ) {
					$email = $super_user->user_email;
				}
			}
		}

		if ( is_subdomain_install() ) {
			$newdomain = $base . '.' . preg_replace( '|^www\.|', '', $current_site->domain );
			$path      = $current_site->path;
		} else {
			$newdomain = $current_site->domain;
			$path      = $current_site->path . $base . '/';
		}

		$user_id = email_exists( $email );
		if ( ! $user_id ) { // Create a new user with a random password
			$password = fin_generate_password( 24, false );
			$user_id  = finmu_create_user( $base, $password, $email );
			if ( false === $user_id ) {
				FIN_CLI::error( "Can't create user." );
			} else {
				User_Command::fin_new_user_notification( $user_id, $password );
			}
		}

		$findb->hide_errors();
		$title = fin_slash( $title );
		$id    = finmu_create_blog( $newdomain, $path, $title, $user_id, [ 'public' => $public ], $network->id );
		$findb->show_errors();
		if ( ! is_fin_error( $id ) ) {
			if ( ! is_super_admin( $user_id ) && ! get_user_option( 'primary_blog', $user_id ) ) {
				update_user_option( $user_id, 'primary_blog', $id, true );
			}
		} else {
			FIN_CLI::error( $id->get_error_message() );
		}

		if ( Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			FIN_CLI::line( (string) $id );
		} else {
			$site_url = trailingslashit( get_site_url( $id ) );
			FIN_CLI::success( "Site {$id} created: {$site_url}" );
		}
	}

	/**
	 * Generate some sites.
	 *
	 * Creates a specified number of new sites.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : How many sites to generates?
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--slug=<slug>]
	 * : Path for the new site. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * [--email=<email>]
	 * : Email for admin user. User will be created if none exists. Assignment to super admin if not included.
	 *
	 * [--network_id=<network-id>]
	 * : Network to associate new site with. Defaults to current network (typically 1).
	 *
	 * [--private]
	 * : If set, the new site will be non-public (not indexed)
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: progress
	 * options:
	 *  - progress
	 *  - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *    # Generate 10 sites.
	 *    $ fin site generate --count=10
	 *    Generating sites  100% [================================================] 0:01 / 0:04
	 */
	public function generate( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			FIN_CLI::error( 'This is not a multisite installation.' );
		}

		global $findb, $current_site;

		$defaults = [
			'count'      => 100,
			'email'      => '',
			'network_id' => 1,
			'slug'       => 'site',
		];

		$assoc_args = array_merge( $defaults, $assoc_args );

		// Base.
		$base = $assoc_args['slug'];
		if ( preg_match( '|^([a-zA-Z0-9-])+$|', $base ) ) {
			$base = strtolower( $base );
		}

		$is_subdomain_install = is_subdomain_install();
		// If not a subdomain install, make sure the domain isn't a reserved word
		if ( ! $is_subdomain_install ) {
			$subdirectory_reserved_names = $this->get_subdirectory_reserved_names();
			if ( in_array( $base, $subdirectory_reserved_names, true ) ) {
				FIN_CLI::error( 'The following words are reserved and cannot be used as blog names: ' . implode( ', ', $subdirectory_reserved_names ) );
			}
		}

		// Network.
		if ( ! empty( $assoc_args['network_id'] ) ) {
			$network = $this->get_network( $assoc_args['network_id'] );
			if ( false === $network ) {
				FIN_CLI::error( "Network with id {$assoc_args['network_id']} does not exist." );
			}
		} else {
			$network = $current_site;
		}

		// Public.
		$public = ! Utils\get_flag_value( $assoc_args, 'private' );

		// Limit.
		$limit = $assoc_args['count'];

		// Email.
		$email = sanitize_email( $assoc_args['email'] );
		if ( empty( $email ) || ! is_email( $email ) ) {
			$super_admins = get_super_admins();
			$email        = '';
			if ( ! empty( $super_admins ) && is_array( $super_admins ) ) {
				$super_login = reset( $super_admins );
				$super_user  = get_user_by( 'login', $super_login );
				if ( $super_user ) {
					$email = $super_user->user_email;
				}
			}
		}

		$user_id = email_exists( $email );
		if ( ! $user_id ) {
			$password = fin_generate_password( 24, false );
			$user_id  = finmu_create_user( $base . '-admin', $password, $email );

			if ( false === $user_id ) {
				FIN_CLI::error( "Can't create user." );
			} else {
				User_Command::fin_new_user_notification( $user_id, $password );
			}
		}

		$format = Utils\get_flag_value( $assoc_args, 'format', 'progress' );

		$notify = false;
		if ( 'progress' === $format ) {
			$notify = Utils\make_progress_bar( 'Generating sites', $limit );
		}

		for ( $index = 1; $index <= $limit; $index++ ) {
			$current_base = $base . $index;
			$title        = ucfirst( $base ) . ' ' . $index;

			if ( $is_subdomain_install ) {
				$new_domain = $current_base . '.' . preg_replace( '|^www\.|', '', $network->domain );
				$path       = $network->path;
			} else {
				$new_domain = $network->domain;
				$path       = $network->path . $current_base . '/';
			}

			$findb->hide_errors();
			$title = fin_slash( $title );
			$id    = finmu_create_blog( $new_domain, $path, $title, $user_id, [ 'public' => $public ], $network->id );
			$findb->show_errors();
			if ( ! is_fin_error( $id ) ) {
				if ( ! is_super_admin( $user_id ) && ! get_user_option( 'primary_blog', $user_id ) ) {
					update_user_option( $user_id, 'primary_blog', $id, true );
				}
			} else {
				FIN_CLI::error( $id->get_error_message() );
			}

			if ( 'progress' === $format ) {
				$notify->tick();
			} else {
				echo $id;
				if ( $index < $limit - 1 ) {
					echo ' ';
				}
			}
		}

		if ( 'progress' === $format ) {
			$notify->finish();
		}
	}

	/**
	 * Retrieves a list of reserved site on a sub-directory Multisite installation.
	 *
	 * Works on older FinPress versions where get_subdirectory_reserved_names() does not exist.
	 *
	 * @return string[] Array of reserved names.
	 */
	private function get_subdirectory_reserved_names() {
		if ( function_exists( 'get_subdirectory_reserved_names' ) ) {
			return get_subdirectory_reserved_names();
		}

		$names = array(
			'page',
			'comments',
			'blog',
			'files',
			'feed',
			'fin-admin',
			'fin-content',
			'fin-includes',
			'fin-json',
			'embed',
		);

		// phpcs:ignore FinPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Calling FinPress native hook.
		return apply_filters( 'subdirectory_reserved_names', $names );
	}

	/**
	 * Gets network data for a given id.
	 *
	 * @param int     $network_id
	 * @return bool|array False if no network found with given id, array otherwise
	 */
	private function get_network( $network_id ) {
		global $findb;

		// Load network data
		$networks = $findb->get_results(
			$findb->prepare(
				"SELECT * FROM $findb->site WHERE id = %d",
				$network_id
			)
		);

		if ( ! empty( $networks ) ) {
			// Only care about domain and path which are set here
			return $networks[0];
		}

		return false;
	}

	/**
	 * Lists all sites in a multisite installation.
	 *
	 * ## OPTIONS
	 *
	 * [--network=<id>]
	 * : The network to which the sites belong.
	 *
	 * [--<field>=<value>]
	 * : Filter by one or more fields (see "Available Fields" section). However,
	 * 'url' isn't an available filter, as it comes from 'home' in fin_options.
	 *
	 * [--site__in=<value>]
	 * : Only list the sites with these blog_id values (comma-separated).
	 *
	 * [--site_user=<value>]
	 * : Only list the sites with this user.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each site.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated list of fields to show.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - count
	 *   - ids
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each site:
	 *
	 * * blog_id
	 * * url
	 * * last_updated
	 * * registered
	 *
	 * These fields are optionally available:
	 *
	 * * site_id
	 * * domain
	 * * path
	 * * public
	 * * archived
	 * * mature
	 * * spam
	 * * deleted
	 * * lang_id
	 *
	 * ## EXAMPLES
	 *
	 *     # Output a simple list of site URLs
	 *     $ fin site list --field=url
	 *     http://www.example.com/
	 *     http://www.example.com/subdir/
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {
		if ( ! is_multisite() ) {
			FIN_CLI::error( 'This is not a multisite installation.' );
		}

		global $findb;

		if ( isset( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = preg_split( '/,[ \t]*/', $assoc_args['fields'] );
		}

		$defaults   = [
			'format' => 'table',
			'fields' => [ 'blog_id', 'url', 'last_updated', 'registered' ],
		];
		$assoc_args = array_merge( $defaults, $assoc_args );

		$where  = [];
		$append = '';

		$site_cols = [ 'blog_id', 'last_updated', 'registered', 'site_id', 'domain', 'path', 'public', 'archived', 'mature', 'spam', 'deleted', 'lang_id' ];
		foreach ( $site_cols as $col ) {
			if ( isset( $assoc_args[ $col ] ) ) {
				$where[ $col ] = $assoc_args[ $col ];
			}
		}

		if ( isset( $assoc_args['site__in'] ) ) {
			$where['blog_id'] = explode( ',', $assoc_args['site__in'] );
			$append           = 'ORDER BY FIELD( blog_id, ' . implode( ',', array_map( 'intval', $where['blog_id'] ) ) . ' )';
		}

		if ( isset( $assoc_args['network'] ) ) {
			$where['site_id'] = $assoc_args['network'];
		}

		if ( isset( $assoc_args['site_user'] ) ) {
			$user = ( new UserFetcher() )->get_check( $assoc_args['site_user'] );

			if ( $user ) {
				/**
				 * @phpstan-var UserSite[] $blogs
				 */
				$blogs = get_blogs_of_user( $user->ID );

				foreach ( $blogs as $blog ) {
					$where['blog_id'][] = $blog->userblog_id;
				}
			}

			if ( ! isset( $where['blog_id'] ) || empty( $where['blog_id'] ) ) {
				$formatter = new Formatter( $assoc_args, [], 'site' );
				$formatter->display_items( [] );
				return;
			}

			$append = 'ORDER BY FIELD( blog_id, ' . implode( ',', array_map( 'intval', $where['blog_id'] ) ) . ' )';
		}

		$iterator_args = [
			'table'  => $findb->blogs,
			'where'  => $where,
			'append' => $append,
		];

		$iterator = new TableIterator( $iterator_args );

		/**
		 * @var iterable $iterator
		 */
		$iterator = Utils\iterator_map(
			$iterator,
			function ( $blog ) {
				$blog->url = trailingslashit( get_home_url( $blog->blog_id ) );
				return $blog;
			}
		);

		if ( ! empty( $assoc_args['format'] ) && 'ids' === $assoc_args['format'] ) {
			$sites     = iterator_to_array( $iterator );
			$ids       = fin_list_pluck( $sites, 'blog_id' );
			$formatter = new Formatter( $assoc_args, null, 'site' );
			$formatter->display_items( $ids );
		} else {
			$formatter = new Formatter( $assoc_args, null, 'site' );
			$formatter->display_items( $iterator );
		}
	}

	/**
	 * Archives one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to archive. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to archive. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site archive 123
	 *     Success: Site 123 archived.
	 *
	 *     $ fin site archive --slug=demo
	 *     Success: Site 123 archived.
	 */
	public function archive( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'archived', 1 );
	}

	/**
	 * Unarchives one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to unarchive. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to unarchive. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site unarchive 123
	 *     Success: Site 123 unarchived.
	 *
	 *     $ fin site unarchive --slug=demo
	 *     Success: Site 123 unarchived.
	 */
	public function unarchive( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'archived', 0 );
	}

	/**
	 * Activates one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to activate. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be activated. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site activate 123
	 *     Success: Site 123 activated.
	 *
	 *      $ fin site activate --slug=demo
	 *      Success: Site 123 marked as activated.
	 */
	public function activate( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'deleted', 0 );
	}

	/**
	 * Deactivates one or more sites.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to deactivate. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be deactivated. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site deactivate 123
	 *     Success: Site 123 deactivated.
	 *
	 *     $ fin site deactivate --slug=demo
	 *     Success: Site 123 deactivated.
	 */
	public function deactivate( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'deleted', 1 );
	}

	/**
	 * Marks one or more sites as spam.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to be marked as spam. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be marked as spam. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site spam 123
	 *     Success: Site 123 marked as spam.
	 */
	public function spam( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'spam', 1 );
	}

	/**
	 * Removes one or more sites from spam.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to remove from spam. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be removed from spam. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site unspam 123
	 *     Success: Site 123 removed from spam.
	 *
	 * @subcommand unspam
	 */
	public function unspam( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'spam', 0 );
	}

	/**
	 * Sets one or more sites as mature.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to set as mature. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be set as mature. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site mature 123
	 *     Success: Site 123 marked as mature.
	 *
	 *     $ fin site mature --slug=demo
	 *     Success: Site 123 marked as mature.
	 */
	public function mature( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'mature', 1 );
	}

	/**
	 * Sets one or more sites as immature.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to set as unmature. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be set as unmature. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site unmature 123
	 *     Success: Site 123 marked as unmature.
	 *
	 *     $ fin site unmature --slug=demo
	 *     Success: Site 123 marked as unmature.
	 */
	public function unmature( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'mature', 0 );
	}

	/**
	 * Sets one or more sites as public.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to set as public. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be set as public. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site public 123
	 *     Success: Site 123 marked as public.
	 *
	 *      $ fin site public --slug=demo
	 *      Success: Site 123 marked as public.
	 *
	 * @subcommand public
	 */
	public function set_public( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'public', 1 );
	}

	/**
	 * Sets one or more sites as private.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : One or more IDs of sites to set as private. If not provided, you must set the --slug parameter.
	 *
	 * [--slug=<slug>]
	 * : Path of the site to be set as private. Subdomain on subdomain installs, directory on subdirectory installs.
	 *
	 * ## EXAMPLES
	 *
	 *     $ fin site private 123
	 *     Success: Site 123 marked as private.
	 *
	 *     $ fin site private --slug=demo
	 *     Success: Site 123 marked as private.
	 *
	 * @subcommand private
	 */
	public function set_private( $args, $assoc_args ) {
		if ( ! $this->check_site_ids_and_slug( $args, $assoc_args ) ) {
			return;
		}

		$ids = $this->get_sites_ids( $args, $assoc_args );

		$this->update_site_status( $ids, 'public', 0 );
	}

	private function update_site_status( $ids, $pref, $value ) {
		$value = (int) $value;

		$action = 'updated';

		switch ( $pref ) {
			case 'archived':
				$action = $value ? 'archived' : 'unarchived';
				break;
			case 'deleted':
				$action = $value ? 'deactivated' : 'activated';
				break;
			case 'mature':
				$action = $value ? 'marked as mature' : 'marked as unmature';
				break;
			case 'public':
				$action = $value ? 'marked as public' : 'marked as private';
				break;
			case 'spam':
				$action = $value ? 'marked as spam' : 'removed from spam';
				break;
		}

		foreach ( $ids as $site_id ) {
			$site = $this->fetcher->get_check( $site_id );

			if ( is_main_site( $site->blog_id ) ) {
				FIN_CLI::warning( 'You are not allowed to change the main site.' );
				continue;
			}

			$old_value = (int) get_blog_status( $site->blog_id, $pref );

			if ( $value === $old_value ) {
				FIN_CLI::warning( "Site {$site->blog_id} already {$action}." );
				continue;
			}

			update_blog_status( $site->blog_id, $pref, (string) $value );
			FIN_CLI::success( "Site {$site->blog_id} {$action}." );
		}
	}

	/**
	 * Get an array of site IDs from the passed-in arguments or slug parameter.
	 *
	 * @param array $args Passed-in arguments.
	 * @param array $assoc_args Passed-in parameters.
	 *
	 * @return array Site IDs.
	 * @throws ExitException
	 */
	private function get_sites_ids( $args, $assoc_args ) {
		/**
		 * @var string|false $slug
		 */
		$slug = Utils\get_flag_value( $assoc_args, 'slug', false );

		if ( $slug ) {
			$blog_id = get_id_from_blogname( trim( $slug, '/' ) );
			if ( null === $blog_id ) {
				FIN_CLI::error( sprintf( 'Could not find site with slug \'%s\'.', $slug ) );
			}
			return [ $blog_id ];
		}

		return $args;
	}

	/**
	 * Check that the site IDs or slug are provided.
	 *
	 * @param  array  $args  Passed-in arguments.
	 * @param  array  $assoc_args  Passed-in parameters.
	 *
	 * @return bool
	 * @throws ExitException If neither site ids nor site slug using --slug were provided.
	 */
	private function check_site_ids_and_slug( $args, $assoc_args ) {
		if ( ( empty( $args ) && empty( $assoc_args['slug'] ) )
			|| ( ! empty( $args ) && ! empty( $assoc_args['slug'] ) ) ) {
			FIN_CLI::error( 'Please specify one or more IDs of sites, or pass the slug for a single site using --slug.' );
		}

		return true;
	}
}
