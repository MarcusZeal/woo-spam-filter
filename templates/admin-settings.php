<?php
/**
 * Admin settings tab template.
 *
 * @package WooSpamFilter
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- Settings -->
<div class="tw-bg-white tw-shadow tw-rounded-lg">
    <form method="post" action="options.php">
        <?php settings_fields( 'wsf_settings' ); ?>

        <div class="tw-px-4 tw-py-5 sm:tw-p-6">
            <h3 class="tw-text-lg tw-font-medium tw-text-gray-900 tw-mb-6">Protection Settings</h3>

            <div class="tw-space-y-6">
                <!-- Enable/Disable -->
                <div class="tw-flex tw-items-start">
                    <div class="tw-flex tw-items-center tw-h-5">
                        <input type="checkbox" id="wsf_enabled" name="wsf_enabled" value="1"
                               <?php checked( '1', get_option( 'wsf_enabled', '1' ) ); ?>
                               class="tw-h-4 tw-w-4 tw-text-indigo-600 tw-border-gray-300 tw-rounded focus:tw-ring-indigo-500">
                    </div>
                    <div class="tw-ml-3 tw-text-sm">
                        <label for="wsf_enabled" class="tw-font-medium tw-text-gray-700">Enable Protection</label>
                        <p class="tw-text-gray-500">Monitor checkout requests for bot activity.</p>
                    </div>
                </div>

                <!-- Test Mode -->
                <div class="tw-flex tw-items-start tw-p-4 tw-bg-yellow-50 tw-rounded-lg tw-border tw-border-yellow-200">
                    <div class="tw-flex tw-items-center tw-h-5">
                        <input type="checkbox" id="wsf_test_mode" name="wsf_test_mode" value="1"
                               <?php checked( '1', get_option( 'wsf_test_mode', '1' ) ); ?>
                               class="tw-h-4 tw-w-4 tw-text-yellow-600 tw-border-gray-300 tw-rounded focus:tw-ring-yellow-500">
                    </div>
                    <div class="tw-ml-3 tw-text-sm">
                        <label for="wsf_test_mode" class="tw-font-medium tw-text-yellow-800">Test Mode (Recommended for new installs)</label>
                        <p class="tw-text-yellow-700">Log suspicious requests without blocking them. Review logs to verify accuracy before disabling test mode.</p>
                    </div>
                </div>

                <hr class="tw-border-gray-200">

                <h4 class="tw-text-md tw-font-medium tw-text-gray-900">Detection Sensitivity</h4>

                <!-- Block Threshold -->
                <div>
                    <label for="wsf_block_threshold" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Block Threshold (Score)</label>
                    <select id="wsf_block_threshold" name="wsf_block_threshold"
                            class="tw-mt-1 tw-block tw-w-full tw-pl-3 tw-pr-10 tw-py-2 tw-text-base tw-border-gray-300 focus:tw-outline-none focus:tw-ring-indigo-500 focus:tw-border-indigo-500 sm:tw-text-sm tw-rounded-md tw-border">
                        <option value="2" <?php selected( '2', get_option( 'wsf_block_threshold', '3' ) ); ?>>2 - More aggressive (may have false positives)</option>
                        <option value="3" <?php selected( '3', get_option( 'wsf_block_threshold', '3' ) ); ?>>3 - Balanced (recommended)</option>
                        <option value="4" <?php selected( '4', get_option( 'wsf_block_threshold', '3' ) ); ?>>4 - Conservative</option>
                        <option value="5" <?php selected( '5', get_option( 'wsf_block_threshold', '3' ) ); ?>>5 - Very conservative (only obvious bots)</option>
                    </select>
                    <p class="tw-mt-1 tw-text-sm tw-text-gray-500">Requests must reach this score to be blocked. Missing token = 2pts, no session = 1pt, rate limit = 3pts, bad referer = 1pt.</p>
                </div>

                <!-- Rate Limit -->
                <div>
                    <label for="wsf_rate_limit" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Rate Limit (requests per hour)</label>
                    <input type="number" id="wsf_rate_limit" name="wsf_rate_limit"
                           value="<?php echo esc_attr( get_option( 'wsf_rate_limit', '10' ) ); ?>"
                           min="5" max="100"
                           class="tw-mt-1 tw-block tw-w-32 tw-border-gray-300 tw-rounded-md tw-shadow-sm focus:tw-ring-indigo-500 focus:tw-border-indigo-500 sm:tw-text-sm tw-p-2 tw-border">
                    <p class="tw-mt-1 tw-text-sm tw-text-gray-500">Flag IPs that exceed this many checkout attempts per hour. Legitimate customers rarely exceed 5.</p>
                </div>

                <hr class="tw-border-gray-200">

                <h4 class="tw-text-md tw-font-medium tw-text-gray-900">Log Cleanup</h4>

                <!-- Auto Cleanup -->
                <div class="tw-flex tw-items-start">
                    <div class="tw-flex tw-items-center tw-h-5">
                        <input type="checkbox" id="wsf_auto_cleanup" name="wsf_auto_cleanup" value="1"
                               <?php checked( '1', get_option( 'wsf_auto_cleanup', '1' ) ); ?>
                               class="tw-h-4 tw-w-4 tw-text-indigo-600 tw-border-gray-300 tw-rounded focus:tw-ring-indigo-500">
                    </div>
                    <div class="tw-ml-3 tw-text-sm">
                        <label for="wsf_auto_cleanup" class="tw-font-medium tw-text-gray-700">Auto-cleanup old logs</label>
                        <p class="tw-text-gray-500">Automatically delete logs older than the specified number of days.</p>
                    </div>
                </div>

                <!-- Cleanup Days -->
                <div>
                    <label for="wsf_cleanup_days" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Keep logs for (days)</label>
                    <input type="number" id="wsf_cleanup_days" name="wsf_cleanup_days"
                           value="<?php echo esc_attr( get_option( 'wsf_cleanup_days', '30' ) ); ?>"
                           min="7" max="365"
                           class="tw-mt-1 tw-block tw-w-32 tw-border-gray-300 tw-rounded-md tw-shadow-sm focus:tw-ring-indigo-500 focus:tw-border-indigo-500 sm:tw-text-sm tw-p-2 tw-border">
                    <p class="tw-mt-1 tw-text-sm tw-text-gray-500">Logs older than this will be automatically deleted daily.</p>
                </div>

                <hr class="tw-border-gray-200">

                <!-- IP Whitelist -->
                <div>
                    <label for="wsf_whitelist_ips" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">IP Whitelist</label>
                    <textarea id="wsf_whitelist_ips" name="wsf_whitelist_ips" rows="3"
                              placeholder="192.168.1.1, 10.0.0.1"
                              class="tw-mt-1 tw-block tw-w-full tw-border-gray-300 tw-rounded-md tw-shadow-sm focus:tw-ring-indigo-500 focus:tw-border-indigo-500 sm:tw-text-sm tw-p-2 tw-border"><?php echo esc_textarea( get_option( 'wsf_whitelist_ips', '' ) ); ?></textarea>
                    <p class="tw-mt-1 tw-text-sm tw-text-gray-500">Comma-separated list of IP addresses that will never be blocked.</p>
                </div>
            </div>
        </div>

        <div class="tw-px-4 tw-py-3 tw-bg-gray-50 tw-text-right sm:tw-px-6 tw-rounded-b-lg">
            <button type="submit" class="tw-inline-flex tw-justify-center tw-py-2 tw-px-4 tw-border tw-border-transparent tw-shadow-sm tw-text-sm tw-font-medium tw-rounded-md tw-text-white tw-bg-indigo-600 hover:tw-bg-indigo-700 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-offset-2 focus:tw-ring-indigo-500">
                Save Settings
            </button>
        </div>
    </form>
</div>
