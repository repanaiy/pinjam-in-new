<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Kirim email lewat API HTTP Resend. Railway memblokir SMTP keluar di plan
 * non-Pro, jadi SMTP (Gmail) tidak bisa dipakai.
 */
class ResendHttpTransport extends AbstractTransport
{
    public function __construct(private string $key)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $fmt = fn (Address $a) => $a->toString();

        $payload = array_filter([
            'from' => $envelope->getSender()->toString(),
            'to' => array_map($fmt, $this->recipients($email, $envelope, 'to')),
            'cc' => array_map($fmt, $email->getCc()),
            'bcc' => array_map($fmt, $email->getBcc()),
            'reply_to' => array_map($fmt, $email->getReplyTo()),
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
        ]);

        $response = Http::withToken($this->key)
            ->timeout(15)
            ->post('https://api.resend.com/emails', $payload);

        if ($response->failed()) {
            throw new TransportException('Resend API error: '.$response->body(), $response->status());
        }
    }

    private function recipients(Email $email, Envelope $envelope, string $type): array
    {
        return $email->getTo() ?: $envelope->getRecipients();
    }

    public function __toString(): string
    {
        return 'resend-http';
    }
}
