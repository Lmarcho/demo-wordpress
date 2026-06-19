<?php
/**
 * RAG Sync uninstall handler.
 *
 * Runs only when the plugin is deleted from the WordPress admin. Removes all
 * plugin options, the sync tracking table, and any transients so no orphaned
 * data is left behind.
 */

// Exit if not called by WordPress during uninstall.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall removes plugin-owned options, transients, and custom tables.

// Remove plugin options.
$rag_sync_options = [
    'rag_sync_backend_url',
    'rag_sync_tenant_slug',
    'rag_sync_api_key',
    'rag_sync_webhook_secret',
    'rag_sync_webhook_endpoint',
    'rag_sync_enabled',
    'rag_sync_content_types',
    'rag_sync_last_sync',
    'rag_sync_sync_status',
    'rag_sync_widget_enabled',
    'rag_sync_widget_debug',
    'rag_sync_mcp_enabled',
    'rag_sync_mcp_public_coupon_codes',
    'rag_sync_mcp_assertion_lifetime',
    'rag_sync_mcp_assertion_signing_key',
    'rag_sync_mcp_max_skus',
    'rag_sync_mcp_max_results',
    'rag_sync_mcp_max_gallery',
    'rag_sync_mcp_max_variants',
    'rag_sync_mcp_rate_limit',
    'rag_sync_mcp_db_version',
    'rag_sync_db_version',
];

foreach ($rag_sync_options as $rag_sync_option) {
    delete_option($rag_sync_option);
}

// Remove transients.
delete_transient('rag_sync_widget_bust');

$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        '_transient_rag_sync_mcp_rate_%',
        '_transient_timeout_rag_sync_mcp_rate_%'
    )
);

// Drop the sync tracking table.
$rag_sync_table_name = esc_sql($wpdb->prefix . 'rag_sync_items');
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom plugin table name is escaped above.
$wpdb->query("DROP TABLE IF EXISTS {$rag_sync_table_name}");

// Drop MCP clients table.
$rag_sync_mcp_table_name = esc_sql($wpdb->prefix . 'rag_sync_mcp_clients');
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom plugin table name is escaped above.
$wpdb->query("DROP TABLE IF EXISTS {$rag_sync_mcp_table_name}");

// Clear any scheduled events.
wp_clear_scheduled_hook('rag_sync_full_sync');
