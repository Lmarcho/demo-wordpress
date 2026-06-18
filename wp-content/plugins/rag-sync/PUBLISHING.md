# RAG Sync — Publishing Checklist

Dev-only document. **Exclude from the distributed ZIP and from SVN trunk** (it is
not part of the plugin). Tracks the steps to ship RAG Sync via self-hosting and
the WordPress.org plugin directory.

---

## ✅ Done (in repo)

- [x] Real plugin header metadata (AskRAG / askrag.app), GPLv2.
- [x] `readme.txt` in WordPress.org format (Description, Installation, FAQ, Changelog).
- [x] `uninstall.php` removes options, custom table, transient, scheduled hook.
- [x] Output escaping pass (`esc_html_e` / `esc_attr_e` / `esc_html__`, `esc_url`).
- [x] `wp_unslash()` before sanitizing `$_GET` / `$_COOKIE`.
- [x] Public REST `/health` no longer leaks WP/PHP/WC versions or site URL.
- [x] `index.php` silence stubs in subdirectories.
- [x] Widget loader refactored to `wp_enqueue_script` + `wp_localize_script`
      (local `assets/js/widget-loader.js`; no inline injection).
- [x] `Update URI` header removed (WordPress.org is the canonical update source).
- [x] `External Services` disclosure section in `readme.txt`.
- [x] Scoped `phpcs:disable` on the read-only admin list filter.
- [x] MCP order tools setup guide in `docs/order-tools-setup.md`.
- [x] Marketplace checklist in `MARKETPLACE_CHECKLIST.md`.
- [x] `.distignore` for cleaner self-hosted ZIP builds.

---

## ⬜ Before submitting (your action items)

- [ ] **Privacy & Terms pages live** at https://askrag.app/privacy and
      https://askrag.app/terms (reviewers click these). Update `readme.txt` if
      the real URLs differ.
- [x] **`Tested up to:`** in `readme.txt` — verified locally with WordPress 6.9.
- [x] **`WC tested up to:`** in `rag-sync.php` — verified locally with
      WooCommerce 10.4.3.
- [ ] **`Contributors:`** in `readme.txt` — replace `askrag` with a real
      WordPress.org account username.
- [ ] **Register a WordPress.org account** (the contributor above) if not done.
- [ ] **Run Plugin Check** (install the official "Plugin Check (PCP)" plugin) on
      `rag-sync`. Fix any **errors**. Expected, acceptable **warnings**: direct
      `$wpdb` queries on the admin table, `DROP TABLE` interpolation.
- [ ] **Smoke test** on a clean WP install: activate, configure, Test Connection,
      Trigger Full Sync, enable widget and confirm it loads + chats.
- [ ] **Decide trademark/name** — confirm "RAG Sync" / slug `rag-sync` is free of
      conflicts in the directory.

---

## ⬜ Directory listing assets (committed to SVN `/assets/`, NOT the ZIP)

- [ ] `icon-256x256.png` (and optionally `icon-128x128.png`).
- [ ] `banner-772x250.png` (and optionally `banner-1544x500.png` for retina).
- [ ] At least one `screenshot-1.png` (+ matching captions in `readme.txt`).

---

## ⬜ Submission (WordPress.org)

- [ ] Build a clean ZIP of the `rag-sync` folder **excluding** dev files
      (`PUBLISHING.md`, `.git`, `node_modules`, maps, tests). Consider a
      `.distignore`.
- [ ] Submit the ZIP at https://wordpress.org/plugins/developers/add for human
      review (first version only; allow days–weeks).
- [ ] On approval, you receive an **SVN** repo. Add code to `trunk/`, tag a
      release under `tags/1.0.0/`, and put listing images in `assets/`.
- [ ] `svn ci` to publish. The directory builds the downloadable ZIP from SVN.

---

## ⬜ Self-hosting (in parallel)

- [ ] Offer the same ZIP for manual upload (Plugins → Add New → Upload).
- [ ] Do **not** add a self-hosted auto-updater for slug `rag-sync` — it would
      collide with WordPress.org updates. (Use a different slug only if you ever
      need an independent update channel.)

---

## Ongoing (each release)

- [ ] Bump `Version:` in `rag-sync.php` **and** `Stable tag:` in `readme.txt`
      (must match).
- [ ] Add a `== Changelog ==` entry.
- [ ] Update `Tested up to:` as new WordPress versions are verified.
- [ ] Commit to SVN `trunk/`, tag the new version, update `Stable tag`.
