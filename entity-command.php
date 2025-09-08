<?php

use FP_CLI\Utils;

if ( ! class_exists( 'FP_CLI' ) ) {
	return;
}

$fpcli_entity_autoloader = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $fpcli_entity_autoloader ) ) {
	require_once $fpcli_entity_autoloader;
}

FP_CLI::add_command( 'comment', 'Comment_Command' );
FP_CLI::add_command( 'comment meta', 'Comment_Meta_Command' );
FP_CLI::add_command( 'menu', 'Menu_Command' );
FP_CLI::add_command( 'menu item', 'Menu_Item_Command' );
FP_CLI::add_command( 'menu location', 'Menu_Location_Command' );
FP_CLI::add_command(
	'network meta',
	'Network_Meta_Command',
	array(
		'before_invoke' => function () {
			if ( ! is_multisite() ) {
				FP_CLI::error( 'This is not a multisite installation.' );
			}
		},
	)
);
FP_CLI::add_command( 'option', 'Option_Command' );
FP_CLI::add_command( 'post', 'Post_Command' );
FP_CLI::add_command( 'post meta', 'Post_Meta_Command' );
FP_CLI::add_command( 'post term', 'Post_Term_Command' );
FP_CLI::add_command( 'post-type', 'Post_Type_Command' );
FP_CLI::add_command( 'site', 'Site_Command' );
FP_CLI::add_command(
	'site meta',
	'Site_Meta_Command',
	array(
		'before_invoke' => function () {
			if ( ! is_multisite() ) {
				FP_CLI::error( 'This is not a multisite installation.' );
			}
			if ( ! function_exists( 'is_site_meta_supported' ) || ! is_site_meta_supported() ) {
				FP_CLI::error( sprintf( 'The %s table is not installed. Please run the network database upgrade.', $GLOBALS['fpdb']->blogmeta ) );
			}
		},
	)
);
FP_CLI::add_command(
	'site option',
	'Site_Option_Command',
	array(
		'before_invoke' => function () {
			if ( ! is_multisite() ) {
				FP_CLI::error( 'This is not a multisite installation.' );
			}
		},
	)
);
FP_CLI::add_command( 'taxonomy', 'Taxonomy_Command' );
FP_CLI::add_command( 'term', 'Term_Command' );
FP_CLI::add_command( 'term meta', 'Term_Meta_Command' );
FP_CLI::add_command( 'user', 'User_Command' );
FP_CLI::add_command(
	'user application-password',
	'User_Application_Password_Command',
	array(
		'before_invoke' => function () {
			if ( Utils\fp_version_compare( '5.6', '<' ) ) {
				FP_CLI::error( 'Requires FinPress 5.6 or greater.' );
			}
		},
	)
);
FP_CLI::add_command( 'user meta', 'User_Meta_Command' );
FP_CLI::add_command( 'user session', 'User_Session_Command' );
FP_CLI::add_command( 'user term', 'User_Term_Command' );

if ( class_exists( 'FP_CLI\Dispatcher\CommandNamespace' ) ) {
	FP_CLI::add_command( 'network', 'Network_Namespace' );
}

FP_CLI::add_command(
	'user signup',
	'Signup_Command',
	array(
		'before_invoke' => function () {
			if ( ! is_multisite() ) {
				FP_CLI::error( 'This is not a multisite installation.' );
			}
		},
	)
);
