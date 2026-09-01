<?php
/**
 * HtmlSanitizer: Safe HTML Output Escaping
 * 
 * Single responsibility: escape user-provided content for safe HTML output
 * Usage: HtmlSanitizer::escape($userInput)
 */

class HtmlSanitizer
{
    /**
     * Safely escape HTML special characters for output
     * 
     * Prevents XSS attacks by escaping:
     * - < > & " '
     * 
     * @param string|null $string Input string to escape
     * @return string Escaped HTML string
     */
    public static function escape(?string $string): string
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Alias for escape() - shorter naming for templates
     */
    public static function e(?string $string): string
    {
        return self::escape($string);
    }
}
