# Cartographica Node Protocol

This document defines the WebSocket protocol used between the game client and a Cartographica Node Server.

The protocol is JSON‑based, message‑oriented, and stateful.

---

# 1. Connection Lifecycle

## 1.1 WebSocket Handshake

Clients connect to:

```
ws://<node-host>:8080
```

Upon successful handshake, the server sends:

```json
{
  "type": "HELLO",
  "node": "0,0"
}
```

The client must then authenticate.

---

# 2. Authentication

## 2.1 AUTH Message

Sent by the client immediately after receiving `HELLO`.

```json
{
  "type": "AUTH",
  "payload": {
    "player_id": "<hex string>",
    "issued_at": 1767074014,
    "expires_at": 1782626014
  },
  "signature": "<base64 signature>"
}
```

### Rules

- `player_id` is a 64‑character hex string.
- `payload` is signed using the Identity Service private key.
- The node verifies the signature using the public key.
- Tokens must not be expired.

## 2.2 AUTH_OK

Returned on success:

```json
{
  "type": "AUTH_OK",
  "player": {
    "player_id": "...",
    "created_at": 1767083727,
    "last_seen": 1767083727,
    "position": [0, 0],
    "inventory": [],
    "stats": {
      "health": 100,
      "mana": 50
    }
  }
}
```

## 2.3 AUTH_ERROR

Returned on failure:

```json
{
  "type": "ERROR",
  "msg": "Invalid signature"
}
```

---

# 3. World Loading

## 3.1 REQUEST_WORLD

Sent by the client after authentication:

```json
{
  "type": "REQUEST_WORLD"
}
```

## 3.2 WORLD_DATA

Returned by the server:

```json
{
  "type": "WORLD_DATA",
  "world": {
    "seed": 12345,
    "chunks": [
      { "x": 0, "y": 0, "terrain": "grass" },
      { "x": 1, "y": 0, "terrain": "forest" }
    ]
  }
}
```

This is a placeholder until the full WorldManager is implemented.

---

# 4. Player Movement (Future)

## 4.1 MOVE

```json
{
  "type": "MOVE",
  "dx": 1,
  "dy": 0
}
```

## 4.2 PLAYER_MOVED

Broadcast to nearby players:

```json
{
  "type": "PLAYER_MOVED",
  "player_id": "...",
  "position": [10, 20]
}
```

---

# 5. Heartbeats

Nodes may send periodic heartbeats to keep connections alive.

```json
{
  "type": "PING"
}
```

Clients respond with:

```json
{
  "type": "PONG"
}
```

---

# 6. Error Handling

All errors follow this format:

```json
{
  "type": "ERROR",
  "msg": "Description of the error"
}
```

---

# 7. Future Extensions

- Chunk streaming
- Entity updates
- Combat + interactions
- Inventory management
- Node handoff (player crossing region boundaries)
- World persistence
- Server‑side simulation ticks

---

# 8. Versioning

The protocol will adopt semantic versioning:

```
major.minor.patch
```

Example:

```
1.0.0
```

Nodes and clients must negotiate compatible versions in the future.

```
