
---

# 🌊 **The Archipelago Protocol**  

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
                 verifies device tokens                   +-------------------+
    +---------------------------------------------------> |  Identity Service |
    |                                                     +--------------+----+
    |                                                                    ^
    |                                                                    |
    |                                             verifies device tokens | 
    |                                                                    |
+---+----+        discovers islands                                      |
| Client | ------------------------------------------------------+       |
+---+----+                                                       |       |
    |                                                            |       |
    | selects island                                             |       |
    v                                                            v       |
+-----------+             registers / updates             +------------------+
|  Island 1 | ------------------------------------------> |        Atlas     |
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
| **Identity Service** | Authenticates humans via email login links. Issues device tokens. |
| **Atlas Service** | Acts as a certificate authority (CA). Issues island certificates. |
| **Island Server** | Hosts a game world “island”. Uses certificates to prove identity. |
| **Client** | The human player’s game client. |

---

# 3. Folder Structure

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
    atlas/
    identity/
    island/
```

All secrets, logs, databases, and keys live in `cartographica_data/`.

---

# 4. Trust Model

The Archipelago Protocol uses a simple PKI‑style trust chain:

```
Human → Identity Service → Device Token
Island → Atlas → Island Certificate
```

### Human Authentication
- Humans authenticate via **email login links**.
- The identity service signs a **device token** with its private key.
- Clients present device tokens to islands.

### Island Authentication
- Islands register with the **atlas service**.
- The atlas service signs an **island certificate**.
- Islands present certificates to clients and other islands.

### Trust Anchors
- Identity public key  
- Atlas public key  

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

## 6.1 Authentication Flow Overview

```
+--------+        +------------------+        +------------------+             +--------+
| Client |        | Identity Service |        | Email Provider   |             | Island |
+---+----+        +---------+--------+        +---------+--------+             +----+---+
    |                       |                           |                           |
    |--POST request_login-->|                           |                           |
    |      (generate token) |--Send email with token--->|                           |
    | <----------User clicks emailed link-------------- |                           |
    |-----POST redeem------>| (verify link token)       |                           |
    | <-----device token----+ (signed)                  |                           |
    |                       |                           |                           |
    |  (returning player)   |                           |                           |
    | ----POST verify-----> | (device token received)   |                           |
    | <---valid signature---+                           |                           |
    |                       |                           |                           |
    | -------------------POST device token to selected island---------------------> |
    |                       | <-------------POST verify (device token)------------- |
    |                       | -------------------valid signature------------------> |
    | <--------------------------player & world state------------------------------ |
