
---

# 🌊 **Island Directory**

The Island Directory is the **trust authority** for the Archipelago.  
It issues **island certificates**, stores island metadata, and verifies ownership.

It mirrors the identity service in structure:

```
services/
  island-directory/
    index.php
    config.php
    routes.php
    controllers/
      RegisterIsland.php
      VerifyCertificate.php
    templates/
      (optional email templates later)
```

And it uses the same shared utilities:

- `Router`  
- `Request`  
- `Response`  
- `Logger`  
- `Crypto`  
- `Keys`  
- `SharedConfig`  
- `Template` (if needed)  

---

# 🧱 **1. Directory Structure**

Inside your repo:

```
services/
  island-directory/
    index.php
    config.php
    routes.php
    controllers/
      RegisterIsland.php
      VerifyCertificate.php
```

Inside your external data directory:

```
cartographica_data/
  services/
    island-directory/
      island-directory.sqlite
      island-directory_private.pem
      island-directory_public.pem
      log/
```

---

# 🔐 **2. What the Island Directory Does**

### ✔ Registers islands  
Islands send:

- their public key  
- their name  
- the owner’s email  
- optional metadata  

### ✔ Issues certificates  
The directory signs a certificate containing:

- island public key  
- island name  
- owner email  
- issued_at  
- expires_at  

### ✔ Verifies certificates  
Islands and clients can POST a certificate to check validity.

### ✔ Stores island metadata  
In a SQLite database.

---

# 🧩 **3. Certificate Format**

We’ll use a simple JSON payload, signed with Ed25519:

```json
{
  "public_key": "<island public key>",
  "name": "My Island",
  "owner": "player@example.com",
  "issued_at": 1700000000,
  "expires_at": 1702592000
}
```

Signed using:

```
island-directory_private.pem
```

---

# 🧭 **4. Routes**

```
POST register_island
POST verify_certificate
```

Later we can add:

- list_islands  
- get_island_info  
- update_metadata  

But for now, we keep it minimal.

---
