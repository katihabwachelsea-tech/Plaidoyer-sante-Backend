<?php

namespace Tests\Unit\Models;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    public function test_last_used_at_is_cast_to_a_datetime(): void
    {
        $token = new DeviceToken(['last_used_at' => '2026-10-10 12:00:00']);

        $this->assertInstanceOf(Carbon::class, $token->last_used_at);
    }

    public function test_device_token_belongs_to_a_user(): void
    {
        $relation = (new DeviceToken)->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame(User::class, $relation->getRelated()::class);
    }
}