```

---

## 6.2 `request_login` (POST, sent from the web client js)

### Request

```json
{
  "action": "request_login",
  "email": "player@example.com"
}
```

### Response (sent to the web client js)

```json
{
  "ok": true,
  "status": "sent"
}
```

---

## 6.3 `redeem`

### Request (POST, sent from the web client js)

```json
{
  "action": "redeem",
  "token": "<signed login token>"
}
```

### Response (sent to the web client js)

```json
{
  "ok": true,
  "device_token": "<signed device token>",
  "payload": {
    "email": "player@example.com",
    "issued_at": 1700000000,
    "expires_at": 1702592000
  }
}
```

---

## 6.4 `verify` (sent to the web client js)

Used by clients and islands to verify device tokens.

### Request (POST)

```json
{
  "action": "verify",
  "device_token": "<signed device token>"
}
```

### Response

```json
{
  "ok": true,
  "valid": true,
  "payload": {
    "email": "player@example.com",
    "issued_at": 1700000000,
    "expires_at": 1702592000
  }
}
```

---

# 7. Atlas

The atlas service acts as a certificate authority (CA) for islands.

## 7.1 Island Registration Flow

```
+-------------+          +-----------------------+
|   Island    |          |         Atlas         |
+------+------+          +-----------+-----------+
       |                             |
       | POST register_island        |
       |---------------------------->|
       |                             |
       | Atlas verifies request      |
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
  "public_key": "<island public key>",
  "name": "My Cool Island",
  "owner": "player@example.com"
}
```

### Response

```json
{
  "ok": true,
  "certificate": "<signed certificate>"
}
```

---

# 8. Island Handshake

When a client connects to an island:

```
Client → Island: send device token
Island → Identity Service: verify token
Island → Client: send island certificate
Client: verify certificate using atlas public key
```

If all checks pass, the session begins.

---

# 9. Security Considerations

- All tokens are signed using Ed25519.  
- All certificates are signed by the atlas service.  
- Tokens include expiry timestamps.  
- Islands must verify device tokens before allowing gameplay.  
- Clients must verify island certificates before trusting an island.  
- No shared secrets exist between islands.  
- No passwords are stored anywhere.  

---

## **Signed Payloads, Tokens, and Certificates**

### **Overview**
Cartographica uses a unified cryptographic format for all authentication and identity assertions. Every “token” or “certificate” in the system is a **signed payload**, consisting of:

- **payload** — a JSON object containing the data being asserted  
- **signature** — a digital signature of the payload, created using the issuing service’s private key  

Any service or island can verify the authenticity of a signed payload using the issuing service’s **public key**.

This mechanism is used for:

- **Email tokens** (prove email ownership)  
- **Session tokens** (prove authenticated player sessions)  
- **Island certificates** (prove island identity and ownership)  
- **Atlas certificates** (prove atlas identity)  

All of these objects share the same structure and verification rules.

---

## **Signed Payload Structure**

Every signed payload has the form:

```json
{
  "payload": { ... },
  "signature": "base64-encoded-signature"
}
```

### **Payload Requirements**
All payloads must include:

- `issued` — UNIX timestamp  
- `expiry` — UNIX timestamp  

Additional fields depend on the issuing service.

---

## **Email Token**

Issued by the **Identity Service** to prove ownership of an email address.

**Payload fields:**

- `email`  
- `issued`  
- `expiry`  

**Usage:**  
Sent to the player via email. Redeemed by the client to obtain a session token.

---

## **Session Token**

Issued by the **Identity Service** after successful email token redemption.  
Represents an authenticated session for a specific player.

**Payload fields:**

- `player_id` — stable identifier derived from the email  
- `session_token` — random 32‑byte session credential  
- `issued`  
- `expiry` (typically 30 days)  

**Usage:**  
The client presents the session token to islands.  
Islands verify the signature using the identity service public key.

---

## **Island Certificate**

Issued by the **Atlas Service** to prove the identity and ownership of an island.

**Payload fields:**

- `island_name`  
- `owner_email`  
- `public_key` (island’s public key)  
- `issued`  
- `expiry`  

**Usage:**  
Islands present this certificate to clients or other services.  
Anyone can verify it using the atlas public key.

---

## **Verification**

Any service or island verifies a signed payload by:

1. Recomputing the signature over the `payload` JSON  
2. Checking the signature using the issuing service’s public key  
3. Ensuring `issued` and `expiry` are valid  
4. Trusting the contents of the payload if verification succeeds  

No service needs to contact the issuer to verify a token or certificate.

---

## **Security Model**

- Private keys never leave their respective services  
- Public keys are distributed and cached  
- All tokens and certificates are self‑contained and offline‑verifiable  
- Clients cannot forge or modify tokens  
- Islands do not need to contact the identity service during gameplay  

---

# 📘 **Protocol Specification: Certificates and Tokens**

## 1. Overview

Cartographica uses a unified cryptographic format for all authentication and identity assertions. These objects are referred to as **certificates** or **tokens**, depending on context, but they share the same structure and verification rules.

A certificate is a **signed payload**, consisting of:

- a JSON **payload** containing the data being asserted  
- a **signature** generated using the issuing service’s private key  

Any service or island can verify a certificate using the issuing service’s public key.

Certificates are used for:

- **Email tokens** (prove email ownership)  
- **Session tokens** (prove authenticated player sessions)  
- **Island certificates** (prove island identity and ownership)  
- **Atlas certificates** (prove atlas identity)  

All certificates follow the same format and validation rules.

---

## 2. Certificate Format

A certificate is a JSON object with the following structure:

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

### Required payload fields

| Field        | Description |
|--------------|-------------|
| `issued_at`  | UNIX timestamp when the certificate was created |
| `expires_at` | UNIX timestamp after which the certificate is invalid |
| `type`       | One of the allowed certificate types |

### Allowed certificate types

```
email_token
session_token
island_certificate
atlas_certificate
```

Each certificate type may include additional fields depending on its purpose.

---

## 3. Signing Rules

Certificates are signed using the issuing service’s **private key**.

### Signing process

1. Construct the payload as a JSON object.
2. Encode the payload using:

```
json_encode(payload, JSON_UNESCAPED_SLASHES)
```

3. Sign the resulting JSON string using:

```
openssl_sign(..., OPENSSL_ALGO_SHA256)
```

4. Base64‑encode the signature.

### Output

The issuing service returns:

```json
{
  "valid": true,
  "payload": { ... },
  "signature": "<base64 signature>"
}
```

If signing fails, the service returns:

```json
{
  "valid": false,
  "error": "<error message>"
}
```

---

## 4. Verification Rules

To verify a certificate, a service or island must perform the following checks **in order**:

### 4.1 Structural validation

- Certificate must be valid JSON.
- Must contain both `payload` and `signature`.
- `payload` must be an object.
- `signature` must be a string.

### 4.2 Required fields

The payload must contain:

- `issued_at`
- `expires_at`
- `type`

### 4.3 Temporal validation

- `issued_at` must not be more than 300 seconds in the future.
- `expires_at` must be strictly greater than `issued_at`.
- Current time must be less than `expires_at`.

### 4.4 Type validation

`type` must be one of the allowed certificate types.

### 4.5 Signature validation

1. Re‑encode the payload using:

```
json_encode(payload, JSON_UNESCAPED_SLASHES)
```

2. Verify the signature using the issuer’s **public key** and:

```
openssl_verify(..., OPENSSL_ALGO_SHA256)
```

3. Verification outcomes:

| Result | Meaning |
|--------|---------|
| `1`    | Signature valid |
| `0`    | Signature invalid |
| `-1`   | OpenSSL error |

### 4.6 Verification output

On success:

```json
{
  "valid": true,
  "payload": { ... }
}
```

On failure:

```json
{
  "valid": false,
  "error": "<error message>"
}
```

---

## 5. Security Properties

This certificate system provides:

- **Integrity** — payloads cannot be modified without invalidating the signature.
- **Authenticity** — only the holder of the private key can issue certificates.
- **Offline verification** — islands and services verify certificates without contacting the issuer.
- **Replay protection** — enforced via `issued_at`, `expires_at`, and type restrictions.
- **Context isolation** — certificate types prevent cross‑use (e.g., using an island certificate as a session token).

---

# 10. Version Notes

| Version | Notes |
|---------|-------|
| **Dec-25/Jan-26** | Initial protocol development and testing. |

---

# 11. Glossary

| Term | Meaning |
|------|---------|
| **Archipelago** | The distributed network of islands. |
| **Island** | A game server hosting a world instance. |
| **Atlas Service** | The trust authority that issues island certificates. |
| **Identity Service** | Authenticates humans and issues device tokens. |
| **Device Token** | A signed token proving human identity. |
| **Island Certificate** | A signed certificate proving island identity. |
| **Trust Anchor** | A public key distributed with the client. |
| **Handshake** | The process of verifying identity between client and island. |

---
