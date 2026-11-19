<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'nom' => 'Patient Test',
            'email' => 'reset@example.com',
            'password' => 'OldPassword123',
            'role' => 'patient',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_verified_reset_token_expires_after_fifteen_minutes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00'));
        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => Hash::make('verified-reset-token'),
            'created_at' => now()->subMinutes(16),
        ]);

        $this->postJson('/api/password/reset', [
            'email' => $this->user->email,
            'reset_token' => 'verified-reset-token',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Ce jeton a expiré. Veuillez recommencer la procédure.');

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $this->user->email,
        ]);
        $this->assertTrue(Hash::check('OldPassword123', $this->user->fresh()->password));
    }

    public function test_verifying_otp_starts_a_fresh_reset_token_expiration_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00'));
        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => Hash::make('123456'),
            'created_at' => now()->subMinutes(14),
        ]);

        $response = $this->postJson('/api/password/verify-code', [
            'email' => $this->user->email,
            'code' => '123456',
        ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $record = DB::table('password_reset_tokens')
            ->where('email', $this->user->email)
            ->first();

        $this->assertNotNull($record);
        $this->assertTrue(Hash::check($response->json('reset_token'), $record->token));
        $this->assertEquals(now()->timestamp, Carbon::parse($record->created_at)->timestamp);
    }

    public function test_successful_password_reset_consumes_token(): void
    {
        DB::table('password_reset_tokens')->insert([
            'email' => $this->user->email,
            'token' => Hash::make('verified-reset-token'),
            'created_at' => now(),
        ]);

        $this->postJson('/api/password/reset', [
            'email' => $this->user->email,
            'reset_token' => 'verified-reset-token',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertTrue(Hash::check('NewPassword123', $this->user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $this->user->email,
        ]);
    }
}
