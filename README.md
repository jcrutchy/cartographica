# 📘 **Cartographica — Project README**

## 🌍 Overview

Cartographica is a web-based civilization/resource simulation game.

It has been developed with the assistance of AI tools including Copilot and Gemini.

Cartographica is an experimental, decentralized, node‑based game world built around a simple idea:

> **The world is infinite, the nodes are autonomous, and the protocol is the game.**

Instead of a monolithic game engine, Cartographica defines a **protocol** that clients and servers can implement in any language. The world is composed of **tilemap nodes**, each handled by its own server process. Nodes form an infinite graph, connected by coordinate offsets. Players move between nodes seamlessly, carrying their identity and state with them.

This repository contains the early foundations of the project, including:

- The **Identity Service** (a small certificate‑authority‑like service)
- Early protocol ideas
- Node server concepts
- Authentication and world‑state design

---

## 🧠 Project Vision

Cartographica aims to be:

- **Decentralized** — any node server can be written in any language and hosted anywhere.
- **Federated** — multiple operators can run their own worlds or connect into shared ones.
- **Procedural** — each node generates its tilemap from a seed.
- **Persistent** — players have a stable identity across nodes and devices.
- **Protocol‑driven** — the “game” is defined by messages, not by a specific engine.

Think of it as a cross between:

- IRC (federated servers)
- Minecraft (persistent identity + world)
- MUDs (text‑first protocol)
- A distributed graph database (world topology)

---

## 🏛️ Architecture Summary

Cartographica consists of three conceptual layers:

### 1. **Identity Layer (Centralized CA‑like service)**
- Issues long‑lived device tokens
- Verifies email login links
- Signs identity payloads using OpenSSL
- Provides a stable `player_id` for each user
- Does **not** store world state

### 2. **World Layer (Decentralized node servers)**
- Each node is a tilemap server
- Nodes generate terrain from a seed
- Nodes store local world state keyed by `player_id`
- Nodes verify identity tokens using the CA’s public key
- Nodes do **not** need to talk to each other directly

### 3. **Client Layer**
- Connects to node servers via WebSocket
- Sends `AUTH` with signed token
- Renders tilemaps, entities, and movement
- Can be implemented in any language

---

## 🔐 Identity Service Overview

The Identity Service acts like a **mini certificate authority**:

- Users authenticate via **email magic links**
- The service issues **long‑lived device tokens**
- Tokens contain:
  - `player_id` (permanent identity)
  - `issued_at`
  - `expires_at`
- Tokens are **signed** with the CA’s private key
- Node servers verify tokens using the **public key**

This allows:

- Portable identity across devices
- Portable identity across nodes
- No passwords
- No central login during gameplay
- No need for node servers to store email or secrets

---

## 🧩 Node Server Overview

Each node server:

- Accepts WebSocket connections
- Receives `AUTH { token, payload, signature }`
- Verifies the signature using the CA’s public key
- Extracts `player_id`
- Loads or creates local world state for that player
- Generates tilemaps from a deterministic seed
- Handles movement, entities, and node‑local simulation

Nodes are **independent**:

- They don’t share databases
- They don’t coordinate identity
- They don’t need to know about other nodes except via edge definitions

---

## 📡 Protocol Philosophy

Cartographica’s protocol is:

- **Message‑based**
- **Language‑agnostic**
- **Human‑readable (JSON)**
- **Extensible**

Core message types include:

- `HELLO`
- `AUTH`
- `WORLD_STATE`
- `ENTITY_UPDATE`
- `MOVE`
- `TRANSFER` (node boundary crossing)

The protocol is intentionally simple so that:

- Clients can be written in any language
- Node servers can be implemented independently
- The world can grow organically

---

## 📁 Folder Structure

```
repo/
│
├── identity/
│   ├── index.php
│   ├── config.php
│   ├── db.sqlite
│   ├── schema.sql
│   ├── keys/
│   │   ├── ca_private.pem
│   │   └── ca_public.pem
│   └── lib/
│       ├── db.php
│       ├── crypto.php
│       ├── email.php
│       └── util.php
│
└── (future)
    ├── node-server/
    ├── client/
    └── protocol/
```

---

## ⚙️ Setup Instructions

### 1. Install dependencies
- PHP 8+
- SQLite
- OpenSSL
- Apache or Nginx

