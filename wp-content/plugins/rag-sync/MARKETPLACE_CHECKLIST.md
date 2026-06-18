# WordPress.org Publishing Checklist

Use this checklist before submitting RAG Sync to the WordPress.org plugin
directory.

## Package Metadata

- Main plugin file: `rag-sync.php`.
- Plugin version and `RAG_SYNC_VERSION` must match.
- `readme.txt` `Stable tag` must match the plugin version.
- `readme.txt` must keep a short one-line description and valid headers.
- `Requires at least`, `Requires PHP`, and `Tested up to` must reflect versions
  actually supported or tested.
- `WC requires at least` and `WC tested up to` in `rag-sync.php` must be reviewed
  for every WooCommerce-facing release.

## Required Directory Content

- `readme.txt`
- `uninstall.php`
- `index.php` silence files in public directories
- plugin assets under `assets/`
- source files under `includes/`

Do not ship local development files, caches, node modules, test artifacts, or
publishing notes in the final ZIP.

## External Services Disclosure

`readme.txt` must explain:

- the configured AskRAG backend URL is an external service
- when content sync sends posts, pages, products, categories, and coupons
- when widget chat sends visitor chat/session data
- when MCP can return public catalog data and verified customer/order data
- the provider URL, Terms of Service URL, and Privacy Policy URL

## Order Tool Disclosure

For releases with MCP order tools, mention:

- logged-in order status uses a same-origin customer assertion
- guest order verification uses order number plus billing email or phone
- guest responses are limited and do not expose address, email, phone, payment,
  or raw order payloads

## Release Checks

1. Run the official Plugin Check plugin and fix all errors.
2. Install on a clean WordPress site.
3. Activate with WooCommerce disabled and confirm posts/pages still work.
4. Activate WooCommerce and confirm product sync, widget, and MCP tools.
5. Run the AskRAG MCP health command for a WooCommerce tenant.
6. Test logged-in and guest order status from the widget.
7. Build the ZIP with `.distignore` rules.
8. Submit the ZIP to WordPress.org for initial review.

## SVN Release Notes

After approval:

1. Commit code to `trunk/`.
2. Put listing images in SVN `/assets/`, not the plugin ZIP.
3. Create `tags/VERSION/`.
4. Ensure `readme.txt` in the tag has the same `Stable tag`.
