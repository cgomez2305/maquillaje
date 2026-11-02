<?php
declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Saneador por lista blanca para el HTML de los artículos (§14.3). Se aplica al guardar.
 *
 * - Etiquetas permitidas y sus atributos: ver ALLOWED. Todo lo demás se elimina (atributos on*,
 *   style, class, data-*…). Las etiquetas peligrosas se eliminan con su contenido; las demás
 *   desconocidas se «desenvuelven» conservando el texto.
 * - Enlaces: solo rutas internas, #anclas, https:// y mailto:. Los externos llevan rel="noopener nofollow".
 * - Imágenes: solo src interno bajo /assets/.
 * - h2 y blockquote reciben un id único derivado del texto (tabla de contenido).
 */
final class HtmlSanitizer
{
    private const ALLOWED = [
        'p' => [], 'h2' => [], 'h3' => [], 'ul' => [], 'ol' => [], 'li' => [],
        'strong' => [], 'em' => [], 'br' => [],
        'a' => ['href', 'rel'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [],
        'th' => ['scope'], 'td' => [],
        'blockquote' => [], 'figure' => [], 'figcaption' => [],
        'img' => ['src', 'alt', 'width', 'height'],
    ];

    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form', 'input', 'textarea',
        'select', 'button', 'template', 'noscript', 'link', 'meta', 'base', 'frame', 'frameset', 'applet', 'head', 'title',
    ];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        $doc = self::load($html);
        $root = $doc->getElementById('__root');
        if ($root === null) {
            return '';
        }
        self::walk($root);
        self::assignIds($doc);
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim($out);
    }

    /**
     * Encabezados para la tabla de contenido: h2 y blockquote con id.
     * @return list<array{id:string,text:string}>
     */
    public static function headings(string $cleanHtml): array
    {
        if (trim($cleanHtml) === '') {
            return [];
        }
        $doc = self::load($cleanHtml);
        $xp = new DOMXPath($doc);
        $out = [];
        foreach ($xp->query('//*[@id="__root"]/*[(self::h2 or self::blockquote) and @id]') ?: [] as $el) {
            /** @var DOMElement $el */
            $text = $el->tagName === 'blockquote'
                ? trim((string) ($xp->query('.//p', $el)?->item(0)?->textContent ?? $el->textContent))
                : trim($el->textContent);
            $out[] = ['id' => $el->getAttribute('id'), 'text' => mb_substr($text, 0, 80)];
        }
        return $out;
    }

    private static function load(string $html): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<!doctype html><html><head><meta charset="utf-8"></head><body><div id="__root">' . $html . '</div></body></html>',
            LIBXML_NONET | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return $doc;
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);
                continue;
            }
            self::walk($child);
            if (!array_key_exists($tag, self::ALLOWED)) {
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            self::filterAttributes($child, $tag);
            if ($tag === 'img' && !$child->hasAttribute('src')) {
                $node->removeChild($child);
            }
        }
    }

    private static function filterAttributes(DOMElement $el, string $tag): void
    {
        $allowed = self::ALLOWED[$tag];
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);
            $keep = in_array($name, $allowed, true);
            if ($keep && $name === 'href') {
                $keep = self::safeHref($value);
            } elseif ($keep && $name === 'src') {
                $keep = (bool) preg_match('#^/assets/[A-Za-z0-9/_.-]+$#', $value) && !str_contains($value, '..');
            } elseif ($keep && in_array($name, ['width', 'height'], true)) {
                $keep = (bool) preg_match('/^\d{1,4}$/', $value);
            } elseif ($keep && $name === 'scope') {
                $keep = in_array($value, ['row', 'col'], true);
            } elseif ($keep && $name === 'rel') {
                $keep = false; // se recalcula abajo
            }
            if (!$keep) {
                $el->removeAttribute($attr->name);
            }
        }
        if ($tag === 'a') {
            $href = $el->getAttribute('href');
            if ($href === '') {
                $el->removeAttribute('href');
            } elseif (str_starts_with($href, 'https://') && !self::isOwnHost($href)) {
                $el->setAttribute('rel', 'noopener nofollow');
            }
        }
    }

    private static function safeHref(string $href): bool
    {
        if (preg_match('/[\x00-\x20]/', $href)) {
            return false;
        }
        if (str_starts_with($href, '#')) {
            return (bool) preg_match('/^#[A-Za-z0-9_-]+$/', $href);
        }
        if (str_starts_with($href, '/')) {
            return !str_starts_with($href, '//') && !str_contains($href, '\\');
        }
        if (str_starts_with(strtolower($href), 'mailto:')) {
            return (bool) filter_var(substr($href, 7), FILTER_VALIDATE_EMAIL);
        }
        return str_starts_with($href, 'https://') && filter_var($href, FILTER_VALIDATE_URL) !== false;
    }

    private static function isOwnHost(string $url): bool
    {
        return strtolower((string) parse_url($url, PHP_URL_HOST)) === strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
    }

    private static function assignIds(DOMDocument $doc): void
    {
        $used = [];
        $xp = new DOMXPath($doc);
        foreach ($xp->query('//*[@id="__root"]//*[@id]') ?: [] as $el) {
            /** @var DOMElement $el */
            if (!in_array($el->tagName, ['h2', 'blockquote'], true)) {
                $el->removeAttribute('id');
            }
        }
        foreach ($xp->query('//*[@id="__root"]/*[self::h2 or self::blockquote]') ?: [] as $el) {
            /** @var DOMElement $el */
            $source = $el->tagName === 'blockquote'
                ? (string) ($xp->query('.//p', $el)?->item(0)?->textContent ?? $el->textContent)
                : $el->textContent;
            $base = self::slug($source) ?: 'seccion';
            $id = $base;
            for ($i = 2; isset($used[$id]); $i++) {
                $id = $base . '-' . $i;
            }
            $used[$id] = true;
            $el->setAttribute('id', $id);
        }
    }

    public static function slug(string $text): string
    {
        $t = mb_strtolower(trim($text));
        $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $t = preg_replace('/[^a-z0-9]+/', '-', $t) ?? '';
        return substr(trim($t, '-'), 0, 60);
    }
}
