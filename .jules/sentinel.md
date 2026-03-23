## 2024-05-18 - Misplaced SSL Context Options
**Vulnerability:** The `verify_peer` stream context option was incorrectly placed inside the `http` array instead of the `ssl` array in `stream_context_create`.
**Learning:** PHP's `file_get_contents` silently ignores invalid context options. Placing SSL-specific options inside the HTTP context means they are completely ignored, potentially leaving connections vulnerable to Man-In-The-Middle (MITM) attacks on older PHP versions or environments where the default stream context has `verify_peer` disabled.
**Prevention:** Always verify that stream context options are placed in the correct protocol array (e.g., `ssl` for SSL/TLS options, `http` for HTTP options) as per the PHP documentation.
