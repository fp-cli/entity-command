<?php

use FIN_CLI\Utils;

if ( ! class_exists( 'FIN_CLI' ) ) {
	return;
}

$fincli_entity_autoloader = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $fincli_entity_autoloader ) ) {
	require_once $fincli_entity_autoloader;
}

FIN_CLI::add_command( 'comment', 'Comment_Command' );
FIN_CLI::add_command( 'comment meta', 'Comment_Meta_Command' );
FIN_CLI::add_command( 'menu', 'Menu_Command' );
FIN_CLI::add_command( 'menu item', 'Menu_Item_Command' );
FIN_CLI::add_command( 'menu location', 'Menu_Location_Command' );
FIN_CLI::add_command(
	'network meta',
	'Network_Meta_Command',
	array(
		'before_invoke' => function () {
			if ( ! is_multisite() ) {
				FIN_CLI::error( 'This is not a multisite installation.' );
			}
		},
	)
);
FIN_CLI::add_command( 'option', 'Option_Command' );
FIN_CLI::add_command( 'post', 'Post_Command' );
FIN_CLI::add_command( 'post meta', 'Post_Meta_Command' );
FIN_CLI::add_command( 'post term', 'Post_Term_Command' );
FIN_CLI::add_command( 'post-type', 'Post_Type_Command' );
FIN_CLI::add_command( 'site', 'Site_Command' );
FIN_CLI::add_command(
	'site meta',
	'Site_Meta_Command',
	array(
		'before_invoke' => function () {
			if ( ! is_multisite() ) {
				FIN_CLI::error( 'This is not a multisite installation.' );
			}
			if ( ! function_exists( 'is_site_meta_supported' ) || ! is_site_meta_supported() ) {
				FIN_CLI::error( sprintf( 'The %s table is not installed. Please run the network database upgrade.', $GLOBALS['findb']->blogmeta ) );
			}
		},
	)
);
FIN_CLI::add_command(
	'site option',
	'Site_Option_Command',
	array(
		'before_invoke' => function () {
			if ( ! is_multisite() ) {
				FIN_CLI::error( 'This is not a multisite installation.' );
			}
		},
	)
);
FIN_CLI::add_command( 'taxonomy', 'Taxonomy_Command' );
FIN_CLI::add_command( 'term', 'Term_Command' );
FIN_CLI::add_command( 'term meta', 'Term_Meta_Command' );
FIN_CLI::add_command( 'user', 'User_Command' );
FIN_CLI::add_command(
	'user application-password',
	'User_Application_Password_Command',
	array(
		'before_invoke' => function () {
			if ( Utils\fin_version_compare( '5.6', '<' ) ) {
				FIN_CLI::error( 'Requires FinPress 5.6 or greater.' );
			}
		},
	)
);
FIN_CLI::add_command( 'user meta', 'User_Meta_Command' );
FIN_CLI::add_command( 'user session', 'User_Session_Command' );
FIN_CLI::add_command( 'user term', 'User_Term_Command' );

if ( class_exists( 'FIN_CLI\Dispatcher\CommandNamespace' ) ) {
	FIN_CLI::add_command( 'network', 'Network_Namespace' );
}

FIN_CLI::add_command(
	'user signup',
	'Signup_Command',
	array(
		'before_invoke' => function () {
			if ( ! is_multisite() ) {
				FIN_CLI::error( 'This is not a multisite installation.' );
			}
		},
	)
);
