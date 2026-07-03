# RAG Sync Customer User Guide

Plugin: RAG Sync

Version: 1.0.4

Audience: WordPress administrators, WooCommerce store operators, and AskRAG
integration operators

## 1. Overview

RAG Sync connects your WordPress or WooCommerce site to an AskRAG backend. The
plugin helps the AskRAG chat widget answer customer questions from your own site
content while still using live commerce data when needed.

The plugin has three main jobs:

- Content sync: sends selected posts, pages, products, categories, and coupons to
  the configured AskRAG backend.
- Chat widget: loads the AskRAG storefront widget and passes it the tenant,
  session, and customer context needed for chat.
- MCP live tools: optionally exposes token-protected read-only tools for current
  catalog, promotion, cart, purchase-history, and order-status data.

RAG Sync does not create, edit, or delete WordPress posts, WooCommerce products,
customers, orders, coupons, carts, or payments through chat. The MCP tools are
read-only.

## 2. Requirements

| Requirement | Supported value |
| --- | --- |
| WordPress | 6.0 or later |
| PHP | 8.0 or later |
| AskRAG backend | Tenant slug, widget API key, webhook secret, and webhook endpoint |
| WooCommerce | Optional; required for product, coupon, order, cart, and purchase-history tools |
| Browser widget | Loads from the configured AskRAG backend URL |

## 3. Installation

1. Upload the `rag-sync` folder to `wp-content/plugins/`, or install the ZIP from
   Plugins > Add New > Upload Plugin.
2. Activate RAG Sync from the Plugins screen.
3. Open Settings > RAG Sync.
4. Configure the connection fields from the AskRAG tenant.
5. Save changes.
6. Click Test Connection.
7. Click Trigger Full Sync when the connection succeeds.

## 4. Connection Settings

Open Settings > RAG Sync.

| Field | Purpose |
| --- | --- |
| Backend URL | Base URL of the AskRAG backend, for example `https://askrag.app`. |
| Tenant Slug | The tenant slug assigned in AskRAG. |
| API Key | Widget API key, usually beginning with `wgt_`. |
| Webhook Secret | Shared secret used to sign sync webhook requests. |
| Webhook Endpoint | AskRAG endpoint that receives WordPress and WooCommerce sync webhooks. |
| Enable Sync | Sends automatic sync webhooks when selected content changes. |

Use Test Connection to verify the webhook endpoint and secret before enabling
production sync.

## 5. Content Types

RAG Sync can sync:

- Blog posts.
- Pages.
- WooCommerce products.
- WooCommerce product categories.
- WooCommerce coupons.

WooCommerce content types appear only when WooCommerce is active. Disable content
types that should not be indexed by the AskRAG tenant.

## 6. Full Sync And Ongoing Sync

Use Trigger Full Sync after first setup or after changing the content-type
selection. A full sync sends the currently selected content to AskRAG.

When Enable Sync is active, RAG Sync sends signed webhooks as content changes.
The sync status table in the settings page shows tracked items, status, last
sync time, and webhook status.

## 7. Chat Widget

Enable Widget to show the AskRAG chat widget on the storefront. Widget colors,
welcome messages, position, and voice settings are managed in the AskRAG backend,
not inside WordPress.

The widget sends chat messages and session context to the configured AskRAG
backend. For logged-in users, the widget can include WordPress/WooCommerce
customer context so AskRAG can support customer-authorized workflows. Customer
identity in browser context is not enough to authorize private data by itself;
order and account tools require proof from this WordPress site.

To force a one-time widget cache refresh on a page, append `?refresh` to the page
URL.

## 8. MCP Live Data

MCP is optional. Enable it when AskRAG should fetch live read-only data directly
from this WordPress/WooCommerce site.

### Setup

1. Open Settings > RAG Sync.
2. Enable MCP.
3. Create an MCP client token.
4. Copy the fallback endpoint and token into the AskRAG tenant's WooCommerce
   integration settings.

Fallback endpoint:

```text
/wp-json/rag-sync/v1/mcp
```

Logged-in customer assertion endpoint:

```text
/wp-json/rag-sync/v1/mcp/customer/assertion
```

### Public WordPress MCP Abilities

When the official WordPress MCP Adapter is installed and active, RAG Sync
registers public catalog and content abilities for compatible clients. Customer
and order tools are not registered as public WordPress abilities. They remain
available only through the plugin's token-protected fallback MCP endpoint.

