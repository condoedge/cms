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
        $consolidated = $this->consolidateStyles($content);

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
