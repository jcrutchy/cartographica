
---

# 🌍 **Cartographica — The Archipelago**

Welcome to Cartographica!

---

## 🌍 **What the game feels like from a player’s perspective**

Cartographica is a slow‑burn exploration and settlement game where you’re dropped into a vast, mostly unknown world and asked to make sense of it one discovery at a time. You begin with almost nothing: a small island, a handful of basic tools, and a map that’s more blank space than information. Your first steps are simple — explore your surroundings, mark what you find, and start building the foundations of a place you can call home. But as you uncover more of the world, the scale of what’s possible begins to open up.

Every island you encounter has its own personality. Some are lush and resource‑rich, others are barren and harsh, and a few hide secrets that only reveal themselves after careful observation. You chart coastlines, map terrain, record landmarks, and gradually stitch together a living atlas of the world. The more you explore, the more the world feels like something you’re genuinely uncovering rather than something handed to you.

As your settlement grows, so does your connection to the wider world. You’ll discover other islands, establish routes between them, and eventually link your discoveries into a shared Atlas that other players can interact with. The game rewards curiosity and patience — there’s no rush, no pressure, just the quiet satisfaction of turning the unknown into something familiar and meaningful.

At its heart, Cartographica is about discovery, connection, and the joy of watching a world take shape because you were the one who mapped it. It’s a game for players who enjoy exploration for its own sake, who like the feeling of uncovering patterns, and who appreciate a world that reveals itself gradually rather than all at once.

---

This project implements **The Archipelago Protocol**, a lightweight, game‑friendly system for connecting players, islands (servers), and trust authorities into a shared world.

This README will help you:

- understand the project structure
- set up your development environment
- configure the external data folder
- run each service
- explore the tools and shared framework
- run tests
- extend the system

This is a developer‑friendly document — no corporate jargon, no over‑formal RFC language. Just clear explanations and practical steps.

**This game has been developed with the assistance of LLM tools including Copilot and Gemini.**

---

# 🗺️ 1. Project Overview

Cartographica is built around the idea of a world composed of many **islands** — independent servers that players can visit. These islands form an **archipelago**, connected through a simple, secure protocol.

The system consists of three main services:

- **Identity Service**
  Authenticates humans via email login links. Issues device tokens.

- **Atlas Service**
  Acts as a certificate authority (CA). Issues island certificates.

- **Island Server**
  Hosts a game world “island”. Uses certificates to prove identity.

All services share a common internal framework located in `share/`.

---

# 🧱 2. Repository Structure

```
cartographica/
├── README.md
├── protocol.md
├── share/
│   ├── Autoload.php
│   ├── Db.php
│   ├── Env.php
│   ├── Logger.php
│   ├── Request.php
│   ├── Response.php
│   ├── Router.php
│   ├── Crypto.php
│   ├── Keys.php
│   ├── Smtp.php
│   ├── Template.php
│   └── SharedConfig.php
├── services/
│   ├── atlas/
│   ├── identity/
│   └── island/
├── tools/
└── tests/
```

Runtime data lives **outside** the repo:

```
cartographica_data/
└── services/
    ├── atlas/
    ├── identity/
    └── island/
```

This keeps secrets, logs, and databases out of version control.

---

# 📦 3. External Data Folder

Create this next to the repo:

```
cartographica/
cartographica_data/
```

Inside:

```
cartographica_data/
  shared/
    config.json
    smtp_credentials.txt
  services/
    atlas/
      atlas.sqlite
      log/
    identity/
      identity_private.pem
      identity_public.pem
      identity.sqlite
      log/
    island/
      island_private.pem
      island_public.pem
      island.sqlite
      island_config.json
      log/
```

The game never stores secrets inside the repo — only here.

---

# ⚙️ 4. Shared Configuration (`config.json`)

Located at:

```
cartographica_data/shared/config.json
```

Example:

```json
{
  "environment": "development",
  "web_root": "http://localhost/cartographica",
  "smtp_host": "smtp.gmail.com",
  "smtp_port": 587,
  "smtp_from_email": "no-reply@cartographica.com",
  "admin_email": "admin@cartographica.com",
  "identity_url": "http://localhost/cartographica/services/identity",
  "atlas_url": "http://localhost/cartographica/services/atlas"
}
```

This file controls environment‑specific settings.

---

# 🚀 5. Running the Services

The identity and Atlas services are web APIs. You can also run them behind Apache/Nginx if you prefer.

### Identity Service

```
php -S localhost:8001 -t services/identity
```

### Atlas Service

```
php -S localhost:8002 -t services/atlas
```

### Island Server

The island server is a long-running terminal daemon that exposes websocket and IPC socket servers for connections from clients and between island servers.

```
php services/island/index.php
```

---

# 📨 6. Email Sending

The identity service uses:

- `share/Smtp.php` for low‑level SMTP
- `share/Template.php` for HTML templates

Email templates live in:

```
services/identity/templates/
```

---

# 🔐 7. Keys and Certificates

Each service has its own keypair stored in:

```
cartographica_data/services/<service>/identity_private.pem
cartographica_data/services/<service>/identity_public.pem
```

The `share/Keys.php` helper ensures keys exist and generates them if missing.

---

# 🧪 8. Tests

Tests live in:

```
tests/
```

You can run them with:

```
php tests/test_all.php
```

An isolated data folder structure for testing is created under "_testdata" inside the main data folder.

---

# 🧩 9. Tools

The `tools/` folder contains:

- game web client for playing Cartographica
- admin utilities
- debugging helpers

Clients and administration tools can be added here.

---

# 🧠 10. The Archipelago Protocol

The full protocol specification lives in:

```
protocol.md
```

It includes:

- architecture diagrams
- sequence diagrams
- JSON examples
- glossary
- version notes

If you’re building a client or island server, start there.

---

# 🛠️ 11. Extending the System

You can add:

- new services
- new actions
- new templates
- new certificate types
- new island features

The `share/` framework is intentionally lightweight and easy to extend.

---

# 🧭 12. Contributing

If you’re working on this project:

- keep secrets out of the repo
- ensure code is readable
- follow consistent code etiquette and conventions
- follow the folder structure
- use the shared framework
- update `protocol.md` when adding new flows
- keep controllers small and focused

---
