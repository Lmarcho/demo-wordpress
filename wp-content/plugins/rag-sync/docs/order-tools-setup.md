# MCP Order Tools Setup

This guide covers the WooCommerce order tools used by the AskRAG widget.

## Required Versions

- RAG Sync plugin: 1.0.4 or later.
- WordPress: 6.0 or later. Tested locally with WordPress 6.9.
- WooCommerce: 8.0 or later for order tools. Tested locally with WooCommerce 10.4.3.
- AskRAG backend: must support the widget order verification endpoint and the
  `commerce:mcp:health` command with `verify_guest_order`.

## Tools

The plugin exposes these order tools when MCP is enabled:

- `get_order_status`: for logged-in customers. Requires a short-lived customer
  assertion issued by this WordPress site.
- `verify_guest_order`: for guest checkout. Requires order number plus the
  billing email or phone used on the order.

Both tools are read-only. Guest verification returns only order number, status,
placed date, currency, total, item count, and shipping method summary.

## Order Number Lookup

The order tools resolve exact WooCommerce order IDs, order numbers with a leading
`#`, common custom order-number metadata, and a bounded scan of the logged-in
customer's own recent orders. Guest verification does not scan all orders by
contact; it first resolves an exact order candidate and then verifies the
submitted billing email or phone.

Stores using a custom order-number plugin can extend lookup with these filters:

- `rag_sync_mcp_order_number_meta_keys`: add plugin-specific order-number meta
  keys.
- `rag_sync_mcp_order_lookup_candidates`: return matching `WC_Order` objects or
  order IDs for a submitted order number.
- `rag_sync_mcp_customer_order_scan_limit`: adjust the bounded logged-in
  customer order scan limit. The default is 200.

## WordPress Setup

1. Install and activate RAG Sync.
2. Go to Settings > RAG Sync.
3. Configure Backend URL, Tenant Slug, API Key, Webhook Secret, and Webhook Endpoint.
4. Enable MCP.
5. Create an MCP client token in the MCP section.
6. Copy the MCP endpoint and token into the AskRAG tenant WooCommerce settings.

The fallback MCP endpoint is:

```text
/wp-json/rag-sync/v1/mcp
```

The logged-in customer assertion endpoint is:

```text
/wp-json/rag-sync/v1/mcp/customer/assertion
```

If the official WordPress MCP adapter is installed and active, the plugin also
registers public catalog/content RAG Sync abilities for compatible clients.
Customer and order tools are intentionally not registered as WordPress abilities;
they remain available only through the fallback MCP endpoint with the bearer
token, per-client tool allow-list, and order rate limits.

## Upgrades

After upgrading from an older RAG Sync version, open the plugin settings once or
allow WordPress to load normally. The plugin backfills default MCP clients with
new approved read-only tools, including `verify_guest_order`.

If a client was manually restricted to a custom tool set, update that client or
create a new MCP token.

## Health Check

From the AskRAG backend, run:

```bash
php artisan commerce:mcp:health TENANT_SLUG --platform=woocommerce --json
```

The health check should report:

- `required_tools_present: true`
- no missing tools
- `get_order_status` in the tool list
- `verify_guest_order` in the tool list

## Widget Behavior

- Logged-in WooCommerce customers asking about an order get a same-origin
  customer assertion. AskRAG then calls `get_order_status`.
- Guests asking about order status see a compact verification form in the widget.
  The form asks for order number and billing email or phone.
- The billing email or phone is posted to the AskRAG verification endpoint, not
  sent through normal chat text and not included in the LLM prompt.
- Failed or mismatched checks return a generic message so order numbers cannot
  be enumerated.
- Product questions that contain similar words, such as "track pants", continue
  through normal product chat.

## Manual Test Cases

1. Logged-in customer asks: "Where is order 1001?"
2. Guest asks: "Track order 1001" and submits the correct billing email.
3. Guest submits the correct order number with the wrong email.
4. Guest submits the correct order number with the correct billing phone.
5. Guest submits an order number from another store or tenant.
6. Visitor asks a product question such as "Show me track pants."
