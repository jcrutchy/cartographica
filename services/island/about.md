
---

# 🏝️ **Island Server**

The Island Server is:

- a **game world instance**
- a **trusted island** in the Archipelago
- a **service that verifies device tokens**
- a **service that presents its island certificate**
- a **service that exposes island metadata**
- eventually: the place where gameplay logic lives

It sits between:

- **Identity Service** (to verify humans)
- **Atlas Service** (to prove the island is legitimate)
- **Game Client** (to actually play)

Think of it as the “gameplay node” in the Archipelago.

---

# 🧱 **1. Folder Structure**

Inside your repo:

```
services/
  island/
    index.php
    config.php
    routes.php
    controllers/
      Handshake.php
      GetIslandInfo.php
    schema.sql
```

Inside your external data folder:

```
cartographica_data/
  services/
    island/
      island_private.pem
      island_public.pem
      island.sqlite
      island_config.json
      log/
```

---

# 🔐 **2. What the Island Server Does (Phase 1)**

We’ll start with the minimal viable island:

### ✔ Loads its island certificate  
Issued by the Atlas service.

### ✔ Verifies device tokens  
By calling the Identity Service’s `/verify`.

### ✔ Exposes island metadata  
Name, description, tags, version, etc.

### ✔ Performs the Archipelago handshake  
Client → Island → Identity → Island → Client

### ✔ Stores local island config  
In `island_config.json`.

### ✔ Has a SQLite DB  
For future gameplay data.

This is enough to:

- let a player connect  
- verify their identity  
- verify the island’s identity  
- return island info  
- prepare for gameplay logic  

---

# 🧩 **3. Routes (Phase 1)**

```
POST handshake
GET  island_info
```

Later we’ll add:

- `POST join_world`
- `POST update_state`
- `GET  world_snapshot`
- `POST leave_world`
- etc.

But for now, we keep it minimal.

---

# 🧭 **4. The Handshake Flow**

Here’s the handshake between client and island:

```
Client → Island: device_token
Island → Identity: verify(device_token)
Identity → Island: valid + payload
Island → Client: island certificate + island metadata
Client: verifies certificate using Atlas public key
```

This establishes:

- human identity  
- island identity  
- trust on both sides  

---
