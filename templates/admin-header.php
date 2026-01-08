<?php
/**
 * Admin page header template.
 *
 * @package WooSpamFilter
 *
 * @var bool   $enabled     Whether protection is enabled.
 * @var bool   $test_mode   Whether test mode is active.
 * @var string $current_tab Current active tab.
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="wsf-admin-wrapper">
    <div class="tw-max-w-7xl tw-mx-auto tw-py-6 tw-px-4">
        <!-- Header -->
        <div class="tw-mb-8">
            <h1 class="tw-text-3xl tw-font-bold tw-text-gray-900">WooCommerce Spam Filter</h1>
            <p class="tw-mt-2 tw-text-gray-600">Block card-testing bot attacks on checkout endpoints.</p>
        </div>

        <?php settings_errors( 'wsf_messages' ); ?>

        <!-- Mode Banner -->
        <?php if ( $enabled && $test_mode ) : ?>
            <div class="tw-mb-6 tw-bg-yellow-50 tw-border-l-4 tw-border-yellow-400 tw-p-4 tw-rounded-r-lg">
                <div class="tw-flex">
                    <div class="tw-flex-shrink-0">
                        <svg class="tw-h-5 tw-w-5 tw-text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="tw-ml-3">
                        <p class="tw-text-sm tw-text-yellow-700">
                            <strong>Test Mode Active</strong> - Suspicious requests are being logged but NOT blocked.
                            Review the logs to verify detection accuracy before enabling blocking mode.
                        </p>
                    </div>
                </div>
            </div>
        <?php elseif ( $enabled && ! $test_mode ) : ?>
            <div class="tw-mb-6 tw-bg-green-50 tw-border-l-4 tw-border-green-400 tw-p-4 tw-rounded-r-lg">
                <div class="tw-flex">
                    <div class="tw-flex-shrink-0">
                        <svg class="tw-h-5 tw-w-5 tw-text-green-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="tw-ml-3">
                        <p class="tw-text-sm tw-text-green-700">
                            <strong>Blocking Mode Active</strong> - Suspicious requests exceeding the threshold are being blocked.
                        </p>
                    </div>
                </div>
            </div>
        <?php elseif ( ! $enabled ) : ?>
            <div class="tw-mb-6 tw-bg-red-50 tw-border-l-4 tw-border-red-400 tw-p-4 tw-rounded-r-lg">
                <div class="tw-flex">
                    <div class="tw-flex-shrink-0">
                        <svg class="tw-h-5 tw-w-5 tw-text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="tw-ml-3">
                        <p class="tw-text-sm tw-text-red-700">
                            <strong>Protection Disabled</strong> - Your checkout is not protected. Enable in Settings.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tw-border-b tw-border-gray-200 tw-mb-6">
            <nav class="tw--mb-px tw-flex tw-space-x-8">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-spam-filter&tab=dashboard' ) ); ?>"
                   class="<?php echo 'dashboard' === $current_tab ? 'tw-border-indigo-500 tw-text-indigo-600' : 'tw-border-transparent tw-text-gray-500 hover:tw-text-gray-700 hover:tw-border-gray-300'; ?> tw-whitespace-nowrap tw-py-4 tw-px-1 tw-border-b-2 tw-font-medium tw-text-sm">
                    Dashboard
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-spam-filter&tab=logs' ) ); ?>"
                   class="<?php echo 'logs' === $current_tab ? 'tw-border-indigo-500 tw-text-indigo-600' : 'tw-border-transparent tw-text-gray-500 hover:tw-text-gray-700 hover:tw-border-gray-300'; ?> tw-whitespace-nowrap tw-py-4 tw-px-1 tw-border-b-2 tw-font-medium tw-text-sm">
                    Logs
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-spam-filter&tab=settings' ) ); ?>"
                   class="<?php echo 'settings' === $current_tab ? 'tw-border-indigo-500 tw-text-indigo-600' : 'tw-border-transparent tw-text-gray-500 hover:tw-text-gray-700 hover:tw-border-gray-300'; ?> tw-whitespace-nowrap tw-py-4 tw-px-1 tw-border-b-2 tw-font-medium tw-text-sm">
                    Settings
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=woo-spam-filter&tab=help' ) ); ?>"
                   class="<?php echo 'help' === $current_tab ? 'tw-border-indigo-500 tw-text-indigo-600' : 'tw-border-transparent tw-text-gray-500 hover:tw-text-gray-700 hover:tw-border-gray-300'; ?> tw-whitespace-nowrap tw-py-4 tw-px-1 tw-border-b-2 tw-font-medium tw-text-sm">
                    How It Works
                </a>
            </nav>
        </div>
