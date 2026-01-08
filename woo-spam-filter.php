<?php
/**
 * Plugin Name: WooCommerce Spam Filter
 * Plugin URI: https://github.com/marcuszeal/woo-spam-filter
 * Description: Blocks card-testing bot attacks on WooCommerce/PayPal checkout endpoints by verifying browser cookies.
 * Version: 1.4.0
 * Author: Marcus Zeal
 * Author URI: https://marcuszeal.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-spam-filter
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.5
 */

defined( 'ABSPATH' ) || exit;

define( 'WSF_VERSION', '1.4.0' );
define( 'WSF_PLUGIN_FILE', __FILE__ );
define( 'WSF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WSF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize plugin update checker.
 * Checks GitHub releases for new versions.
 */
require_once WSF_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$wsf_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/marcuszeal/woo-spam-filter',
	__FILE__,
	'woo-spam-filter'
);

// Use releases as the update source (recommended).
$wsf_update_checker->getVcsApi()->enableReleaseAssets();

/**
 * Main plugin class.
 */
final class WooSpamFilter {

    /**
     * Single instance.
     *
     * @var WooSpamFilter
     */
    private static $instance = null;

    /**
     * Get instance.
     *
     * @return WooSpamFilter
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files.
     */
    private function includes() {
        // Load shared trait first.
        require_once WSF_PLUGIN_DIR . 'includes/trait-wsf-ip-utils.php';

        // Load core classes.
        require_once WSF_PLUGIN_DIR . 'includes/class-wsf-logger.php';
        require_once WSF_PLUGIN_DIR . 'includes/class-wsf-detector.php';
        require_once WSF_PLUGIN_DIR . 'includes/class-wsf-admin.php';

        // Load CLI commands if WP-CLI is available.
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            require_once WSF_PLUGIN_DIR . 'includes/class-wsf-cli.php';
        }
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        register_activation_hook( WSF_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( WSF_PLUGIN_FILE, array( $this, 'deactivate' ) );

        add_action( 'init', array( 'WSF_Detector', 'init' ), 1 );
        add_action( 'admin_menu', array( 'WSF_Admin', 'add_menu' ) );
        add_action( 'admin_init', array( 'WSF_Admin', 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );

        // Dashboard widget.
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );

        // Admin bar indicator.
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_indicator' ), 100 );

        // Auto-cleanup cron.
        add_action( 'wsf_cleanup_logs', array( 'WSF_Logger', 'auto_cleanup' ) );

        // AJAX handlers.
        add_action( 'wp_ajax_wsf_get_chart_data', array( 'WSF_Admin', 'ajax_get_chart_data' ) );
        add_action( 'wp_ajax_wsf_bulk_whitelist', array( 'WSF_Admin', 'ajax_bulk_whitelist' ) );

        // Declare HPOS compatibility.
        add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
    }

    /**
     * Activation hook.
     */
    public function activate() {
        WSF_Logger::create_table();

        // Set default options.
        add_option( 'wsf_enabled', '1' );
        add_option( 'wsf_test_mode', '1' );
        add_option( 'wsf_block_threshold', '3' );
        add_option( 'wsf_rate_limit', '10' );
        add_option( 'wsf_whitelist_ips', '' );
        add_option( 'wsf_auto_cleanup', '1' );
        add_option( 'wsf_cleanup_days', '30' );

        // Schedule cleanup cron.
        if ( ! wp_next_scheduled( 'wsf_cleanup_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'wsf_cleanup_logs' );
        }
    }

    /**
     * Deactivation hook.
     */
    public function deactivate() {
        // Clear scheduled cron.
        wp_clear_scheduled_hook( 'wsf_cleanup_logs' );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page.
     */
    public function admin_assets( $hook ) {
        // Admin bar styles on all pages.
        wp_add_inline_style( 'admin-bar', '
            #wpadminbar .wsf-status-enabled { color: #46b450 !important; }
            #wpadminbar .wsf-status-test { color: #ffb900 !important; }
            #wpadminbar .wsf-status-disabled { color: #dc3232 !important; }
        ' );

        if ( 'woocommerce_page_woo-spam-filter' !== $hook && 'index.php' !== $hook ) {
            return;
        }

        // Tailwind CSS (pre-compiled).
        wp_enqueue_style(
            'wsf-tailwind',
            WSF_PLUGIN_URL . 'assets/admin-tailwind.css',
            array(),
            WSF_VERSION
        );

        // Custom admin styles.
        wp_enqueue_style(
            'wsf-admin',
            WSF_PLUGIN_URL . 'assets/admin.css',
            array( 'wsf-tailwind' ),
            WSF_VERSION
        );

        // Chart.js for trend visualization.
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            array(),
            '4.4.1',
            true
        );

        wp_enqueue_script(
            'wsf-admin',
            WSF_PLUGIN_URL . 'assets/admin.js',
            array( 'jquery', 'chartjs' ),
            WSF_VERSION,
            true
        );

        wp_localize_script( 'wsf-admin', 'wsfAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wsf_admin_nonce' ),
        ) );
    }

