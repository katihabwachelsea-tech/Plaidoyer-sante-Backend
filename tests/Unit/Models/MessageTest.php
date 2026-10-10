<?php

namespace Tests\Unit\Models;

use App\Models\Appointment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class MessageTest extends TestCase
{
    public function test_message_belongs_to_an_appointment_and_a_sender(): void
    {
        $message = new Message;

        $this->assertInstanceOf(BelongsTo::class, $message->appointment());
        $this->assertSame(Appointment::class, $message->appointment()->getRelated()::class);
        $this->assertInstanceOf(BelongsTo::class, $message->sender());
        $this->assertSame(User::class, $message->sender()->getRelated()::class);
        $this->assertSame('sender_id', $message->sender()->getForeignKeyName());
    }

    public function test_format_identifies_the_current_sender_and_read_state(): void
    {
        $message = new Message([
            'contenu' => 'Bonjour',
            'sender_id' => 7,
            'lu_at' => '2026-10-10 12:00:00',
        ]);
        $message->setRelation('sender', new User(['nom' => 'Dr Karim']));

        $formatted = $message->format(7);

        $this->assertSame('Bonjour', $formatted['contenu']);
        $this->assertSame('Dr Karim', $formatted['sender_nom']);
        $this->assertTrue($formatted['is_mine']);
        $this->assertTrue($formatted['lu']);
    }
}
