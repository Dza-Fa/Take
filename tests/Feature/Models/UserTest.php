<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_admin_role_is_recognized(): void
    {
        $user = new User(['role' => 'admin']);

        $this->assertTrue($user->isAdmin());
    }

    public function test_warga_role_is_not_recognized_as_admin(): void
    {
        $user = new User(['role' => 'warga']);

        $this->assertFalse($user->isAdmin());
    }
}
