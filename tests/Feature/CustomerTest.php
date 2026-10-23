<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Customer',
            'email' => 'test.customer@example.com',
            'phone' => '0771112222',
            'address' => '1 Test Road, Colombo',
        ], $overrides);
    }

    public function test_guest_cannot_access_customers(): void
    {
        $this->getJson('/api/customers')->assertStatus(401);
    }

    #[DataProvider('allRoles')]
    public function test_every_role_can_list_and_view_customers(Role $role): void
    {
        $this->actingAsRole($role);
        $customer = Customer::factory()->create();

        $this->getJson('/api/customers')->assertOk();
        $this->getJson("/api/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $customer->id);
    }

    public function test_customers_are_paginated_10_per_page_by_default(): void
    {
        $this->actingAsRole(Role::Owner);
        Customer::factory()->count(12)->create();

        $this->getJson('/api/customers')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 12);
    }

    public function test_missing_customer_returns_404(): void
    {
        $this->actingAsRole(Role::Owner);

        $this->getJson('/api/customers/9999')->assertStatus(404);
    }

    #[DataProvider('allRoles')]
    public function test_every_role_can_create_a_customer(Role $role): void
    {
        $this->actingAsRole($role);

        $this->postJson('/api/customers', $this->validPayload())
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Customer');

        $this->assertDatabaseHas('customers', ['email' => 'test.customer@example.com']);
    }

    public function test_create_customer_requires_name(): void
    {
        $this->actingAsRole(Role::Owner);

        $this->postJson('/api/customers', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $this->actingAsRole(Role::Owner);
        Customer::factory()->create(['email' => 'nimal@example.com']);

        $this->postJson('/api/customers', $this->validPayload(['email' => 'nimal@example.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_invalid_phone_is_rejected(): void
    {
        $this->actingAsRole(Role::Owner);

        $this->postJson('/api/customers', $this->validPayload(['phone' => 'abc']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    #[DataProvider('allRoles')]
    public function test_every_role_can_update_a_customer(Role $role): void
    {
        $this->actingAsRole($role);
        $customer = Customer::factory()->create();

        $this->patchJson("/api/customers/{$customer->id}", ['name' => 'Renamed Customer'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed Customer');

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Renamed Customer']);
    }

    public function test_customer_can_be_saved_with_own_email(): void
    {
        $this->actingAsRole(Role::Owner);
        $customer = Customer::factory()->create(['email' => 'keep@example.com']);

        $this->putJson("/api/customers/{$customer->id}", $this->validPayload(['email' => 'keep@example.com']))
            ->assertOk();
    }

    #[DataProvider('deleteRoles')]
    public function test_delete_customer_permission(Role $role, int $expectedStatus): void
    {
        $this->actingAsRole($role);
        $customer = Customer::factory()->create();

        $this->deleteJson("/api/customers/{$customer->id}")->assertStatus($expectedStatus);

        if ($expectedStatus === 200) {
            $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        } else {
            $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        }
    }

    public static function allRoles(): array
    {
        return [
            'owner' => [Role::Owner],
            'manager' => [Role::Manager],
            'cashier' => [Role::Cashier],
        ];
    }

    public static function deleteRoles(): array
    {
        return [
            'owner can delete' => [Role::Owner, 200],
            'manager cannot delete' => [Role::Manager, 403],
            'cashier cannot delete' => [Role::Cashier, 403],
        ];
    }
}