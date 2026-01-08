<?php
/**
 * Admin class for WooCommerce Spam Filter.
 *
 * @package WooSpamFilter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles admin settings and log viewer.
 */
class WSF_Admin {

    /**
     * Add admin menu.
     */
    public static function add_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Spam Filter', 'woo-spam-filter' ),
            __( 'Spam Filter', 'woo-spam-filter' ),
            'manage_woocommerce',
            'woo-spam-filter',
            array( __CLASS__, 'render_page' )
        );
    }

    /**
     * Register settings.
     */
    public static function register_settings() {
        register_setting( 'wsf_settings', 'wsf_enabled', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'wsf_settings', 'wsf_test_mode', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'wsf_settings', 'wsf_block_threshold', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'wsf_settings', 'wsf_rate_limit', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'wsf_settings', 'wsf_whitelist_ips', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
        register_setting( 'wsf_settings', 'wsf_auto_cleanup', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'wsf_settings', 'wsf_cleanup_days', array( 'sanitize_callback' => 'absint' ) );
    }

    /**
     * Handle AJAX request for chart data.
     */
    public static function ajax_get_chart_data() {
        check_ajax_referer( 'wsf_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $days = isset( $_POST['days'] ) ? absint( $_POST['days'] ) : 14;
        $data = WSF_Logger::get_chart_data( $days );

        wp_send_json_success( $data );
    }

    /**
     * Handle AJAX request for bulk whitelist.
     */
    public static function ajax_bulk_whitelist() {
        check_ajax_referer( 'wsf_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $ips = isset( $_POST['ips'] ) ? array_map( 'sanitize_text_field', (array) $_POST['ips'] ) : array();

        if ( empty( $ips ) ) {
            wp_send_json_error( array( 'message' => 'No IPs provided.' ) );
        }

        $added = WSF_Logger::bulk_whitelist_ips( $ips );

        wp_send_json_success( array(
            'message' => sprintf( '%d IP address(es) added to whitelist.', $added ),
            'added'   => $added,
        ) );
    }

    /**
     * Handle admin actions.
     */
    private static function handle_actions() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        // Clear logs.
        if ( isset( $_POST['wsf_clear_logs'] ) && check_admin_referer( 'wsf_clear_logs_nonce' ) ) {
            WSF_Logger::clear_logs();
            add_settings_error( 'wsf_messages', 'wsf_logs_cleared', __( 'Logs cleared successfully.', 'woo-spam-filter' ), 'success' );
        }

        // Whitelist IP.
        if ( isset( $_POST['wsf_whitelist_ip'] ) && check_admin_referer( 'wsf_whitelist_ip_nonce' ) ) {
            $ip = isset( $_POST['wsf_ip'] ) ? sanitize_text_field( wp_unslash( $_POST['wsf_ip'] ) ) : '';
            if ( ! empty( $ip ) && WSF_Logger::whitelist_ip( $ip ) ) {
                add_settings_error( 'wsf_messages', 'wsf_ip_whitelisted', sprintf( __( 'IP %s added to whitelist.', 'woo-spam-filter' ), esc_html( $ip ) ), 'success' );
            }
        }

        // Export logs.
        if ( isset( $_GET['wsf_export'] ) && check_admin_referer( 'wsf_export_nonce' ) ) {
            self::export_logs();
        }
    }

    /**
     * Export logs as CSV.
     */
    private static function export_logs() {
        $status    = isset( $_GET['export_status'] ) ? sanitize_text_field( wp_unslash( $_GET['export_status'] ) ) : 'all';
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

        $logs = WSF_Logger::get_all_logs( $status, $date_from, $date_to );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=wsf-blocked-requests-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );

        // Header row.
        fputcsv( $output, array( 'ID', 'IP Address', 'Country', 'Endpoint', 'User Agent', 'Request Data', 'Reason', 'Status', 'Date' ) );

        // Data rows.
        foreach ( $logs as $log ) {
            fputcsv(
                $output,
                array(
                    $log['id'],
                    $log['ip_address'],
                    $log['country_code'] ?? '',
                    $log['endpoint'],
                    $log['user_agent'],
                    $log['request_data'],
                    $log['blocked_reason'],
                    $log['status'],
                    $log['created_at'],
                )
            );
        }

        fclose( $output );
        exit;
    }

    /**
     * Get country flag emoji.
     *
     * @param string $country_code Two-letter country code.
     * @return string Flag emoji or empty string.
     */
    public static function get_flag_emoji( $country_code ) {
        if ( empty( $country_code ) || strlen( $country_code ) !== 2 ) {
            return '';
        }

        $country_code = strtoupper( $country_code );
        $flag         = '';

        // Convert country code to regional indicator symbols.
        foreach ( str_split( $country_code ) as $char ) {
            $flag .= mb_chr( ord( $char ) - ord( 'A' ) + 0x1F1E6 );
        }

        return $flag;
    }

    /**
     * Render the admin page.
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        self::handle_actions();

        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
        $stats       = WSF_Logger::get_stats_cached();
        $test_mode   = '1' === get_option( 'wsf_test_mode', '1' );
        $enabled     = '1' === get_option( 'wsf_enabled', '1' );

        // Pagination and filtering.
        $page          = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $per_page      = 20;
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all';
        $ip_search     = isset( $_GET['ip_search'] ) ? sanitize_text_field( wp_unslash( $_GET['ip_search'] ) ) : '';
        $date_from     = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to       = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

        $total = WSF_Logger::get_total_count( $status_filter, $ip_search, $date_from, $date_to );
        $logs  = WSF_Logger::get_logs( $page, $per_page, $status_filter, 'created_at', 'DESC', $ip_search, $date_from, $date_to );

        // Chart data.
        $chart_data = WSF_Logger::get_chart_data( 14 );

        // Load header template.
        include WSF_PLUGIN_DIR . 'templates/admin-header.php';

        // Load tab-specific template.
        switch ( $current_tab ) {
            case 'logs':
                include WSF_PLUGIN_DIR . 'templates/admin-logs.php';
                break;
            case 'settings':
                include WSF_PLUGIN_DIR . 'templates/admin-settings.php';
                break;
            case 'help':
                include WSF_PLUGIN_DIR . 'templates/admin-help.php';
                break;
            default:
                include WSF_PLUGIN_DIR . 'templates/admin-dashboard.php';
                break;
        }

        // Load footer template.
        include WSF_PLUGIN_DIR . 'templates/admin-footer.php';
    }
}
