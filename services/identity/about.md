
---

# 🔐 **Identity Service**

The Identity Service is the **human authentication authority** for the Archipelago.  
It handles email‑based login, issues device tokens, and verifies them for islands.

It mirrors the island-directory service in structure:

```
services/
  identity/
    index.php
    config.php
    routes.php
    controllers/
      RequestLogin.php
      Redeem.php
      Verify.php
    templates/
      login_email.html
    Mailer.php
```

And it uses the shared framework:

- `Router`  
- `Request`  
- `Response`  
- `Logger`  
- `Crypto`  
- `Keys`  
- `SharedConfig`  
- `Template`  
- `Smtp`  

---

# 🧱 **1. Directory Structure**

Inside the repo:

```
services/
  identity/
    index.php
    config.php
    routes.php
    controllers/
      RequestLogin.php
      Redeem.php
      Verify.php
    Mailer.php
    templates/
      login_email.html
```

Inside the external data directory:

```
cartographica_data/
  services/
    identity/
      identity_private.pem
      identity_public.pem
      identity.sqlite
      smtp_credentials.txt
      log/
```

The Identity Service stores:

- its Ed25519 keypair  
- its SQLite database  
- SMTP credentials  
- logs  

All sensitive data lives **outside** the repository.

---

# ✉️ **2. What the Identity Service Does**

### ✔ Handles login requests  
Users submit their email address.  
The service generates a short‑lived login token and emails a login link.

### ✔ Sends login emails  
Using:

- `Mailer.php`  
- `Smtp.php`  
- `Template.php`  
- `smtp_credentials.txt`  

Email templates live in `templates/`.

### ✔ Redeems login tokens  
When a user clicks the link, the service:

- verifies the login token  
- checks expiry  
- issues a **device token**  

### ✔ Issues device tokens  
Device tokens are signed JSON payloads containing:

- email  
- issued_at  
- expires_at  

These tokens authenticate humans to islands.

### ✔ Verifies device tokens  
Islands POST device tokens to the identity service to confirm validity.

---

# 🔑 **3. Token Formats**

## Login Token (short‑lived)

Payload:

```json
{
  "email": "player@example.com",
  "issued_at": 1700000000,
  "expires_at": 1700000300
}
```

Signed with:

```
identity_private.pem
```

Used only for login links.

---

## Device Token (long‑lived)

Payload:

```json
{
  "email": "player@example.com",
  "issued_at": 1700000000,
  "expires_at": 1702592000
}
```

Also signed with:

```
identity_private.pem
```

Used by islands to authenticate players.

---

# 🧭 **4. Routes**

```
POST request_login
POST redeem
POST verify
```

### `request_login`
- Accepts an email  
- Generates a login token  
- Sends a login link via email  

### `redeem`
- Accepts a login token  
- Verifies it  
- Issues a device token  

### `verify`
- Accepts a device token  
- Verifies signature + expiry  
- Returns payload  

---

# 🧩 **5. Email Templates**

Email templates live in:

```
services/identity/templates/login_email.html
```

Example:

```html
<p>Hello {{email}},</p>

<p>Click the link below to log in:</p>

<p><a href="{{link}}">{{link}}</a></p>
```

Rendered using:

- `Template::render()`  
- `Mailer::sendLoginLink()`  

---

# 🧰 **6. Shared Utilities Used**

The Identity Service relies heavily on the shared framework:

### **Crypto**
- Ed25519 signing  
- Ed25519 verification  

### **Keys**
- Ensures keypair exists  
- Generates keys if missing  

### **Logger**
- Writes logs to `cartographica_data/services/identity/log/`  

### **SharedConfig**
- Loads SMTP host/port  
- Loads admin email  
- Loads web_root for login links  

### **Template**
- Renders HTML email templates  

### **Smtp**
- Sends email via SMTP  

---

# 🗄️ **7. Database**

The identity service uses a small SQLite database:

```
identity.sqlite
```

Currently used for:

- logging login attempts (optional)
- future rate limiting
- future account metadata

The schema is intentionally minimal and can grow as needed.

---

# 🔐 **8. Security Model**

- No passwords are stored  
- No password resets  
- No shared secrets  
- All authentication is via signed tokens  
- Tokens include expiry timestamps  
- Private keys never leave the server  
- Public keys are distributed to islands  

This keeps the system simple, secure, and user‑friendly.

---

# 🧭 **9. Future Extensions**

- Rate limiting login attempts  
- Email verification throttling  
- Account metadata  
- Admin login notifications  
- Multi‑device token management  
- Optional OAuth integration  

---
