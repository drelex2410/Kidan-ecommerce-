<?php

namespace Tests\Feature\Api\V1\Content;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContactSubmissionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
        Cache::flush();
    }

    public function test_contact_submission_endpoint_stores_valid_message(): void
    {
        $response = $this->postJson('/api/v1/contact-submissions', [
            'full_name' => 'Ada Kidan',
            'email' => 'ada@example.com',
            'phone' => '07000000000',
            'inquiry_type' => 'General Inquiries',
            'message' => 'Please help me confirm the best way to visit your store.',
            'source_page' => 'contact-us',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('contact_submissions', [
            'full_name' => 'Ada Kidan',
            'email' => 'ada@example.com',
            'inquiry_type' => 'General Inquiries',
            'source_page' => 'contact-us',
            'status' => 'new',
        ]);
    }

    public function test_contact_submission_endpoint_validates_required_fields(): void
    {
        $this->postJson('/api/v1/contact-submissions', [
            'full_name' => '',
            'email' => 'not-an-email',
            'message' => '',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'email', 'message']);

        $this->assertSame(0, DB::table('contact_submissions')->count());
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('translations');
        Schema::dropIfExists('contact_submissions');

        Schema::create('contact_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('inquiry_type')->nullable();
            $table->text('message');
            $table->string('source_page')->default('contact-us');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('status')->default('new');
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });

        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->string('lang', 10);
            $table->string('lang_key')->index();
            $table->text('lang_value')->nullable();
            $table->timestamps();
        });
    }
}
