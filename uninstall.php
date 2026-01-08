<?php
/**
 * Uninstall script for WooCommerce Spam Filter.
 *
 * This file runs when the plugin is deleted from WordPress.
 * It removes all database tables and options created by the plugin.
 *
 * @package WooSpamFilter
 */

// Exit if not called by WordPress uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Delete options.
delete_option( 'wsf_enabled' );
delete_option( 'wsf_test_mode' );
delete_option( 'wsf_block_threshold' );
delete_option( 'wsf_rate_limit' );
delete_option( 'wsf_whitelist_ips' );
delete_option( 'wsf_auto_cleanup' );
delete_option( 'wsf_cleanup_days' );

// Delete legacy options (if any).
delete_option( 'wsf_check_ga_cookie' );
delete_option( 'wsf_check_wc_cookies' );
delete_option( 'wsf_custom_cookies' );

// Drop the custom table.
$table_name = $wpdb->prefix . 'wsf_blocked_requests';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// Clear any transients we may have created.
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wsf_%' OR option_name LIKE '_transient_timeout_wsf_%'"
);

// For multisite, clean up each site.
if ( is_multisite() ) {
    $sites = get_sites();
    foreach ( $sites as $site ) {
        switch_to_blog( $site->blog_id );

        // Delete options.
        delete_option( 'wsf_enabled' );
        delete_option( 'wsf_test_mode' );
        delete_option( 'wsf_block_threshold' );
        delete_option( 'wsf_rate_limit' );
        delete_option( 'wsf_whitelist_ips' );
        delete_option( 'wsf_auto_cleanup' );
        delete_option( 'wsf_cleanup_days' );
        delete_option( 'wsf_check_ga_cookie' );
        delete_option( 'wsf_check_wc_cookies' );
        delete_option( 'wsf_custom_cookies' );

        // Drop table.
        $table_name = $wpdb->prefix . 'wsf_blocked_requests';
        $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // Clear transients.
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wsf_%' OR option_name LIKE '_transient_timeout_wsf_%'"
        );

        restore_current_blog();
    }
}
