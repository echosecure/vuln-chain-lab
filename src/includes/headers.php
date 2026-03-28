<?php

// CSP: restricts script sources to same origin but allows inline scripts
// and eval(). This is a common misconfiguration — the policy looks secure
// but the unsafe-inline and unsafe-eval directives undermine it for XSS.
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");

// CORS: no Access-Control-Allow-Origin header sent at all.
// Without it, browsers block cross-origin requests by default.
// This is correct — the app has no reason to allow cross-origin access.

// Standard security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: strict-origin-when-cross-origin");
