=== RAG Sync ===
Contributors: askrag
Tags: ai, chatbot, woocommerce, rag, chat widget
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Syncs WordPress/WooCommerce content to the AskRAG backend and embeds the AI-powered chat widget on your site.

== Description ==

RAG Sync connects your WordPress or WooCommerce store to an AskRAG backend so an AI chat widget can answer customer questions using your own content (posts, pages, products, categories, and coupons).

The plugin does two things:

1. **Content sync** — sends posts, pages, products, categories, and coupons to your AskRAG backend via signed webhooks whenever content is created, updated, or deleted, plus an on-demand full sync. A REST endpoint exposes real-time price and stock data so answers stay current.
2. **Chat widget** — embeds the AskRAG chat widget on the storefront. All styling (colors, messages, position, voice) is configured in the AskRAG backend, so the widget updates without touching the site.

The widget bundle is loaded with a cache-busting token derived from the deployed bundle, so a rebuilt widget reaches visitors automatically without serving a stale copy.

= Requirements =

* An AskRAG backend with a tenant slug, widget API key, and webhook secret.
* PHP 8.0 or higher.
* WooCommerce 8.0+ is optional; product/category/coupon features activate only when WooCommerce is present.

== External Services ==

This plugin connects to the AskRAG backend that you configure (the "Backend URL" in settings, e.g. https://askrag.app). It relies on this service to store and index your content and to power the AI chat widget. The plugin does not function without it.

What is sent and when:

* When sync is enabled and content changes (or you trigger a full sync), the plugin sends your posts, pages, products, categories, and coupons — including titles, descriptions, prices, stock, and SKUs — to your configured Backend URL via signed webhooks.
* When the chat widget is enabled, each visitor's browser loads the widget script from your Backend URL and sends chat messages, plus minimal session/customer context (a generated session ID, and for logged-in users their WooCommerce/WordPress identifiers) to that backend to generate answers.
* A lightweight request is made from the server to the Backend URL to detect the current widget version for cache-busting.

No data is sent to any party other than the Backend URL you configure.

Service provider (default AskRAG): https://askrag.app
Terms of Service: https://askrag.app/terms
Privacy Policy: https://askrag.app/privacy

== Installation ==

1. Upload the `rag-sync` folder to `/wp-content/plugins/`, or install the ZIP via Plugins → Add New → Upload Plugin.
2. Activate the plugin through the Plugins screen.
3. Go to Settings → RAG Sync.
4. Enter your Backend URL, Tenant Slug, API Key, Webhook Secret, and Webhook Endpoint (copy these from your AskRAG backend).
5. Enable Sync and/or Enable Widget as needed.
6. Click "Test Connection" to verify the webhook secret, then "Trigger Full Sync" to send existing content.

== Frequently Asked Questions ==

= Do I need WooCommerce? =

No. Posts and pages sync without WooCommerce. Products, categories, and coupons require WooCommerce to be active.

= The widget shows an old version after an update. What do I do? =

The widget URL is cache-busted from the deployed bundle and refreshes within a few minutes. To force an immediate refresh on a page, append `?refresh` to its URL.

= Is my data sent anywhere else? =

Content is sent only to the Backend URL you configure. Webhooks are signed with your webhook secret.

== Changelog ==

= 1.0.0 =
* Initial release: content sync via signed webhooks, on-demand full sync, real-time product REST endpoint, and embedded AI chat widget.
* Widget cache-busting derived from the deployed bundle to avoid stale copies.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
