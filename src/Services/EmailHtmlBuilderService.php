<?php

namespace Anonimatrix\PageEditor\Services;

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

        return view('cms::emails.layout', [
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
