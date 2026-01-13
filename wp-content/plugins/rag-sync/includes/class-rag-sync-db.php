<?php
/**
 * RAG Sync Database Handler
 *
 * Manages the sync tracking table
 */

if (!defined('ABSPATH')) {
    exit;
}

class RAG_Sync_DB {

    /**
     * Table name (without prefix)
     */
    const TABLE_NAME = 'rag_sync_items';

    /**
     * Get full table name with prefix
     */
    public static function get_table_name(): string {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }

    /**
     * Create the sync tracking table
     */
    public static function create_table(): void {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            item_type varchar(50) NOT NULL,
            item_id bigint(20) UNSIGNED NOT NULL,
            item_name varchar(255) NOT NULL,
            item_sku varchar(100) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            last_modified datetime DEFAULT NULL,
            last_synced datetime DEFAULT NULL,
            last_webhook_topic varchar(100) DEFAULT NULL,
            last_webhook_at datetime DEFAULT NULL,
            last_webhook_response text DEFAULT NULL,
            error_message text DEFAULT NULL,
            sync_count int(11) UNSIGNED NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY item_type_id (item_type, item_id),
            KEY status (status),
            KEY item_type (item_type),
            KEY last_synced (last_synced)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Drop the table (for uninstall)
     */
    public static function drop_table(): void {
        global $wpdb;
        $table_name = self::get_table_name();
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }

    /**
     * Get or create a sync item
     */
    public static function get_or_create_item(string $type, int $item_id, string $name, ?string $sku = null): ?object {
        global $wpdb;
        $table_name = self::get_table_name();

        $item = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE item_type = %s AND item_id = %d",
            $type,
            $item_id
        ));

        if (!$item) {
            $wpdb->insert($table_name, [
                'item_type' => $type,
                'item_id' => $item_id,
                'item_name' => $name,
                'item_sku' => $sku,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ]);

            $item = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $wpdb->insert_id
            ));
        } else {
            // Update name/sku if changed
            $wpdb->update(
                $table_name,
                [
                    'item_name' => $name,
                    'item_sku' => $sku,
                    'updated_at' => current_time('mysql'),
                ],
                ['id' => $item->id]
            );
        }

        return $item;
    }

    /**
     * Update sync status
     */
    public static function update_status(string $type, int $item_id, string $status, ?string $error = null): void {
        global $wpdb;
        $table_name = self::get_table_name();

        $data = [
            'status' => $status,
            'updated_at' => current_time('mysql'),
        ];

        if ($status === 'synced') {
            $data['last_synced'] = current_time('mysql');
            $data['error_message'] = null;
            $data['sync_count'] = $wpdb->get_var($wpdb->prepare(
                "SELECT sync_count FROM {$table_name} WHERE item_type = %s AND item_id = %d",
                $type,
                $item_id
            )) + 1;
        }

        if ($status === 'failed' && $error) {
            $data['error_message'] = $error;
        }

        $wpdb->update(
            $table_name,
            $data,
            [
                'item_type' => $type,
                'item_id' => $item_id,
            ]
        );
    }

    /**
     * Record webhook sent
     */
    public static function record_webhook(string $type, int $item_id, string $topic, ?string $response = null): void {
        global $wpdb;
        $table_name = self::get_table_name();

        $wpdb->update(
            $table_name,
            [
                'last_webhook_topic' => $topic,
                'last_webhook_at' => current_time('mysql'),
                'last_webhook_response' => $response,
                'last_modified' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            [
                'item_type' => $type,
                'item_id' => $item_id,
            ]
        );
    }

    /**
     * Mark item as modified (pending sync)
     */
    public static function mark_modified(string $type, int $item_id): void {
        global $wpdb;
        $table_name = self::get_table_name();

        $wpdb->update(
            $table_name,
            [
                'last_modified' => current_time('mysql'),
                'status' => 'pending',
                'updated_at' => current_time('mysql'),
            ],
            [
                'item_type' => $type,
                'item_id' => $item_id,
            ]
        );
    }

    /**
     * Mark item as deleted
     */
    public static function mark_deleted(string $type, int $item_id): void {
        global $wpdb;
        $table_name = self::get_table_name();

        $wpdb->update(
            $table_name,
            [
                'status' => 'deleted',
                'updated_at' => current_time('mysql'),
            ],
            [
                'item_type' => $type,
                'item_id' => $item_id,
            ]
        );
    }

    /**
     * Get items with pagination and filters
     */
    public static function get_items(array $args = []): array {
        global $wpdb;
        $table_name = self::get_table_name();

        $defaults = [
            'type' => '',
            'status' => '',
            'search' => '',
            'orderby' => 'updated_at',
            'order' => 'DESC',
            'per_page' => 20,
            'page' => 1,
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $params = [];

        if (!empty($args['type'])) {
            $where[] = 'item_type = %s';
            $params[] = $args['type'];
        }

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $params[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $where[] = '(item_name LIKE %s OR item_sku LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $where_clause = implode(' AND ', $where);

        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
        if (!empty($params)) {
            $count_sql = $wpdb->prepare($count_sql, $params);
        }
        $total = (int) $wpdb->get_var($count_sql);

        // Get items
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'updated_at DESC';
        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY {$orderby} LIMIT %d OFFSET %d";
        $params[] = $args['per_page'];
        $params[] = $offset;

        $items = $wpdb->get_results($wpdb->prepare($sql, $params));

        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $args['per_page']),
            'page' => $args['page'],
        ];
    }

    /**
     * Get sync statistics
     */
    public static function get_stats(): array {
        global $wpdb;
        $table_name = self::get_table_name();

        $stats = [
            'total' => 0,
            'by_type' => [],
            'by_status' => [],
        ];

        // Total count
        $stats['total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

        // By type
        $type_results = $wpdb->get_results(
            "SELECT item_type, COUNT(*) as count FROM {$table_name} GROUP BY item_type"
        );
        foreach ($type_results as $row) {
            $stats['by_type'][$row->item_type] = (int) $row->count;
        }

        // By status
        $status_results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$table_name} GROUP BY status"
        );
        foreach ($status_results as $row) {
            $stats['by_status'][$row->status] = (int) $row->count;
        }

        // Last sync time
        $stats['last_sync'] = $wpdb->get_var(
            "SELECT MAX(last_synced) FROM {$table_name} WHERE last_synced IS NOT NULL"
        );

        return $stats;
    }

    /**
     * Bulk update status
     */
    public static function bulk_update_status(array $ids, string $status): int {
        global $wpdb;
        $table_name = self::get_table_name();

        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        return $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET status = %s, updated_at = %s WHERE id IN ({$placeholders})",
            array_merge([$status, current_time('mysql')], $ids)
        ));
    }

    /**
     * Delete old deleted items (cleanup)
     */
    public static function cleanup_deleted(int $days = 30): int {
        global $wpdb;
        $table_name = self::get_table_name();

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table_name} WHERE status = 'deleted' AND updated_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
}
