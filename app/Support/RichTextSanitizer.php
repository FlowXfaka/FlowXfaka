<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class RichTextSanitizer
{
    private const STYLE_PLACEHOLDER = 'data-safe-style';

    private const ALLOWED_TAGS = [
        'a', 'b', 'blockquote', 'br', 'code', 'div', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'hr', 'i', 'img', 'li', 'ol', 'p', 'pre', 's', 'span', 'strong', 'table', 'tbody', 'td',
        'th', 'thead', 'tr', 'u', 'ul',
    ];

    private const DROP_WITH_CONTENT = [
        'button', 'embed', 'form', 'iframe', 'input', 'link', 'math', 'meta', 'object', 'option',
        'script', 'select', 'style', 'svg', 'textarea',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan'],
    ];

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $html = self::preserveStyleAttributes($html);

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><body><div data-sanitizer-root="1">' . $html . '</div></body></html>';
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $root = null;
        foreach ($document->getElementsByTagName('div') as $div) {
            if ($div instanceof DOMElement && $div->getAttribute('data-sanitizer-root') === '1') {
                $root = $div;
                break;
            }
        }

        if (! $root instanceof DOMElement) {
            return '';
        }

        self::sanitizeNode($root);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $document->saveHTML($child) ?: '';
        }

        return trim($output);
    }

    private static function preserveStyleAttributes(string $html): string
    {
        $pattern = "#\\sstyle\\s*=\\s*(['\"])(.*?)\\1#i";

        return preg_replace_callback($pattern, static function (array $matches): string {
            $decoded = html_entity_decode($matches[2] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === '') {
                return '';
            }

            return ' ' . self::STYLE_PLACEHOLDER . '="' . base64_encode($decoded) . '"';
        }, $html) ?? $html;
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }

            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                    $node->removeChild($child);
                    continue;
                }

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }

                self::sanitizeAttributes($child);
                self::sanitizeNode($child);
                continue;
            }

            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);
        $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];
        $encodedStyle = $element->hasAttribute(self::STYLE_PLACEHOLDER) ? $element->getAttribute(self::STYLE_PLACEHOLDER) : null;
        if ($encodedStyle !== null) {
            $element->removeAttribute(self::STYLE_PLACEHOLDER);
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = trim($attribute->nodeValue ?? '');

            if (str_starts_with($name, 'on') || $name === 'srcset' || $name === 'style') {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if (! in_array($name, $allowed, true)) {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if ($name === 'href') {
                $normalizedHref = self::normalizeHref($value);

                if ($normalizedHref === null) {
                    $element->removeAttribute('href');
                    continue;
                }

                $element->setAttribute('href', $normalizedHref);

                if ($element->getAttribute('target') === '_blank') {
                    $element->setAttribute('rel', 'noopener noreferrer nofollow');
                }
            }

            if ($name === 'src') {
                $normalizedSrc = self::normalizeImageSource($value);

                if (! self::isAllowedImageSource($normalizedSrc)) {
                    $element->removeAttribute('src');
                    continue;
                }

                $element->setAttribute('src', $normalizedSrc);
            }
        }

        if ($encodedStyle) {
            $decoded = base64_decode($encodedStyle, true);
            if ($decoded !== false) {
                $sanitizedStyle = self::sanitizeStyleAttribute($decoded);
                if ($sanitizedStyle !== '') {
                    $element->setAttribute('style', $sanitizedStyle);
                }
            }
        }

        if ($tag === 'a' && ! $element->hasAttribute('href')) {
            $fallbackHref = self::normalizeHref(trim((string) $element->textContent));

            if ($fallbackHref !== null) {
                $element->setAttribute('href', $fallbackHref);

                if ($element->getAttribute('target') === '_blank') {
                    $element->setAttribute('rel', 'noopener noreferrer nofollow');
                }
            }
        }
    }

    private static function sanitizeStyleAttribute(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $safe = [];
        foreach (preg_split('/\\s*;\\s*/', $value, -1, PREG_SPLIT_NO_EMPTY) as $chunk) {
            $parts = explode(':', $chunk, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $property = strtolower(trim($parts[0]));
            $raw = trim($parts[1]);
            if ($raw === '') {
                continue;
            }

            if (in_array($property, ['color', 'background-color'], true)) {
                if (! preg_match('/^(#[0-9a-fA-F]{3,8}|rgba?\\([0-9\\s.,%]+\\)|hsla?\\([0-9\\s.,%]+\\)|[a-zA-Z]+)$/', $raw)) {
                    continue;
                }

                $safe[] = $property . ': ' . $raw;
                continue;
            }

            if ($property === 'font-size') {
                if (! preg_match('/^(?:[0-9]+(?:\\.[0-9]+)?)(?:px|rem|em|%)$/i', $raw)) {
                    continue;
                }

                $safe[] = $property . ': ' . strtolower($raw);
                continue;
            }

            if ($property === 'line-height') {
                if (! preg_match('/^(?:normal|[0-9]+(?:\\.[0-9]+)?(?:px|rem|em|%)?)$/i', $raw)) {
                    continue;
                }

                $safe[] = $property . ': ' . strtolower($raw);
                continue;
            }

            if ($property === 'font-family') {
                if (! preg_match('/^[\p{L}\p{N}\s,"\'.\-_,]+$/u', $raw)) {
                    continue;
                }

                $safe[] = $property . ': ' . $raw;
            }
        }

        return implode('; ', $safe);
    }

    private static function normalizeHref(string $value): ?string
    {
        if ($value === '' || str_starts_with($value, '#') || str_starts_with($value, '/')) {
            return $value;
        }

        if ((bool) preg_match('/^(https?:|mailto:|tel:)/i', $value)) {
            return $value;
        }

        if ((bool) preg_match('/^(www\.)[^\s]+$/i', $value)) {
            return 'https://' . $value;
        }

        if ((bool) preg_match('/^[a-z0-9.-]+\.[a-z]{2,}(\/[^\s]*)?$/i', $value)) {
            return 'https://' . $value;
        }

        return null;
    }

    private static function isAllowedImageSource(string $value): bool
    {
        if ($value === '' || str_starts_with($value, '/')) {
            return true;
        }

        if ((bool) preg_match('/^data:image\/[a-z0-9.+-]+;base64,/i', $value)) {
            return true;
        }

        return (bool) preg_match('/^https?:/i', $value);
    }

    private static function normalizeImageSource(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        if ((bool) preg_match('/^https?:/i', $value)) {
            return $value;
        }

        if ((bool) preg_match('/^data:image\/[a-z0-9.+-]+;base64,/i', $value)) {
            return $value;
        }

        if ((bool) preg_match('#^(?:\./)?uploads/[^\s]+$#i', $value)) {
            return '/' . ltrim(preg_replace('#^\./#', '', $value) ?? $value, '/');
        }

        return $value;
    }
}
