<?php

namespace Tests\Feature;

use App\Mail\EmailManager;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailTestCommandTest extends TestCase
{
    public function test_mail_test_command_warns_when_log_mailer_is_active(): void
    {
        Mail::fake();

        config()->set('mail.default', 'log');
        config()->set('mail.from.address', 'noreply@example.com');
        config()->set('mail.from.name', 'KIDAN');

        $this->artisan('mail:test', ['email' => 'customer@example.com'])
            ->expectsOutput('KIDAN mail diagnostic')
            ->expectsOutput('Mailer: log')
            ->expectsOutput('Queue connection: sync')
            ->expectsOutput('From address: noreply@example.com')
            ->expectsOutput('From name: KIDAN')
            ->expectsOutput('The current mailer [log] does not deliver to external inboxes. This test will confirm Laravel mail dispatch only.')
            ->expectsOutput('Mail was accepted by Laravel, but [log] will not send externally.')
            ->assertSuccessful();

        Mail::assertSent(EmailManager::class);
    }
}
