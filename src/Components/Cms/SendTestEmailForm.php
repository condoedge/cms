<?php

namespace Anonimatrix\PageEditor\Components\Cms;

use Anonimatrix\PageEditor\Services\EmailHtmlBuilderService;
use Anonimatrix\PageEditor\Support\Facades\Models\PageModel;
use Condoedge\Utils\Kompo\Common\Modal;
use Illuminate\Support\Facades\Mail;

class SendTestEmailForm extends Modal
{
    public $id = 'send-test-email-form';

    public $_Title = 'cms::cms.send-test-email';

    protected $prefixGroup = "";

    public function created()
    {
        $this->model(PageModel::findOrFail($this->modelKey()));
    }

    public function headerButtons()
    {
        return _SubmitButton('cms::cms.send-test')
            ->selfPost('sendTestEmail')
            ->withAllFormValues()
            ->alert('cms::cms.test-email-sent')
            ->closeModal();
    }

    public function body()
    {
        return [
            _Html('cms::cms.send-test-email-desc')->class('text-sm text-gray-500 mb-4'),

            _Input('cms::cms.recipient-email')
                ->name('test_email', false)
                ->type('email')
                ->value(auth()->user()?->email)
                ->class('mb-4'),

            _Input('cms::cms.email-subject')
                ->name('test_subject', false)
                ->value($this->model->title ?: __('cms::cms.untitled-email')),
        ];
    }

    public function sendTestEmail()
    {
        $email = request('test_email');
        $subject = request('test_subject') ?: ($this->model->title ?: __('cms::cms.untitled-email'));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $fullHtml = app(EmailHtmlBuilderService::class)->buildFromPage($this->model);

        Mail::html($fullHtml, function ($message) use ($email, $subject) {
            $message->to($email)
                ->subject('[TEST] ' . $subject);
        });
    }
}
