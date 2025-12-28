<?php
namespace Cartographica\Core;

/*
Redis Global Event Bus
The Shared Intelligence Layer
This class is used by both the AI and Authority to communicate across the distributed network.

This system, developed by Doist, uses a redis_bus.Bus object to register methods and enables services
to expose functions that can be called remotely. It supports features like method prefixing for
namespace management and caching of function results using format strings for cache keys.

Another implementation is a Go library called redisbus, which provides a pub/sub bus built on top of
Redis, allowing for multiplexing Redis subscriptions to local subscriptions.
This library facilitates publishing and subscribing to channels, making it suitable for event-driven
architectures.

Redis Cluster itself uses a dedicated communication channel known as the Redis Cluster Bus, a binary
protocol used for node-to-node communication. This bus enables nodes to auto-discover each other,
detect failures, manage cluster state, and coordinate failovers using a gossip protocol.
The cluster bus operates on a separate port (typically 10000 + data port) and is essential for
maintaining cluster integrity and enabling distributed operations.

Additionally, Redis can be used as an event bus for applications, leveraging its built-in pub/sub
functionality or newer features like Streams for consumer groups.
Libraries such as node-redis-eventbus on npm provide simple event bus functionality powered by Redis
for Node.js applications.
While Redis is not a full-featured message broker like Apache Kafka, it can serve as a lightweight
event bus for many use cases, especially when combined with proper architectural considerations.

*/

class RedisBus {
    private \Redis $redis;

    public function __construct(string $host = '127.0.0.1') {
        $this->redis = new \Redis();
        $this->redis->connect($host);
    }

    public function publish(string $channel, array $payload): void {
        $this->redis->publish($channel, json_encode($payload));
    }

    public function subscribe(array $channels, callable $callback): void {
        // Warning: This is a blocking call
        $this->redis->subscribe($channels, $callback);
    }
}
