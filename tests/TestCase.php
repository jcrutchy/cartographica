<?php

namespace cartographica\tests;

abstract class TestCase
{
    private array $results = [];

    protected function post($url, array $data) {
        $body = http_build_query($data);
    
        $options = [
            "http" => [
                "method"  => "POST",
                "header"  =>
                    "Content-Type: application/x-www-form-urlencoded\r\n" .
                    "Content-Length: " . strlen($body) . "\r\n" .
                    "User-Agent: PHPTestClient\r\n" .
                    "Connection: close\r\n",
                "content" => $body,
                "ignore_errors" => true
            ]
        ];
    
        return file_get_contents($url, false, stream_context_create($options));
    }

    protected function assertTrue($condition, string $message = '', array $data = []): void
    {
        $this->results[] = [
            "ok" => (bool)$condition,
            "message" => $message ?: "assertTrue failed"
        ];
        if (!$condition)
        {
            echo "\n--- DEBUG ---\n";
            var_dump($data);
            echo "\n-------------\n";
        }
    }

    protected function assertEquals($expected, $actual, string $message = '', array $data = []): void
    {
        $ok = ($expected === $actual);

        $this->results[] = [
            "ok" => $ok,
            "message" => $message ?: "Expected " . var_export($expected, true) .
                                   " but got " . var_export($actual, true)
        ];
        if (!$ok)
        {
            echo "\n--- DEBUG ---\n";
            var_dump($data);
            echo "\n-------------\n";
        }
    }

    public function run(): array
    {
        $this->results = [];
        $this->test();
        return $this->results;
    }

    abstract protected function test(): void;
}
