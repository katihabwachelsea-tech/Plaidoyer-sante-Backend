<?php

namespace Tests\Unit\Models;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppNotificationTest extends TestCase
{
    public function test_notification_uses_its_table_and_casts_data_and_read_time(): void
    {
        $notification = new AppNotification([
            'data' => ['appointment_id' => 12],
            'read_at' => '2026-10-10 12:00:00',
        ]);

        $this->assertSame('app_notifications', $notification->getTable());
        $this->assertSame(['appointment_id' => 12], $notification->data);
        $this->assertInstanceOf(Carbon::class, $notification->read_at);
    }

    public function test_notification_belongs_to_a_user(): void
    {
        $relation = (new AppNotification)->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame(User::class, $relation->getRelated()::class);
    }
}
