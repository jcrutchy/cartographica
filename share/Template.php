<?php

/*

Template.php
============

Purpose:
Simple placeholder-based template loader for HTML/CSS/JS/SQL/etc.
Allows separating content from PHP logic.

*/

namespace cartographica\share;

use Exception;

class Template
{
    /**
     * Load a template file and replace {{placeholders}} with values.
     */
    public static function render(string $path, array $vars = []): string
    {
        if (!file_exists($path)) {
            throw new Exception("Template not found: $path");
        }

        $content = file_get_contents($path);

        // Replace {{key}} with value
        foreach ($vars as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
        }

        return $content;
    }
}
