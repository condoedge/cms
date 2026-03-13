<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Illuminate\Support\Facades\Mail;
use Kompo\Form;

class SendTestEmailForm extends Form
{
    public $id = 'send-test-email-form';

    protected $prefixGroup = "";

    public function created()
    {
        $this->model(PageModel::find($this->prop('page_id')) ?? PageModel::make());
    }

    public function render()
    {
        return _Rows(
            _FlexBetween(
                _Html('cms::cms.send-test-email')->class('text-base font-semibold text-gray-800'),
                _Link()->icon('x')->class('text-gray-400 hover:text-gray-600')
                    ->run('() => { document.querySelector(".vlTestEmailModal").remove() }'),
            )->class('mb-4'),

            _Html('cms::cms.send-test-email-desc')->class('text-sm text-gray-500 mb-4'),

            _Input('cms::cms.recipient-email')
                ->name('test_email')
                ->type('email')
                ->value(auth()->user()?->email)
                ->class('mb-4'),

            _Input('cms::cms.email-subject')
                ->name('test_subject')
                ->value($this->model->title ?: __('cms::cms.untitled-email'))
                ->class('mb-4'),

            _FlexEnd(
                _Link('cms::cms.cancel')
                    ->class('vlTestEmailCancelBtn')
                    ->run('() => { document.querySelector(".vlTestEmailModal").remove() }'),
                _Button('cms::cms.send-test')
                    ->class('vlTestEmailSendBtn')
                    ->selfPost('sendTestEmail')
                    ->withAllFormValues()
                    ->onSuccess(fn($e) => $e->run('() => { document.querySelector(".vlTestEmailModal").remove(); vlEmailEditor.showToast("'.__('cms::cms.test-email-sent').'") }')),
            )->class('gap-3'),

            _Html($this->modalStyles()),
        )->class('vlTestEmailFormInner');
    }

    public function sendTestEmail()
    {
        $email = request('test_email');
        $subject = request('test_subject') ?: ($this->model->title ?: __('cms::cms.untitled-email'));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $page = $this->model;
        $htmlContent = $page->getHtmlContent();

        $bgColor = $page->getExteriorBackgroundColor();
        $contentBg = $page->getContentBackgroundColor();
        $textColor = $page->getTextColor();
        $linkColor = $page->getLinkColor();
        $fontSize = $page->getFontSize();
        $maxWidth = $page->getContentMaxWidth();
        $fontFamily = $page->getFontFamily();

        $fullHtml = $this->buildEmailHtml($htmlContent, $bgColor, $contentBg, $textColor, $linkColor, $fontSize, $maxWidth, $fontFamily);

        Mail::html($fullHtml, function ($message) use ($email, $subject) {
            $message->to($email)
                ->subject('[TEST] ' . $subject);
        });
    }

    public function buildEmailHtml($content, $bgColor, $contentBg, $textColor, $linkColor, $fontSize, $maxWidth, $fontFamily)
    {
        $lang = app()->getLocale();

        // Consolidate all inline <style> tags from content into the <head>
        $consolidated = $this->consolidateStyles($content);
        $content = $consolidated['html'];
        $inlineCss = $consolidated['css'];

        return '<!DOCTYPE html>
<html lang="'.$lang.'" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <!--[if gte mso 9]><xml><o:OfficeDocumentSettings><o:AllowPNG/><o:PixelPerInch>96</o:PixelPerInch></o:OfficeDocumentSettings></xml><![endif]-->
    <style type="text/css">
        /* Email Reset */
        body, #bodyTable { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        body { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        td { border-collapse: collapse; }
        img { border: 0; outline: none; text-decoration: none; display: block; -ms-interpolation-mode: bicubic; max-width: 100%; }
        p, h1, h2, h3, h4, h5, h6 { margin: 0; padding: 0; }
        a { color: '.$linkColor.'; }
        body { background-color: '.$bgColor.'; font-family: '.$fontFamily.'; font-size: '.$fontSize.'px; color: '.$textColor.'; }
        '.$inlineCss.'
    </style>
</head>
<body style="margin:0; padding:0; background-color:'.$bgColor.'; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%;">
    <table id="bodyTable" role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:'.$bgColor.';">
        <tr>
            <td align="center" valign="top" style="padding: 0;">
                <!--[if mso]>
                <table role="presentation" width="'.$maxWidth.'" border="0" cellpadding="0" cellspacing="0" align="center"><tr><td>
                <![endif]-->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="max-width:'.$maxWidth.'px; width:100%; background-color:'.$contentBg.'; font-family:'.$fontFamily.'; font-size:'.$fontSize.'px; color:'.$textColor.';">
                    <tr>
                        <td>'.$content.'</td>
                    </tr>
                </table>
                <!--[if mso]>
                </td></tr></table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Extract all <style> tags from content HTML and consolidate into one CSS string.
     * This prevents Gmail's 8192-char style limit from being exceeded with many blocks.
     */
    protected function consolidateStyles(string $html): array
    {
        $css = '';

        $html = preg_replace_callback('/<style[^>]*>(.*?)<\/style>/si', function ($matches) use (&$css) {
            $css .= $matches[1] . "\n";
            return '';
        }, $html);

        return ['html' => $html, 'css' => trim($css)];
    }

    protected function modalStyles()
    {
        return '<style>
            .vlTestEmailModal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: vlModalFadeIn 0.15s ease;
            }
            @keyframes vlModalFadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            .vlTestEmailFormInner {
                background: #ffffff;
                border-radius: 12px;
                padding: 24px;
                width: 440px;
                max-width: 90vw;
                box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            }
            .vlTestEmailCancelBtn {
                padding: 7px 16px;
                font-size: 13px;
                font-weight: 500;
                color: #374151;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                transition: all 0.15s;
            }
            .vlTestEmailCancelBtn:hover {
                background: #f9fafb;
            }
            .vlTestEmailSendBtn {
                padding: 7px 20px !important;
                font-size: 13px !important;
                font-weight: 600 !important;
                background: #2563eb !important;
                color: #ffffff !important;
                border-radius: 8px !important;
                border: none !important;
            }
            .vlTestEmailSendBtn:hover {
                background: #1d4ed8 !important;
            }
        </style>';
    }
}