## 9. MCP Tools

Depending on WooCommerce availability and client permissions, the MCP server can
provide:

- Store context.
- Live product data.
- Product search.
- Category products.
- Product variants.
- Related products.
- Active public promotions.
- Product popularity counts.
- Published post and page content.
- Logged-in customer order status.
- Guest order verification.
- Logged-in customer cart summary.
- Logged-in customer purchase-history summary.

All tools are read-only. The AskRAG backend uses these responses to build product
cards, promotion cards, comparison tables, and grounded chat answers.

## 10. Promotions And Coupon Codes

RAG Sync can return public WooCommerce promotion summaries. Coupon codes are only
returned when the code is listed in Public Coupon Codes in the MCP settings.
Leave that field blank to hide all coupon codes from MCP promotion tools.

## 11. Logged-In Customer Order Status

Logged-in WooCommerce customers can ask the widget about their own orders. The
widget requests a short-lived customer assertion from this WordPress site. AskRAG
then uses the assertion to call the read-only `get_order_status` MCP tool.

Order responses exclude billing address, shipping address, customer email,
telephone, payment details, internal notes, and raw order payloads.

## 12. Guest Order Verification

Guests asking about order status see a compact widget verification form. The form
asks for:

- Order number.
- Billing email or billing phone used on the order.

Those values are posted to the AskRAG verification endpoint and are not sent in
normal chat text or included in the LLM prompt. Failed checks return a generic
message so order numbers cannot be enumerated.

## 13. Security And Privacy Boundaries

- Content sync sends data only to the configured Backend URL.
- Webhook requests are signed with the webhook secret.
- MCP requires a bearer token created in the plugin settings.
- MCP tokens are shown once. Store them securely.
- Customer and order tools require either a logged-in customer assertion or guest
  order verification.
- Browser-provided customer IDs, emails, or login flags do not authorize private
  data.
- The plugin does not expose payment data, billing addresses, shipping addresses,
  product cost, administrator records, or arbitrary database queries.
- Do not include API keys, webhook secrets, MCP tokens, customer assertions,
  billing emails, phone numbers, addresses, or payment details in support notes.

## 14. Health Check

From the AskRAG backend, run:

```bash
php artisan commerce:mcp:health TENANT_SLUG --platform=woocommerce --json
```

The health check should confirm:

- Required tools are present.
- The configured endpoint authenticates successfully.
- Store context resolves.
- Order tools are present when order workflows are enabled.

## 15. Troubleshooting

| Symptom | Check |
| --- | --- |
| Test Connection fails | Confirm Backend URL, Webhook Endpoint, and Webhook Secret. |
| Full sync fails | Confirm the AskRAG backend can receive the webhook and that the content type is enabled. |
| Widget does not show | Confirm Enable Widget is checked and Backend URL, Tenant Slug, and API Key are set. |
| Widget shows stale script | Append `?refresh` to the page URL once, then reload. |
| Product cards are missing | Confirm WooCommerce is active, products are published, sync has run, and MCP is configured for live product cards. |
| Coupon code is hidden | Add the code to Public Coupon Codes, or leave it hidden intentionally. |
| Logged-in order status fails | Confirm the user is logged in to this WordPress site and the customer assertion endpoint is reachable. |
| Guest order verification fails | Confirm the order number and billing email or phone match the order. |

## 16. Upgrade Checklist

1. Back up the site according to your normal maintenance policy.
2. Update the plugin.
3. Visit Settings > RAG Sync once after the update so MCP tables and default
   tools can be backfilled.
4. Run Test Connection.
5. Run Trigger Full Sync when content contracts or sync behavior changed.
6. Run the AskRAG MCP health check.
7. Confirm product, promotion, logged-in order, and guest order questions in the
   widget.

## 17. Manual Verification

- Activate RAG Sync with WooCommerce disabled and confirm posts/pages still work.
- Activate WooCommerce and confirm products, categories, and coupons sync.
- Enable the widget and confirm it loads on the storefront.
- Enable MCP, create a token, and run the AskRAG health check.
- Ask a product question and confirm product cards use live price and stock.
- Ask a promotion question and confirm only approved coupon codes are shown.
- Ask an order-status question as a logged-in customer.
- Ask an order-status question as a guest and complete the verification form.
