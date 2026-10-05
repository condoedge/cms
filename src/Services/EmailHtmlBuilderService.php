<?php

namespace Anonimatrix\PageEditor\Services;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class EmailHtmlBuilderService
{
    public function buildFromPage($page, ?array $variables = null): string
    {
        $htmlContent = $variables === null
            ? $page->getHtmlContent()
            : $page->getHtmlContent($variables);

        return $this->buildEmailHtml(
            $htmlContent,
            $page->getExteriorBackgroundColor(),
            $page->getContentBackgroundColor(),
            $page->getTextColor(),
            $page->getLinkColor(),
            $page->getFontSize(),
            $page->getContentMaxWidth(),
            $page->getFontFamily(),
        );
    }

    public function buildEmailHtml(
        string $content,
        string $bgColor,
        string $contentBg,
        string $textColor,
        string $linkColor,
        $fontSize,
        $maxWidth,
        string $fontFamily,
    ): string {
        $consolidated = $this->consolidateStyles($this->outlookSafeColors($this->stripEditorOnlyProperties($content)));

        $html = view('cms::emails.layout', [
            'lang' => app()->getLocale(),
            'content' => $consolidated['html'],
            'inlineCss' => $consolidated['css'],
            'bgColor' => $bgColor,
            'contentBg' => $contentBg,
            'textColor' => $textColor,
            'linkColor' => $linkColor,
            'fontSize' => $fontSize,
            'maxWidth' => $maxWidth,
            'fontFamily' => $fontFamily,
        ])->render();

        return $this->inlineStyles($html);
    }

    // Inline the <style> rules onto each element. Outlook (Word engine) and
    // Gmail (which can strip <style> on clipping/forwarding) honour inline
    // styles far more reliably than a <style> block. @media and pseudo rules
    // can't be inlined and are preserved in the kept <style> block for the
    // clients (Apple/iOS) that do use them.
    protected function inlineStyles(string $html): string
    {
        $inlined = (new CssToInlineStyles())->convert($html);

        // The inliner's DOM serialization drops the doctype; re-add it so clients
        // render in standards mode (quirks mode breaks the box model on Outlook.com).
        if (stripos($inlined, '<!doctype') === false) {
            $inlined = "<!DOCTYPE html>\n" . $inlined;
        }

        return $inlined;
    }

    // The editor stores its own settings as pseudo-CSS (height-auto, link-color, aspect-ratio: free, empty values): not CSS for a mail client.
    protected function stripEditorOnlyProperties(string $html): string
    {
        return preg_replace_callback('/style="([^"]*)"/i', function ($m) {
            $clean = preg_replace('/(?:^|;)\s*(?:height-auto|link-color|aspect-ratio)\s*:[^;]*/i', '', $m[1]);
            $clean = preg_replace('/(?:^|;)\s*[a-z-]+\s*:\s*(?=;|$)/i', '', $clean);

            return 'style="' . trim($clean, "; ") . '"';
        }, $html);
    }

    // Outlook (Word engine) ignores hsl() and rgb() colours: the editor writes text highlights as hsl().
    protected function outlookSafeColors(string $html): string
    {
        $html = preg_replace_callback('/hsla?\(\s*([\d.]+)(?:deg)?[\s,]+([\d.]+)%[\s,]+([\d.]+)%(?:[\s,\/]+[\d.]+%?)?\s*\)/i', function ($m) {
            return $this->hslToHex((float) $m[1], (float) $m[2] / 100, (float) $m[3] / 100);
        }, $html);

        return preg_replace_callback('/rgba?\(\s*(\d{1,3})[\s,]+(\d{1,3})[\s,]+(\d{1,3})(?:[\s,\/]+[\d.]+%?)?\s*\)/i', function ($m) {
            return sprintf('#%02x%02x%02x', min(255, (int) $m[1]), min(255, (int) $m[2]), min(255, (int) $m[3]));
        }, $html);
    }

    protected function hslToHex(float $h, float $s, float $l): string
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0],
            $h < 120 => [$x, $c, 0],
            $h < 180 => [0, $c, $x],
            $h < 240 => [0, $x, $c],
            $h < 300 => [$x, 0, $c],
            default => [$c, 0, $x],
        };

        return sprintf('#%02x%02x%02x', round(($r + $m) * 255), round(($g + $m) * 255), round(($b + $m) * 255));
    }

    // Pulls every inline <style> tag into one block so Gmail's 8192-char per-style cap isn't tripped.
    protected function consolidateStyles(string $html): array
    {
        $css = '';

        $html = preg_replace_callback('/<style[^>]*>(.*?)<\/style>/si', function ($matches) use (&$css) {
            $css .= $matches[1] . "\n";
            return '';
        }, $html);

        return ['html' => $html, 'css' => trim($css)];
    }
}
