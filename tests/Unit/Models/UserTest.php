<?php

namespace Tests\Unit\Models;

use App\Models\Medecin;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_password_is_hashed_when_assigned(): void
    {
        $user = new User(['password' => 'secret123']);

        $this->assertNotSame('secret123', $user->password);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_sensitive_attributes_are_hidden_from_serialization(): void
    {
        $user = new User([
            'nom' => 'Aline Ndayishimiye',
            'email' => 'aline@example.com',
            'password' => 'secret123',
        ]);

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    public function test_profile_completion_is_cast_to_boolean(): void
    {
        $user = new User(['is_profile_completed' => 1]);

        $this->assertTrue($user->is_profile_completed);
    }

    public function test_user_has_patient_and_doctor_profile_relations(): void
    {
        $user = new User;

        $this->assertInstanceOf(HasOne::class, $user->patient());
        $this->assertSame(Patient::class, $user->patient()->getRelated()::class);
        $this->assertInstanceOf(HasOne::class, $user->medecin());
        $this->assertSame(Medecin::class, $user->medecin()->getRelated()::class);
    }
}
