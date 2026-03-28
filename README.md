# File Upload Bypass + Stored XSS PoC Lab

A deliberately vulnerable web application demonstrating how a file upload bypass chains with stored XSS to create backdoor admin accounts, even when CSP, CORS, and CSRF protections are in place.

Full Blog Post: [KurtiseBear Blog](https://kurtisebear.com/2026/03/28/chaining-file-upload-xss-admin-compromise/)

**This is an educational lab for defensive security training. Do not deploy this anywhere publicly accessible.**

## What This Demonstrates

The application has real security controls:

- **Content Security Policy** restricting script sources to `'self'` (but with `'unsafe-inline'` and `'unsafe-eval'`)
- **No CORS headers** sent, so cross-origin requests are blocked by browsers
- **CSRF tokens** on all form submissions (messages, file uploads)
- **Standard security headers** (X-Content-Type-Options, X-Frame-Options, Referrer-Policy)

An attacker with a low-privilege user account chains two vulnerabilities to bypass all of them:

1. **File upload bypass** -- The upload form restricts to `.pdf` via a client-side `accept` attribute, but the server performs zero file type validation. An attacker uploads a `.js` file containing JavaScript. The download endpoint serves it from the same origin, so CSP and CORS don't block it.

2. **Stored XSS via message subject** -- The messaging feature stores user input without sanitisation. The admin inbox renders the message subject as raw HTML. The XSS payload uses an `<img onerror>` handler to fetch the uploaded script and `eval()` it. CSP allows this because `'unsafe-inline'` and `'unsafe-eval'` are permitted.

3. **Missing CSRF on API endpoint** -- The user management API (`/api/manage-user.php`) doesn't validate CSRF tokens, even though form endpoints do. The XSS payload calls this API using the admin's same-origin session. Even if CSRF were present, same-origin JavaScript could read the token from the DOM.

The result: when an admin opens their inbox, the XSS fires, the JavaScript creates a backdoor admin account using the admin's session. Every defence is in place and functioning. The chain works because it never leaves the origin.

## Prerequisites

- Docker
- Docker Compose

## Setup

```bash
docker-compose up -d
```

Wait 10-15 seconds for MySQL to initialise, then visit http://localhost:8080

### Credentials

| Role  | Email              | Password |
|-------|--------------------|----------|
| Admin | admin@company.com  | admin    |
| User  | user@company.com   | user     |

## Attack Walkthrough

### Step 1: Login as the regular user

Navigate to http://localhost:8080 and login with `user@company.com` / `user`.

### Step 2: Upload the payload

Go to **Upload Files**. The form says "PDF only" but only enforces this client-side. Either:
- Use browser dev tools to remove the `accept=".pdf"` attribute from the file input, or
- Use curl/Burp to upload directly (you'll need to include the CSRF token from the form)

Upload the provided `payload.js` (or your own). Note the file ID returned (e.g., `1`).

The uploaded file is now served from `/api/download.php?file_id=1` on the same origin. CSP won't block fetches to this endpoint because it's `'self'`.

### Step 3: Craft the XSS message

Go to **Send Message**. In the subject field, enter:

```html
<img src=x onerror="fetch('/api/download.php?file_id=1').then(r=>r.blob()).then(b=>b.text()).then(eval)">
```

(Replace `1` with the actual file ID from step 2.)

Put anything in the body. Tick priority if you want it at the top of the inbox. Send.

The `onerror` handler works because CSP allows `'unsafe-inline'`. The `eval()` works because CSP allows `'unsafe-eval'`. The fetch to the download endpoint works because it's same-origin.

### Step 4: Wait for the admin to check their inbox

Log out. Log in as `admin@company.com` / `admin`. Go to **Inbox**.

The message subject renders as raw HTML. The `<img>` tag fails to load, the `onerror` handler fires, fetches the uploaded payload, and `eval()` executes it. The payload POSTs to `/api/manage-user.php` using the admin's session cookie (attached automatically for same-origin requests). No CSRF token needed because the API endpoint doesn't check for one.

### Step 5: Verify the backdoor

Go to **Users**. You should see a new user: `BackdoorAdmin` with role `admin` and email `attacker@evil.com`.

Log out and log in with `attacker@evil.com` / `Compromised1!` to confirm.

## Why The Defences Failed

```
CSP blocks external scripts
  --> But the payload is hosted on the same origin via file upload
  --> And unsafe-inline/unsafe-eval allow the onerror handler and eval()

CORS blocks cross-origin requests
  --> But every request in the chain is same-origin

CSRF tokens protect form submissions
  --> But the API endpoint doesn't validate them
  --> And even if it did, same-origin JS can read tokens from the DOM

Session cookies have standard protections
  --> But same-origin requests carry them automatically
```

The defences are all working correctly. They're designed to stop cross-origin attacks. This chain never leaves the origin.

## Defensive Measures

What would actually break this chain:

1. **Server-side file type validation** -- Check MIME type, file extension, and magic bytes. Don't trust the client. This prevents the attacker from hosting a payload on your origin.
2. **Output encoding** -- Use `htmlspecialchars()` on all user-controlled output. The inbox renders `$row['subject']` raw. This kills the XSS entirely.
3. **Strict CSP** -- Remove `'unsafe-inline'` and `'unsafe-eval'`. Use nonces or hashes for legitimate inline scripts. This blocks the `onerror` handler and `eval()`.
4. **Content-Disposition: attachment** -- Force downloads instead of inline rendering for user-uploaded files. This prevents the browser from interpreting uploaded content.
5. **CSRF on all state-changing endpoints** -- Including API endpoints, not just forms.
6. **Access controls on uploads** -- The download API serves any file to any authenticated user. Files should be scoped to their owner.

## Cleanup

```bash
docker-compose down -v
```

## Disclaimer

This application is deliberately vulnerable. It is designed for educational and defensive security training purposes only. Do not deploy it on any network accessible to untrusted users. Do not use these techniques against systems without explicit written authorisation.
