---

# 🌊 **The Archipelago Protocol**  
*A friendly, secure protocol for a world of islands.*

---

# 1. Introduction

The **Archipelago Protocol** defines how Cartographica’s distributed services communicate, authenticate, and exchange trust. It is designed for a world composed of many independent “islands” (servers), all connected through a lightweight, secure, and human‑friendly network.

This document describes:

- the architecture  
- the trust model  
- the certificate system  
- the identity login flow  
- the atlas island‑registration flow  
- the island handshake  
- security considerations  
- version notes  
- glossary  

The tone is intentionally friendly — this is a game, not a spacecraft navigation bus protocol.

---

# 2. High‑Level Architecture

```
                 verifies session tokens                 +-------------------+
    +---------------------------------------------------> |  Identity Service |
    |                                                     +--------------+----+
    |                                                                    ^
    |                                                                    |
    |                                             verifies session tokens| 
    |                                                                    |
+---+----+        discovers islands                                      |
| Client | ------------------------------------------------------+       |
+---+----+                                                       |       |
    |                                                            |       |
    | selects island                                             |       |
    v                                                            v       |
+-----------+             registers / updates             +------------------+
|  Island 1 | ------------------------------------------> |       Atlas      |
+-----------+                                             +------------------+
    ^                                                                ^
    |                                                                |
    | gameplay, sync, world state                                    |
    |                                                                |
+-----------+                                                        |
|  Island 2 | -------------------------------------------------------+
+-----------+
```

### Components

| Component | Purpose |
|----------|---------|
| **Identity Service** | Authenticates humans via email login links. Issues session tokens. |
| **Atlas Service** | Acts as a certificate authority (CA). Issues island certificates. |
| **Island Server** | Hosts a game world “island”. Uses certificates to prove identity. |
| **Client** | The human player’s game client. |

---

# 3. Folder Structure

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
    atlas/
    identity/
    island/
