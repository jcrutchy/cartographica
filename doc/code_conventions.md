# Cartographica

Cartographica is a web-based civilization/resource simulation game.
This repository follows strict coding conventions to ensure consistency, readability, and maintainability.

---

## Coding conventions

### General
- Simple procedural style only (no classes, no OOP).
- Use snake_case for all variables and functions.
- No ternary operators — always use explicit if/else.
- Allman indentation/bracing style: braces on their own lines.
- Two-space indents throughout the code.
- Root-prefixed namespaces: always call functions as \cartographica\network\send_message().
- Minimal comments: only explain rationale, not obvious behavior.
- Compact spacing: omit unnecessary spaces around operators, commas, and parameters.
- Allman bracing style (no K&R).
- Always specify a relational or logical operator in conditional statements (no implied values, including true or 1).
- Don't omit braces for single lines.
- Always use double-quotes for strings unless single-quote is required for specific behaviour.
- Don't omit semi-colons from statements, even if they are implied.
- Space between control structure keywords (if, for, while) and first bracket.
- Use 'and' and 'or' instead of symbolic variants.
- No variables embedded in strings. Use concatenation instead. eg: "the ".$animal." is ".$color, not "the $animal is $color"

---

### PHP
- File layout:
  - Always namespaced (e.g., namespace cartographica\network;).
- Functions:
  - function calls always namespaced (including root) except for built-in PHP library functions. eg: \cartographica\network\start_server();
  - Names in snake_case.
- Variables:
  - Use descriptive names in snake_case (eg: $player_id, $chunk_list).
- Formatting:
  ```php
  if ($condition)
  {
    do_something()
  }
  else
  {
    do_something_else()
  }
  ```
- Spacing:
  - fwrite($conn,$msg); not fwrite( $conn, $msg );
  - for ($i=0;$i<$len;$i++) not for ( $i = 0; $i < $len; $i++ )

---

### JavaScript
- Functions:
  - Names in snake_case.
- Variables:
  - Use descriptive names in snake_case.
- Formatting:
  ```js
  function connect_server()
  {
    socket=new WebSocket("ws://localhost:9000")
  }
  ```
- Spacing:
  - ctx.fillRect(x,y,w,h) not ctx.fillRect( x, y, w, h )

---

### JSON
- Always include type field.
- Use snake_case for keys.
- Pretty-print with 2-space indentation.
- Example:
  ```json
  {
    "type": "state_update",
    "tick": 42,
    "chunks": [
      {
        "chunk_id": "c_12_34",
        "version": 5,
        "entities": [
          {
            "id": "agent_1",
            "kind": "citizen",
            "pos": [12,34],
            "task": "gather"
          }
        ],
        "stocks": {
          "wood": 120,
          "planks": 30
        }
      }
    ]
  }
  ```

---

### CSS/HTML
- HTML IDs: snake_case (eg: id="game_canvas")
- CSS classes: kebab-case (eg: .hud-panel, .resource-bar)
- Compact attribute spacing:
  ```html
  <div id="game_canvas">
  ```
  not
  ```html
  <div id = "game_canvas">
  ```

---

### Documentation
- Maintain a protocol.md file with message schemas.
- Use minimal comments in code, only to explain rationale.
- Keep markdown docs consistent with these conventions.
