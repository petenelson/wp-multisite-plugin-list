<?php
/**
 * Plugin Name: Multisite Plugin List
 * Description: WP-CLI commands to generate a list of plugins for a WordPress multisite install.
 * Version:     0.1.0
 * Author:      Pete Nelson
 * Author URI:  https://github.com/petenelson
 * Plugin URI:  https://github.com/petenelson/wp-multisite-plugin-list
 * License:     GPLv2 or later
 *
 * @package WPMultisitePluginList
 */

namespace WPMultisitePluginList;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! defined( 'WP_MULTISITE_PLUGIN_LIST_VERSION' ) ) {
	define( 'WP_MULTISITE_PLUGIN_LIST_VERSION', '1.0.0' );
}

if ( ! defined( 'WP_MULTISITE_PLUGIN_LIST_INC' ) ) {
	define( 'WP_MULTISITE_PLUGIN_LIST_INC', trailingslashit( dirname( __FILE__ ) ) . 'include/' );
}

if ( ! defined( 'WP_CLI' ) ||  ( defined( 'WP_CLI' ) && ! WP_CLI ) ) {
	return;
}

if ( ! is_multisite() ) {
	return;
}

/**
 * Generate a list of all plugins across a multisite install.
 *
 * @return void
 */
function list_all_plugins( $args, $assoc_args = [] ) {

	$plugins = get_plugins();
	$list    = [];

	$sites = get_sites();

	foreach ( $plugins as $plugin => $details ) {

		$slug = dirname( plugin_basename( $plugin ) );

		$data = [
			'Name'   => $details['Name'],  
			'Status' => '',
			'Sites'  => [],
		];

		if ( is_plugin_active_for_network( $plugin ) ) {
			$data['Status'] = 'active-network';
		} else {

			foreach ( $sites as $site ) {
				if ( is_a( $site, '\WP_Site' ) ) {

					switch_to_blog( $site->blog_id );

					$site_data = [
						'URL'    => home_url(),
						'Status' => is_plugin_active( $plugin ) ? 'active' : 'inactive',
					];

					$data['Sites'][] = $site_data;

					restore_current_blog();
				}
			}
		}

		$list[ $slug ] = $data;
	}

	// Now that we have the inital list, make a final list for table or CSV output.
	$items = [];

	foreach ( $list as $slug => $data ) {

		$item = [
			'Plugin' => $slug,
			'Name'   => $data['Name'],
			'Status' => $data['Status'],
			'Site'   => ''
		];

		if ( empty( $data['Sites'] ) ) {
			$items[] = $item;
		} else {
			foreach ( $data['Sites'] as $site ) {
				$item['Status'] = $site['Status'];
				$item['Site']   = $site['URL'];

				$items[] = $item;
			}
		}
	}

	\WP_CLI\Utils\format_items( 'table', $items, [ 'Plugin', 'Name', 'Status', 'Site' ] );
}
\WP_CLI::add_command( 'multisite-plugin-list list-all', __NAMESPACE__ . '\list_all_plugins' );
