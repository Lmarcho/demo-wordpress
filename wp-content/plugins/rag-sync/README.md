# RAG Sync

RAG Sync connects a WordPress or WooCommerce site to an AskRAG backend. It syncs
site content for retrieval, embeds the AskRAG chat widget, and can expose
token-protected read-only MCP tools for live catalog and verified order data.

## Features

- Sync WordPress posts and pages to the configured AskRAG backend.
- Sync WooCommerce products, categories, and coupons when WooCommerce is active.
- Send signed webhooks when synced content is created, updated, or deleted.
- Trigger a full sync from the WordPress admin.
- Embed the AskRAG storefront chat widget.
- Expose optional read-only MCP tools for public content, catalog data,
  promotions, product popularity, customer-owned order status, cart summaries,
  and purchase-history summaries.
- Support guest order verification through a widget form using order number plus
  billing email or phone.

## Requirements

- WordPress 6.0 or later.
- PHP 8.0 or later.
- An AskRAG backend tenant with Backend URL, Tenant Slug, Widget API Key,
  Webhook Secret, and Webhook Endpoint.
- WooCommerce 8.0 or later for product, coupon, cart, purchase history, and
  order tools.

## Installation

1. Upload the `rag-sync` folder to `wp-content/plugins/`, or install the ZIP from
   Plugins > Add New > Upload Plugin.
2. Activate RAG Sync from the Plugins screen.
3. Open Settings > RAG Sync.
4. Enter Backend URL, Tenant Slug, API Key, Webhook Secret, and Webhook Endpoint.
5. Select the content types to sync.
6. Enable Sync and/or Enable Widget.
7. Click Test Connection.
8. Click Trigger Full Sync.

## MCP Setup

MCP is optional. Enable it only when the AskRAG tenant should fetch live
WordPress or WooCommerce data from this site.

1. Open Settings > RAG Sync.
2. Enable MCP.
3. Create an MCP client token.
4. Copy the fallback endpoint and token into the AskRAG WooCommerce integration
   settings.

Fallback MCP endpoint:

```text
/wp-json/rag-sync/v1/mcp
```

Logged-in customer assertion endpoint:

```text
/wp-json/rag-sync/v1/mcp/customer/assertion
```

If the official WordPress MCP Adapter is active, RAG Sync also registers public
catalog and content abilities. Customer and order tools stay behind the plugin's
token-protected fallback endpoint.

## Documentation

- Customer user guide: [docs/user-guide.md](docs/user-guide.md)
- Order tools setup: [docs/order-tools-setup.md](docs/order-tools-setup.md)
- WordPress.org publishing checklist: [MARKETPLACE_CHECKLIST.md](MARKETPLACE_CHECKLIST.md)

## Security And Privacy

RAG Sync sends data only to the Backend URL configured by the site
administrator. Webhooks are signed with the configured webhook secret. MCP tools
require a bearer token created in the plugin settings.

Logged-in customer order tools require a short-lived assertion issued by this
WordPress site. Guest order verification asks for order number plus billing
email or phone in a widget form so those values are not sent through normal chat
text or included in the LLM prompt.

Do not place widget API keys, webhook secrets, or MCP tokens in public pages,
browser JavaScript outside the provided widget config, logs, screenshots, or
support tickets.
