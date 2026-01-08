<?php
/**
 * Admin help tab template.
 *
 * @package WooSpamFilter
 */

defined( 'ABSPATH' ) || exit;
?>
<!-- Help / How It Works -->
<div class="tw-bg-white tw-shadow tw-rounded-lg tw-p-6">
    <h3 class="tw-text-lg tw-font-medium tw-text-gray-900 tw-mb-6">How This Plugin Works</h3>

    <div class="tw-prose tw-prose-indigo tw-max-w-none">
        <h4>The Problem</h4>
        <p>Card-testing bots exploit WooCommerce/PayPal checkout endpoints by hitting the API directly without ever loading your checkout page. They test stolen credit card numbers by making many small purchases.</p>

        <h4>The Solution</h4>
        <p>This plugin detects bot requests using multiple signals that <strong>cannot produce false positives</strong> for legitimate customers:</p>

        <div class="tw-bg-gray-50 tw-rounded-lg tw-p-4 tw-my-4">
            <table class="tw-w-full tw-text-sm">
                <thead>
                    <tr class="tw-border-b">
                        <th class="tw-text-left tw-pb-2">Detection Layer</th>
                        <th class="tw-text-left tw-pb-2">Points</th>
                        <th class="tw-text-left tw-pb-2">Why It's Safe</th>
                    </tr>
                </thead>
                <tbody class="tw-divide-y tw-divide-gray-200">
                    <tr>
                        <td class="tw-py-2 tw-font-medium">Missing JS Token</td>
                        <td class="tw-py-2">+2</td>
                        <td class="tw-py-2">Customers always load checkout page first (sets token)</td>
                    </tr>
                    <tr>
                        <td class="tw-py-2 tw-font-medium">No WC Session</td>
                        <td class="tw-py-2">+1</td>
                        <td class="tw-py-2">Customers browse your store first (creates session)</td>
                    </tr>
                    <tr>
                        <td class="tw-py-2 tw-font-medium">Rate Limit Exceeded</td>
                        <td class="tw-py-2">+3</td>
                        <td class="tw-py-2">No customer tries checkout 10+ times per hour</td>
                    </tr>
                    <tr>
                        <td class="tw-py-2 tw-font-medium">Invalid Referer</td>
                        <td class="tw-py-2">+1</td>
                        <td class="tw-py-2">Requests should come from your own site</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4>Recommended Setup</h4>
        <ol>
            <li><strong>Start in Test Mode</strong> - Leave test mode enabled for the first week</li>
            <li><strong>Review Logs</strong> - Check that flagged requests are actually bots, not real customers</li>
            <li><strong>Enable Blocking</strong> - Once confident, disable test mode to start blocking</li>
            <li><strong>Monitor</strong> - Check logs periodically to ensure no false positives</li>
        </ol>

        <h4>What Gets Protected</h4>
        <ul>
            <li><code>/?wc-ajax=ppc-create-order</code> - PayPal order creation</li>
            <li><code>/?wc-ajax=ppc-approve-order</code> - PayPal order approval</li>
            <li><code>/?wc-ajax=checkout</code> - WooCommerce AJAX checkout</li>
            <li><code>/wp-json/wc/store/checkout</code> - WooCommerce Store API</li>
        </ul>

        <h4>WP-CLI Commands</h4>
        <p>If WP-CLI is available, you can manage the plugin from command line:</p>
        <pre class="tw-bg-gray-800 tw-text-gray-100 tw-p-4 tw-rounded-lg tw-text-sm tw-overflow-x-auto">
# View stats
wp wsf stats

# Clear all logs
wp wsf clear

# Run cleanup manually
wp wsf cleanup

# Whitelist an IP
wp wsf whitelist 192.168.1.1

# Enable/disable protection
wp wsf enable
wp wsf disable

# Toggle test mode
wp wsf test-mode on
wp wsf test-mode off</pre>

        <div class="tw-bg-green-50 tw-border-l-4 tw-border-green-400 tw-p-4 tw-my-4">
            <p class="tw-text-green-800 tw-font-medium">Safe by Design</p>
            <p class="tw-text-green-700 tw-text-sm tw-mt-1">This plugin cannot block legitimate customers because it relies on signals that are always present for real checkouts (loading the page sets the token, browsing sets the session).</p>
        </div>
    </div>
</div>
