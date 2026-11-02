<?php
namespace App\Support;
use App\Mail\QuotationMail;
use App\Models\Business;
use Illuminate\Support\Facades\Mail;
class QuotationSender
{
    public function send(Business $business, string $recipient, QuotationMail $message): void
    {
        $settings = $business->quotation_mail;
        if (!$settings) {
            Mail::to($recipient)->send($message);
            return;
        }
        $message->from($settings['from_address'], $settings['from_name'])
            ->replyTo($settings['from_address'], $settings['from_name']);
        // Build an isolated transport so tenant credentials never leak into another mailer.
        $mailer = Mail::build([
            'transport' => 'smtp', 'scheme' => (int) $settings['port'] === 465 ? 'smtps' : 'smtp',
            'host' => $settings['host'], 'port' => (int) $settings['port'],
            'username' => $settings['username'], 'password' => $settings['password'],
            'require_tls' => true, 'timeout' => 20,
        ]);
        $mailer->to($recipient)->send($message);
    }
}
