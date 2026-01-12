<?php
/**
 * RAG Sync WordPress Hooks
 *
 * Handles WordPress post and page hooks for content syncing
 */

if (!defined('ABSPATH')) {
    exit;
}

class RAG_Sync_WordPress_Hooks {

    /**
     * Webhook sender
     */
    private RAG_Sync_Webhook $webhook;

    /**
     * Constructor
     */
    public function __construct(RAG_Sync_Webhook $webhook) {
        $this->webhook = $webhook;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Post hooks
        add_action('wp_insert_post', [$this, 'on_post_saved'], 10, 3);
        add_action('before_delete_post', [$this, 'on_post_deleted'], 10, 2);
        add_action('trashed_post', [$this, 'on_post_trashed']);
        add_action('untrashed_post', [$this, 'on_post_untrashed']);

        // Post status transitions
        add_action('transition_post_status', [$this, 'on_post_status_changed'], 10, 3);
    }

    /**
     * Handle post saved (create/update)
     */
    public function on_post_saved(int $post_id, WP_Post $post, bool $update): void {
        // Skip autosaves
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Skip revisions
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Skip auto-drafts
        if ($post->post_status === 'auto-draft') {
            return;
        }

        // Handle different post types
        switch ($post->post_type) {
            case 'post':
                $this->handle_post_change($post, $update);
                break;

            case 'page':
                $this->handle_page_change($post, $update);
                break;

            // WooCommerce products are handled by WooCommerce hooks
            case 'product':
            case 'shop_coupon':
                return;

            default:
                // Skip other post types
                return;
        }
    }

    /**
     * Handle post deletion
     */
    public function on_post_deleted(int $post_id, WP_Post $post): void {
        switch ($post->post_type) {
            case 'post':
                if (RAG_Sync::is_content_type_enabled('posts')) {
                    $this->webhook->send(
                        RAG_Sync_Webhook::TOPIC_POST_DELETED,
                        ['id' => $post_id]
                    );
                }
                break;

            case 'page':
                if (RAG_Sync::is_content_type_enabled('pages')) {
                    $this->webhook->send(
                        RAG_Sync_Webhook::TOPIC_PAGE_DELETED,
                        ['id' => $post_id]
                    );
                }
                break;
        }
    }

    /**
     * Handle post trashed
     */
    public function on_post_trashed(int $post_id): void {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        // Treat trashing as deletion for RAG purposes
        $this->on_post_deleted($post_id, $post);
    }

    /**
     * Handle post untrashed (restored)
     */
    public function on_post_untrashed(int $post_id): void {
        $post = get_post($post_id);
        if (!$post) {
            return;
        }

        // Treat restoration as creation
        $this->on_post_saved($post_id, $post, false);
    }

    /**
     * Handle post status changes
     */
    public function on_post_status_changed(string $new_status, string $old_status, WP_Post $post): void {
        // If published -> unpublished, treat as deletion
        if ($old_status === 'publish' && $new_status !== 'publish') {
            switch ($post->post_type) {
                case 'post':
                    if (RAG_Sync::is_content_type_enabled('posts')) {
                        $this->webhook->send(
                            RAG_Sync_Webhook::TOPIC_POST_DELETED,
                            ['id' => $post->ID]
                        );
                    }
                    break;

                case 'page':
                    if (RAG_Sync::is_content_type_enabled('pages')) {
                        $this->webhook->send(
                            RAG_Sync_Webhook::TOPIC_PAGE_DELETED,
                            ['id' => $post->ID]
                        );
                    }
                    break;
            }
        }

        // If unpublished -> published, treat as creation
        if ($old_status !== 'publish' && $new_status === 'publish') {
            $this->on_post_saved($post->ID, $post, false);
        }
    }

    /**
     * Handle blog post changes
     */
    private function handle_post_change(WP_Post $post, bool $update): void {
        if (!RAG_Sync::is_content_type_enabled('posts')) {
            return;
        }

        // Only sync published posts
        if ($post->post_status !== 'publish') {
            return;
        }

        $topic = $update
            ? RAG_Sync_Webhook::TOPIC_POST_UPDATED
            : RAG_Sync_Webhook::TOPIC_POST_CREATED;

        $this->webhook->send($topic, $this->webhook->build_post_payload($post));
    }

    /**
     * Handle page changes
     */
    private function handle_page_change(WP_Post $page, bool $update): void {
        if (!RAG_Sync::is_content_type_enabled('pages')) {
            return;
        }

        // Only sync published pages
        if ($page->post_status !== 'publish') {
            return;
        }

        $topic = $update
            ? RAG_Sync_Webhook::TOPIC_PAGE_UPDATED
            : RAG_Sync_Webhook::TOPIC_PAGE_CREATED;

        $this->webhook->send($topic, $this->webhook->build_page_payload($page));
    }
}
