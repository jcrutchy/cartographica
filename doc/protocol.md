
---

# 🌊 **The Archipelago Protocol**  
### *A developer‑friendly specification for Cartographica’s distributed world services*

---

# 1. Introduction

The **Archipelago Protocol** defines how Cartographica’s distributed services communicate, authenticate, and exchange trust. It is designed for a game world composed of many independent “islands” (servers), all connected through a lightweight, secure, and human‑friendly network.

This document describes:

- the architecture  
- the trust model  
- the JSON message format  
- the identity login flow  
- the island registration and certificate flow  
- the island handshake  
- security considerations  
- version notes  
- glossary  

The tone is intentionally friendly — this is a game, not a spacecraft navigation bus protocol.

---

# 2. High‑Level Architecture

```
                           +---------------------------+
                           |     Island Directory      |
                           |  (Trust Authority / CA)   |
                           +-------------+-------------+
                                         ^
                                         |
                                         |
+------------------+                     |                     +------------------+
|   Identity       |                     |                     |     Island       |
|   Service        |                     |                     |     Server       |
| (Human Auth)     |                     |                     | (Game Instance)  |
+---------+--------+                     |                     +---------+--------+
          ^                              |                               ^
          |                              |                               |
          |                              |                               |
          |                              |                               |
          |                              |                               |
+---------+--------+                     |                     +---------+--------+
|     Client       |---------------------+---------------------|     Client       |
| (Human Player)   |   Archipelago Protocol (JSON over HTTP)   | (Game Client)    |
+------------------+                                           +------------------+
```

### Components

| Component | Purpose |
|----------|---------|
| **Identity Service** | Authenticates humans via email login links. Issues device tokens. |
| **Island Directory** | Acts as a certificate authority (CA). Issues island certificates. |
| **Island Server** | Hosts a game world “island”. Uses certificates to prove identity. |
| **Client** | The human player’s game client. |

---

# 3. Directory Structure

The protocol assumes the following layout:

```
cartographica/
  share/
  services/
  tools/
  tests/

cartographica_data/
  shared/
    config.json
  services/
    identity/
    island/
    island-directory/
```

All secrets, logs, databases, and keys live in `cartographica_data/`.

---

# 4. Trust Model

The Archipelago Protocol uses a simple PKI‑style trust chain:

```
Human → Identity Service → Device Token
Island → Island Directory → Island Certificate
```

### Human Authentication
- Humans authenticate via **email login links**.
- The identity service signs a **device token** with its private key.
- Clients present device tokens to islands.

### Island Authentication
- Islands register with the **Island Directory**.
- The directory signs an **island certificate**.
- Islands present certificates to clients and other islands.

### Trust Anchors
- Identity public key  
- Island Directory public key  

These are distributed with the game client.

---

# 5. JSON Message Format

All messages follow this structure:

```json
{
  "action": "string",
  "data": { ... }
}
```

Responses:

```json
{
  "ok": true,
  "data": { ... }
}
```

Errors:

```json
{
  "ok": false,
  "error": "Message describing the error"
}
```

---

# 6. Identity Service

## 6.1 Login Flow Overview

```
+--------+        +------------------+        +------------------+
| Client |        | Identity Service |        | Email Provider   |
+---+----+        +---------+--------+        +---------+--------+
    |                       |                           |
    | POST request_login    |                           |
    |---------------------->|                           |
    |                       | Generate token            |
    |                       | Send email via SMTP       |
    |                       |-------------------------->|
    |                       |                           |
    |                       | <--- Email delivered ---- |
    |                       |                           |
    | User clicks link      |                           |
    |---------------------->| POST redeem               |
    |                       | Validate + issue device   |
    |                       | token                     |
    |                       |                           |
    | <------ device token -+                           |
```

---

## 6.2 `request_login`

### Request

```json
{
  "action": "request_login",
  "data": {
    "email": "player@example.com"
  }
}
```

### Response

```json
{
  "ok": true,
  "data": {
    "status": "sent"
  }
}
```

---

## 6.3 `redeem`

### Request

```json
{
  "action": "redeem",
  "data": {
    "token": "<signed login token>"
  }
}
```

### Response

```json
{
  "ok": true,
  "data": {
    "device_token": "<signed device token>",
    "payload": {
      "email": "player@example.com",
      "issued_at": 1700000000,
      "expires_at": 1702592000
    }
  }
}
```

---

## 6.4 `verify`

Used by islands to verify device tokens.

### Request

```json
{
  "action": "verify",
  "data": {
    "device_token": "<signed device token>"
  }
}
```

### Response

```json
{
  "ok": true,
  "data": {
    "valid": true,
    "payload": {
      "email": "player@example.com",
      "issued_at": 1700000000,
      "expires_at": 1702592000
    }
  }
}
```

---

# 7. Island Directory

The Island Directory acts as a certificate authority (CA) for islands.

## 7.1 Island Registration Flow

```
+-------------+          +-----------------------+
| Island      |          | Island Directory      |
+------+------+          +-----------+-----------+
       |                             |
       | POST register_island        |
       |---------------------------->|
       |                             |
       | Directory verifies request  |
       | Generates certificate       |
       |                             |
       | <---- island certificate ---|
```

---

## 7.2 `register_island`

### Request

```json
{
  "action": "register_island",
  "data": {
    "public_key": "<island public key>",
    "name": "My Cool Island",
    "owner": "player@example.com"
  }
}
```

### Response

```json
{
  "ok": true,
  "data": {
    "certificate": "<signed certificate>"
  }
}
```

---

# 8. Island Handshake

When a client connects to an island:

```
Client → Island: send device token
Island → Identity Service: verify token
Island → Client: send island certificate
Client: verify certificate using directory public key
```

If all checks pass, the session begins.

---

# 9. Security Considerations

- All tokens are signed using Ed25519.  
- All certificates are signed by the Island Directory.  
- Tokens include expiry timestamps.  
- Islands must verify device tokens before allowing gameplay.  
- Clients must verify island certificates before trusting an island.  
- No shared secrets exist between islands.  
- No passwords are stored anywhere.  

---

# 10. Version Notes

| Version | Notes |
|---------|-------|
| **0.1** | Initial login flow. |
| **0.2** | Added island registration. |
| **0.3** | Added certificate format. |
| **0.4** | Introduced `share/` framework. |
| **0.5** | Moved secrets to `cartographica_data/`. |
| **0.6** | Added Template helper. |
| **0.7** | Renamed protocol to **The Archipelago Protocol**. |

---

# 11. Glossary

| Term | Meaning |
|------|---------|
| **Archipelago** | The distributed network of islands. |
| **Island** | A game server hosting a world instance. |
| **Island Directory** | The trust authority that issues island certificates. |
| **Identity Service** | Authenticates humans and issues device tokens. |
| **Device Token** | A signed token proving human identity. |
| **Island Certificate** | A signed certificate proving island identity. |
| **Trust Anchor** | A public key distributed with the client. |
| **Handshake** | The process of verifying identity between client and island. |

---

# 12. Future Extensions

- Island‑to‑island federation  
- Player‑to‑player trust tokens  
- Island reputation system  
- Cross‑island travel  
- Distributed world state  

---
