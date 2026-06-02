/**
 * RAG Sync widget loader.
 *
 * Reads configuration from the localized `RAGSyncConfig` object, warms the
 * cross-origin connection, loads the remote AskRAG widget bundle, then fetches
 * the tenant config and initializes the widget. Kept as a local enqueued script
 * so no executable code is injected inline.
 */
(function () {
    'use strict';

    var config = window.RAGSyncConfig || {};
    var scriptUrl = config.scriptUrl;
    var configUrl = config.configUrl;

    if (!scriptUrl || !configUrl) {
        return;
    }

    // Warm the cross-origin connection and prefetch the bundle early so the
    // chat button appears quickly even on heavy pages (otherwise the async
    // script pays a fresh DNS+TLS handshake to the API host and is deprioritized).
    try {
        var apiOrigin = new URL(scriptUrl, window.location.href).origin;
        ['preconnect', 'dns-prefetch'].forEach(function (rel) {
            var link = document.createElement('link');
            link.rel = rel;
            link.href = apiOrigin;
            if (rel === 'preconnect') {
                link.crossOrigin = '';
            }
            document.head.appendChild(link);
        });
        var preload = document.createElement('link');
        preload.rel = 'preload';
        preload.as = 'script';
        preload.href = scriptUrl;
        document.head.appendChild(preload);
    } catch (e) {
        /* preconnect/preload are best-effort */
    }

    var script = document.createElement('script');
    script.src = scriptUrl;
    script.async = true;
    script.onload = function () {
        if (typeof window.RAGWidget === 'undefined') {
            console.error('RAG Widget: RAGWidget not found after script load');
            return;
        }

        fetch(configUrl, {
            headers: config.apiKey ? { 'X-Api-Key': config.apiKey } : {}
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Config fetch failed: ' + response.status);
                }
                return response.json();
            })
            .then(function (remoteConfig) {
                window.RAGWidget.init(Object.assign({}, remoteConfig, {
                    apiUrl: config.apiBaseUrl,
                    apiKey: config.apiKey,
                    customer: config.customer,
                    session: config.session,
                    skipRemoteConfig: true,
                    debug: config.debug
                }));
            })
            .catch(function (error) {
                console.error('RAG Widget: Failed to initialize', error);
            });
    };
    script.onerror = function () {
        console.error('RAG Widget: Failed to load widget script');
    };
    document.head.appendChild(script);
})();