```

All secrets, logs, databases, and keys live in `cartographica_data/`.

---

# 4. Trust Model

The Archipelago Protocol uses a simple PKI‑style trust chain:

```
Human → Identity Service → Session Token
Island → Atlas → Island Certificate
```

### Human Authentication
- Humans authenticate via **email login links**.
- The identity service issues a **session token**.
- Clients present session tokens to islands.

### Island Authentication
- Islands register with the **atlas service**.
- The atlas service issues an **island certificate**.
- Islands present certificates to clients and other islands.

### Trust Anchors
- Identity public key  
- Atlas public key  

These are distributed with the game client.

---

# 5. Certificate System

All authentication and identity objects in Cartographica use the same cryptographic structure: a **signed certificate**.

## 5.1 Certificate Format

```json
{
  "payload": {
    "issued_at": <unix timestamp>,
    "expires_at": <unix timestamp>,
    "type": "<certificate_type>",
    ... additional fields ...
  },
  "signature": "<base64 signature>"
}
```

### Required fields

| Field        | Description |
|--------------|-------------|
| `issued_at`  | When the certificate was created |
| `expires_at` | When the certificate becomes invalid |
| `type`       | One of the allowed certificate types |

### Allowed certificate types

```
email_token
session_token
island_certificate
atlas_certificate
```

## 5.2 Signing Rules

- Payload encoded with `json_encode(..., JSON_UNESCAPED_SLASHES)`
- Signed using `openssl_sign(..., OPENSSL_ALGO_SHA256)`
- Signature is base64‑encoded

## 5.3 Verification Rules

A certificate is valid only if:

1. JSON structure is correct  
2. `payload` and `signature` exist  
3. `issued_at`, `expires_at`, and `type` exist  
4. `issued_at` ≤ now + 300 seconds  
5. `expires_at` > `issued_at`  
6. now < `expires_at`  
7. `type` is allowed  
8. Signature verifies using the issuer’s public key  

Verification returns:

```json
{ "valid": true, "payload": { ... } }
```

or

```json
{ "valid": false, "error": "<reason>" }
```

---

# 6. Identity Service

The Identity Service provides passwordless login using email links and signed certificates.

## 6.1 Authentication Flow

```
Client → Identity: request_login(email)
Identity → Email Provider: send login link
User clicks link
Client → Identity: redeem(email_token)
Identity → Client: session_token
Client → Island: session_token
Island → Identity: verify(session_token)
Island → Client: welcome to the island
```

---

## 6.2 `POST /identity/request-login`

### Request

```json
{ "email": "player@example.com" }
```

### Response

```json
{ "status": "sent" }
```

### Steps

1. Normalize and validate email  
2. Validate MX record  
3. Generate:
   - `player_id` (16‑byte hex)
   - `email_token_id` (16‑byte hex)
4. Issue an `email_token` certificate:

```json
{
  "email": "<email>",
  "player_id": "<player_id>",
  "email_token_id": "<email_token_id>"
}
```

5. Email the login link  
6. Log the attempt in `login_attempts`:

| Field | Value |
|-------|-------|
| email | user email |
| requested_at | timestamp |
| ip_address | client IP |
| player_id | generated player ID |
| email_token_id | generated token ID |

---

## 6.3 `POST /identity/redeem`

### Request

```json
{ "email_token": "<json>" }
```

### Response

Returns a **session token certificate**.

### Steps

1. Verify certificate  
2. Ensure `type === "email_token"`  
3. Ensure `email`, `player_id`, `email_token_id` exist  
4. Generate `session_id` (32‑byte hex)  
5. Issue a `session_token` certificate:

```json
{
  "email": "<email>",
  "player_id": "<player_id>",
  "session_id": "<session_id>"
}
```

6. Store in `session_tokens`  
7. Return the certificate

---

## 6.4 `POST /identity/verify`

### Request

```json
{ "session_token": "<json>" }
```

### Response

```json
{
  "valid": true,
  "payload": { ... },
  "signature": "..."
}
```

### Steps

1. Verify certificate  
2. Ensure `type === "session_token"`  
3. Ensure `email` exists  
4. Return certificate  

Used by islands to authenticate players.

---

# 7. Atlas Service

The Atlas Service acts as a certificate authority (CA) for islands.

## 7.1 Island Registration Flow

```
Island → Atlas: register_island(public_key, name, owner)
Atlas: verify request
Atlas: issue island_certificate
Atlas → Island: certificate
```

## 7.2 `POST /atlas/register-island`

### Request

```json
{
  "public_key": "<island public key>",
  "name": "My Cool Island",
  "owner": "player@example.com"
}
```

### Response

```json
{
  "certificate": "<signed island certificate>"
}
```

---

# 8. Island Handshake

When a client connects to an island:

1. Client → Island: send session token  
2. Island → Identity: verify session token  
3. Island → Client: send island certificate  
4. Client verifies island certificate using atlas public key  
5. Gameplay begins  

---

# 9. Security Considerations

- All certificates are signed using private keys stored on their respective services  
- Public keys are distributed with the client  
- All certificates are offline‑verifiable  
- No passwords are stored anywhere  
- Replay protection enforced via timestamps and token IDs  
- Islands must verify session tokens before allowing gameplay  
- Clients must verify island certificates before trusting an island  

---

# 10. Version Notes

| Version | Notes |
|---------|-------|
| **Jan‑2026** | First unified protocol specification. |

---

# 11. Glossary

| Term | Meaning |
|------|---------|
| **Archipelago** | The distributed network of islands. |
| **Island** | A game server hosting a world instance. |
| **Atlas Service** | Issues island certificates. |
| **Identity Service** | Authenticates humans and issues session tokens. |
| **Session Token** | A signed certificate proving human identity. |
| **Island Certificate** | A signed certificate proving island identity. |
| **Trust Anchor** | A public key distributed with the client. |
| **Handshake** | The identity verification process between client and island. |

---
