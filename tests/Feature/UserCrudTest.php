<?php

namespace Tests\Feature;

use App\Models\User;
use App\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_crud_lifecycle_as_superadmin(): void
    {
        // Create a superadmin to authenticate
        $admin = User::factory()->create([
            'role' => UserRole::SUPERADMIN->value,
        ]);

        $this->actingAs($admin);

        // Create
        $createData = [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'supersecure',
            'password_confirmation' => 'supersecure',
            'role' => UserRole::USER->value,
            'status' => 'active',
        ];

        $createResponse = $this->postJson(route('admin.users.store'), $createData);
        $createResponse->assertStatus(201)->assertJson(['success' => true]);

        $createdUserId = $createResponse->json('user.id');
        $this->assertDatabaseHas('users', ['id' => $createdUserId, 'email' => 'testuser@example.com']);

        // Update
        $updateData = [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'role' => UserRole::USER->value,
            'status' => 'active',
        ];

        $updateResponse = $this->putJson(route('admin.users.update', ['user' => $createdUserId]), $updateData);
        $updateResponse->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['id' => $createdUserId, 'email' => 'updated@example.com', 'name' => 'Updated User']);

        // Delete
        $deleteResponse = $this->deleteJson(route('admin.users.destroy', ['user' => $createdUserId]));
        $deleteResponse->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseMissing('users', ['id' => $createdUserId]);
    }
}