### 2. Generate CA keys
```
openssl genpkey -algorithm RSA -out ca_private.pem -pkeyopt rsa_keygen_bits:4096
openssl rsa -in ca_private.pem -pubout -out ca_public.pem
```

Place them in:

```
identity/keys/
```

### 3. Initialize SQLite database
```
sqlite3 db.sqlite < schema.sql
```

### 4. Configure email sending
Edit `identity/config.php`:

```php
define("EMAIL_FROM", "no-reply@example.com");
```

---

## 🚀 Running the Identity Service

Point your web server to the `identity/` folder.

Endpoints:

### `POST /identity/index.php?action=request_login`
Request a login link.

### `GET /identity/index.php?action=redeem&token=...`
Redeem login link → returns device token.

### `POST /identity/index.php?action=verify`
Verify token signature (optional).

---

## 🔏 Token Format & Cryptography

### Payload example:
```json
{
  "player_id": "a1f3c9d0e2...",
  "issued_at": 1735689600,
  "expires_at": 1798857600
}
```

### Signature:
- Base64‑encoded OpenSSL signature
- Signed with CA private key
- Verified with CA public key

### Node server verification:
- Check signature
- Check expiry
- Extract `player_id`


# Cartographica Node Server

The Cartographica Node Server is a lightweight, stateful simulation server responsible for:

- Managing authenticated player sessions
- Serving world data (chunks, terrain, entities)
- Simulating local gameplay state
- Communicating with the Node Discovery Service (NDS)
- Persisting player and world data to SQLite

Each node represents a region of the world grid (e.g., `0,0`) and is responsible for all players and entities within that region.

---

## Features

### ✔ WebSocket Server
- RFC 6455–compliant handshake and frame parser
- Ping/pong, close frames, fragmentation support
- Event‑driven callbacks (`onOpen`, `onMessage`, `onClose`, `onTick`)

### ✔ Authentication
- Verifies signed identity tokens from the Identity Service
- Rejects expired or invalid tokens
- Loads or creates player records

### ✔ Player Management
- PlayerManager handles:
  - Loading players from SQLite
  - Creating new players
  - Caching active players
  - Saving on disconnect
- JSON‑based player data for flexibility

### ✔ Database Layer
- SQLite database with WAL mode
- Schema auto‑initialization from `schema.sql`
- Shared DB connection via `DB` singleton

### ✔ Node Discovery Integration
- Registers with NDS on startup
- Announces node coordinates and availability

---

## Directory Structure

```
node/
  server.php
  config.php
  schema.sql
  lib/
    DB.php
    PlayerManager.php
    WebSocketServer.php
  README.md
  protocol.md
```

---

## Running the Node Server

```
php server.php
```

You should see:

```
[INFO] Node server database initialized.
[INFO] Cartographica Node Server starting…
[INFO] Listening on ws://localhost:8080
[INFO] Node registered with NDS.
```

---

## Configuration

`config.php` defines:

```php
define('DB_PATH', __DIR__ . '/data/node.db');
define('NODE_ID', '0,0');
define('NDS_URL', 'http://localhost:9000');
```

---

## Database Schema

The schema is defined in `schema.sql` and automatically applied on startup.

---

## Protocol

See [`protocol.md`](protocol.md) for a full description of the WebSocket protocol used by Cartographica nodes.

---

## Roadmap

- WorldManager (chunks, terrain, entities)
- Player movement + interpolation
- Entity simulation
- Inventory system
- Combat + interactions
- Node‑to‑node handoff
```



---

## 🧭 Future Plans / Roadmap

- Node server prototype (PHP, Swoole, or Go)
- WebSocket protocol spec
- Tilemap generation module
- Node graph topology service
- QR‑based login flow
- Federation support (multiple identity realms)
- Client SDKs (JS, C#, Rust)

---

## 🤝 Contribution Guidelines

- Keep the protocol simple and language‑agnostic
- Avoid engine‑specific assumptions
- Document message formats clearly
- Keep identity service stable and backward‑compatible
- Prefer deterministic systems over random ones

---

## 📄 License

*(Add your chosen license here — MIT, Apache 2.0, GPL, etc.)*

---

If you want, I can also generate:

- A **diagram** of the architecture  
- A **protocol.md** spec  
- A **node server skeleton**  
- A **client authentication example**  

Just tell me what you want to build next.
