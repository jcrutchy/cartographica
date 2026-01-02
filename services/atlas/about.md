
---

# 🌊 **Atlas service**

The Atlas service is the **trust authority** for the Archipelago protocol.
It issues **island certificates**, stores island metadata, and verifies ownership.

It mirrors the identity service in structure:

```
services/
  atlas/
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

# 🧱 **1. Folder Structure**

Inside your repo:

```
services/
  atlas/
    index.php
    config.php
    routes.php
    controllers/
      RegisterIsland.php
      VerifyCertificate.php
```

Inside your external data folder:

```
cartographica_data/
  services/
    atlas/
      atlas.sqlite
      atlas_private.pem
      atlas_public.pem
      log/
```

---

# 🔐 **2. What the Atlas service Does**

### ✔ Registers islands  
Islands send:

- their public key
- their name
- the owner’s email
- optional metadata

### ✔ Issues certificates  
The Atlas service signs a certificate containing:

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
atlas_private.pem
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