    /**
     * Add dashboard widget.
     */
    public function add_dashboard_widget() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'wsf_dashboard_widget',
            __( 'Spam Filter Status', 'woo-spam-filter' ),
            array( $this, 'render_dashboard_widget' )
        );
    }

    /**
     * Render dashboard widget.
     */
    public function render_dashboard_widget() {
        $stats     = WSF_Logger::get_stats_cached();
        $enabled   = '1' === get_option( 'wsf_enabled', '1' );
        $test_mode = '1' === get_option( 'wsf_test_mode', '1' );

        ?>
        <div class="wsf-dashboard-widget">
            <style>
                .wsf-dashboard-widget .wsf-status { padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; }
                .wsf-dashboard-widget .wsf-status-enabled { background: #d4edda; color: #155724; }
                .wsf-dashboard-widget .wsf-status-test { background: #fff3cd; color: #856404; }
                .wsf-dashboard-widget .wsf-status-disabled { background: #f8d7da; color: #721c24; }
                .wsf-dashboard-widget .wsf-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                .wsf-dashboard-widget .wsf-stat { background: #f8f9fa; padding: 12px; border-radius: 4px; text-align: center; }
                .wsf-dashboard-widget .wsf-stat-value { font-size: 24px; font-weight: 600; color: #1d2327; }
                .wsf-dashboard-widget .wsf-stat-label { font-size: 12px; color: #646970; margin-top: 4px; }
                .wsf-dashboard-widget .wsf-actions { margin-top: 15px; text-align: center; }
            </style>

            <?php if ( $enabled && $test_mode ) : ?>
                <div class="wsf-status wsf-status-test">
                    <strong>Test Mode</strong> - Logging only, not blocking
                </div>
            <?php elseif ( $enabled ) : ?>
                <div class="wsf-status wsf-status-enabled">
                    <strong>Active</strong> - Blocking suspicious requests
                </div>
            <?php else : ?>
                <div class="wsf-status wsf-status-disabled">
                    <strong>Disabled</strong> - Protection is off
                </div>
            <?php endif; ?>

            <div class="wsf-stats">
                <div class="wsf-stat">
                    <div class="wsf-stat-value"><?php echo esc_html( number_format( $stats['logged_today'] ) ); ?></div>
                    <div class="wsf-stat-label">Logged Today</div>
                </div>
                <div class="wsf-stat">
                    <div class="wsf-stat-value"><?php echo esc_html( number_format( $stats['blocked_today'] ) ); ?></div>
                    <div class="wsf-stat-label">Blocked Today</div>
                </div>
                <div class="wsf-stat">
                    <div class="wsf-stat-value"><?php echo esc_html( number_format( $stats['logged_week'] + $stats['blocked_week'] ) ); ?></div>
                    <div class="wsf-stat-label">This Week</div>
                </div>
                <div class="wsf-stat">
                    <div class="wsf-stat-value"><?php echo esc_html( number_format( $stats['unique_ips'] ) ); ?></div>
                    <div class="wsf-stat-label">Unique IPs</div>
                </div>
            </div>

            <div class="wsf-actions">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-spam-filter&tab=logs' ) ); ?>" class="button">
                    View Logs
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-spam-filter&tab=settings' ) ); ?>" class="button">
                    Settings
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Add admin bar indicator.
     *
     * @param WP_Admin_Bar $admin_bar Admin bar instance.
     */
    public function add_admin_bar_indicator( $admin_bar ) {
        if ( ! current_user_can( 'manage_woocommerce' ) || ! is_admin() ) {
            return;
        }

        $enabled   = '1' === get_option( 'wsf_enabled', '1' );
        $test_mode = '1' === get_option( 'wsf_test_mode', '1' );
        $stats     = WSF_Logger::get_stats_cached();

        if ( $enabled && $test_mode ) {
            $status_class = 'wsf-status-test';
            $status_text  = 'Test';
            $icon         = '⚠';
        } elseif ( $enabled ) {
            $status_class = 'wsf-status-enabled';
            $status_text  = 'Active';
            $icon         = '✓';
        } else {
            $status_class = 'wsf-status-disabled';
            $status_text  = 'Off';
            $icon         = '✗';
        }

        $today_count = $stats['logged_today'] + $stats['blocked_today'];

        $admin_bar->add_node( array(
            'id'    => 'wsf-status',
            'title' => sprintf(
                '<span class="%s">%s Spam Filter: %s</span>%s',
                esc_attr( $status_class ),
                $icon,
                $status_text,
                $today_count > 0 ? ' <span style="background:#d63638;color:#fff;padding:0 6px;border-radius:10px;font-size:11px;">' . $today_count . '</span>' : ''
            ),
            'href'  => admin_url( 'admin.php?page=woo-spam-filter' ),
        ) );

        $admin_bar->add_node( array(
            'id'     => 'wsf-view-logs',
            'parent' => 'wsf-status',
            'title'  => 'View Logs',
            'href'   => admin_url( 'admin.php?page=woo-spam-filter&tab=logs' ),
        ) );

        $admin_bar->add_node( array(
            'id'     => 'wsf-settings',
            'parent' => 'wsf-status',
            'title'  => 'Settings',
            'href'   => admin_url( 'admin.php?page=woo-spam-filter&tab=settings' ),
        ) );
    }

    /**
     * Declare HPOS compatibility.
     */
    public function declare_hpos_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WSF_PLUGIN_FILE, true );
        }
    }
}

/**
 * Initialize the plugin.
 *
 * @return WooSpamFilter
 */
function woo_spam_filter() {
    return WooSpamFilter::instance();
}

// Initialize.
woo_spam_filter();
