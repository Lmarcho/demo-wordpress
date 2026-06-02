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

// Remove plugin options.
$options = [
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
    'rag_sync_db_version',
];

foreach ($options as $option) {
    delete_option($option);
}

// Remove transients.
delete_transient('rag_sync_widget_bust');

// Drop the sync tracking table.
$table_name = $wpdb->prefix . 'rag_sync_items';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

// Clear any scheduled events.
wp_clear_scheduled_hook('rag_sync_full_sync');
