<?php

namespace App\Console\Commands;

use App\Mail\EmailManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailTestCommand extends Command
{
    protected $signature = 'mail:test {email : Recipient email address}';

    protected $description = 'Send a safe KIDAN test email and print non-secret mail diagnostics.';

    public function handle(): int
    {
        $recipient = (string) $this->argument('email');
        $mailer = (string) config('mail.default');
        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');
        $queueConnection = (string) config('queue.default');

        $this->info('KIDAN mail diagnostic');
        $this->line('Mailer: ' . $mailer);
        $this->line('Queue connection: ' . $queueConnection);
        $this->line('From address: ' . ($fromAddress !== '' ? $fromAddress : '[missing]'));
        $this->line('From name: ' . ($fromName !== '' ? $fromName : '[missing]'));

        if ($mailer === 'smtp') {
            $this->line('SMTP host: ' . (config('mail.mailers.smtp.host') ?: '[missing]'));
            $this->line('SMTP port: ' . (string) (config('mail.mailers.smtp.port') ?: '[missing]'));
            $this->line('SMTP encryption: ' . (config('mail.mailers.smtp.encryption') ?: '[none]'));
            $this->line('SMTP username present: ' . (config('mail.mailers.smtp.username') ? 'yes' : 'no'));
        }

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("The current mailer [{$mailer}] does not deliver to external inboxes. This test will confirm Laravel mail dispatch only.");
        }

        try {
            Mail::to($recipient)->send(new EmailManager([
                'view' => 'emails.newsletter',
                'from' => $fromAddress,
                'subject' => 'KIDAN Mail Test',
                'content' => 'This is a KIDAN mail diagnostic test.',
            ]));
        } catch (\Throwable $exception) {
            Log::error('Mail test command failed', [
                'mailer' => $mailer,
                'queue_connection' => $queueConnection,
                'to' => $recipient,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $this->error('Mail test failed: ' . $exception->getMessage());

            return self::FAILURE;
        }

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("Mail was accepted by Laravel, but [{$mailer}] will not send externally.");
        } else {
            $this->info('Mail test completed successfully.');
        }

        return self::SUCCESS;
    }
}
