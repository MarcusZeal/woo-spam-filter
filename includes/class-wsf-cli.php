<?php
/**
 * WP-CLI commands for WooCommerce Spam Filter.
 *
 * @package WooSpamFilter
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

/**
 * Manage WooCommerce Spam Filter from command line.
 */
class WSF_CLI {

    /**
     * Display spam filter statistics.
     *
     * ## EXAMPLES
     *
     *     wp wsf stats
     *
     * @subcommand stats
     */
    public function stats() {
        $stats = WSF_Logger::get_stats();

        WP_CLI::log( '' );
        WP_CLI::log( 'WooCommerce Spam Filter Statistics' );
        WP_CLI::log( str_repeat( '-', 40 ) );
        WP_CLI::log( sprintf( 'Logged Today:    %s', number_format( $stats['logged_today'] ) ) );
        WP_CLI::log( sprintf( 'Blocked Today:   %s', number_format( $stats['blocked_today'] ) ) );
        WP_CLI::log( sprintf( 'Logged Week:     %s', number_format( $stats['logged_week'] ) ) );
        WP_CLI::log( sprintf( 'Blocked Week:    %s', number_format( $stats['blocked_week'] ) ) );
        WP_CLI::log( sprintf( 'Total Logged:    %s', number_format( $stats['total_logged'] ) ) );
        WP_CLI::log( sprintf( 'Total Blocked:   %s', number_format( $stats['total_blocked'] ) ) );
        WP_CLI::log( sprintf( 'Unique IPs:      %s', number_format( $stats['unique_ips'] ) ) );
        WP_CLI::log( '' );

        $enabled   = '1' === get_option( 'wsf_enabled', '1' );
        $test_mode = '1' === get_option( 'wsf_test_mode', '1' );

        if ( ! $enabled ) {
            WP_CLI::warning( 'Protection is currently DISABLED.' );
        } elseif ( $test_mode ) {
            WP_CLI::warning( 'Test mode is active - requests are logged but not blocked.' );
        } else {
            WP_CLI::success( 'Blocking mode is active.' );
        }
    }

    /**
     * Clear all logs.
     *
     * ## EXAMPLES
     *
     *     wp wsf clear
     *     wp wsf clear --yes
     *
     * [--yes]
     * : Skip confirmation prompt.
     *
     * @subcommand clear
     */
    public function clear( $args, $assoc_args ) {
        if ( ! isset( $assoc_args['yes'] ) ) {
            WP_CLI::confirm( 'Are you sure you want to clear all logs?' );
        }

        if ( WSF_Logger::clear_logs() ) {
            WP_CLI::success( 'All logs cleared.' );
        } else {
            WP_CLI::error( 'Failed to clear logs.' );
        }
    }

    /**
     * Run log cleanup manually.
     *
     * ## EXAMPLES
     *
     *     wp wsf cleanup
     *
     * @subcommand cleanup
     */
    public function cleanup() {
        $days = get_option( 'wsf_cleanup_days', 30 );

        WSF_Logger::auto_cleanup();

        WP_CLI::success( sprintf( 'Cleanup complete. Logs older than %d days removed.', $days ) );
    }

    /**
     * Add an IP to the whitelist.
     *
     * ## OPTIONS
     *
     * <ip>
     * : The IP address to whitelist.
     *
     * ## EXAMPLES
     *
     *     wp wsf whitelist 192.168.1.1
     *
     * @subcommand whitelist
     */
    public function whitelist( $args ) {
        $ip = $args[0];

        if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            WP_CLI::error( 'Invalid IP address.' );
        }

        if ( WSF_Logger::whitelist_ip( $ip ) ) {
            WP_CLI::success( sprintf( 'IP %s added to whitelist.', $ip ) );
        } else {
            WP_CLI::error( 'Failed to add IP to whitelist.' );
        }
    }

    /**
     * Enable spam filter protection.
     *
     * ## EXAMPLES
     *
     *     wp wsf enable
     *
     * @subcommand enable
     */
    public function enable() {
        update_option( 'wsf_enabled', '1' );
        WP_CLI::success( 'Spam filter protection enabled.' );
    }

    /**
     * Disable spam filter protection.
     *
     * ## EXAMPLES
     *
     *     wp wsf disable
     *
     * @subcommand disable
     */
    public function disable() {
        update_option( 'wsf_enabled', '0' );
        WP_CLI::warning( 'Spam filter protection disabled.' );
    }

    /**
     * Toggle test mode on or off.
     *
     * ## OPTIONS
     *
     * <state>
     * : Either "on" or "off".
     *
     * ## EXAMPLES
     *
     *     wp wsf test-mode on
     *     wp wsf test-mode off
     *
     * @subcommand test-mode
     */
    public function test_mode( $args ) {
        $state = strtolower( $args[0] );

        if ( ! in_array( $state, array( 'on', 'off' ), true ) ) {
            WP_CLI::error( 'State must be "on" or "off".' );
        }

        $value = 'on' === $state ? '1' : '0';
        update_option( 'wsf_test_mode', $value );

        if ( 'on' === $state ) {
            WP_CLI::success( 'Test mode enabled. Requests will be logged but not blocked.' );
        } else {
            WP_CLI::success( 'Test mode disabled. Blocking is now active.' );
        }
    }

    /**
     * Export logs to CSV file.
     *
     * ## OPTIONS
     *
     * [--status=<status>]
     * : Filter by status (all, logged, blocked). Default: all.
     *
     * [--output=<file>]
     * : Output file path. Default: wsf-export.csv in current directory.
     *
     * ## EXAMPLES
     *
     *     wp wsf export
     *     wp wsf export --status=blocked --output=/tmp/blocked.csv
     *
     * @subcommand export
     */
    public function export( $args, $assoc_args ) {
        $status = isset( $assoc_args['status'] ) ? $assoc_args['status'] : 'all';
        $output = isset( $assoc_args['output'] ) ? $assoc_args['output'] : 'wsf-export.csv';

        $logs = WSF_Logger::get_all_logs( $status );

        if ( empty( $logs ) ) {
            WP_CLI::warning( 'No logs to export.' );
            return;
        }

        $handle = fopen( $output, 'w' );
        if ( ! $handle ) {
            WP_CLI::error( sprintf( 'Cannot write to %s', $output ) );
        }

        // Header.
        fputcsv( $handle, array( 'ID', 'IP', 'Country', 'Endpoint', 'Reason', 'Status', 'Date' ) );

        // Data.
        foreach ( $logs as $log ) {
            fputcsv( $handle, array(
                $log['id'],
                $log['ip_address'],
                $log['country_code'] ?? '',
                $log['endpoint'],
                $log['blocked_reason'],
                $log['status'],
                $log['created_at'],
            ) );
        }

        fclose( $handle );

        WP_CLI::success( sprintf( 'Exported %d logs to %s', count( $logs ), $output ) );
    }
}

WP_CLI::add_command( 'wsf', 'WSF_CLI' );
