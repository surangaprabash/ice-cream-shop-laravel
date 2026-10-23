<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(int $categoryId, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $categoryId,
            'name' => 'Choco Chip Cone',
            'description' => 'Chocolate chip single scoop',
            'price' => 360.00,
            'quantity' => 40,
            'unit' => 'cone',
        ], $overrides);
    }

    // ---------- Authentication ----------

    public function test_guest_cannot_access_items(): void
    {
        $this->getJson('/api/items')->assertStatus(401);
    }

    // ---------- Read ----------

    #[DataProvider('allRoles')]
    public function test_every_role_can_list_and_view_items(Role $role): void
    {
        $this->actingAsRole($role);
        $item = Item::factory()->create();

        $this->getJson('/api/items')->assertOk();
        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonStructure(['data' => ['id', 'name', 'price', 'quantity', 'unit', 'category' => ['id', 'name']]]);
    }

    public function test_items_are_paginated_10_per_page_by_default(): void
    {
        $this->actingAsRole(Role::Owner);
        Item::factory()->count(12)->create();

        $this->getJson('/api/items')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 12);

        $this->getJson('/api/items?page=2')->assertJsonCount(2, 'data');
    }

    public function test_items_can_be_searched_by_name(): void
    {
        $this->actingAsRole(Role::Cashier);
        Item::factory()->create(['name' => 'Dark Chocolate Tub']);
        Item::factory()->create(['name' => 'Vanilla Cone']);

        $this->getJson('/api/items?search=choc')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Dark Chocolate Tub');
    }

    public function test_missing_item_returns_404(): void
    {
        $this->actingAsRole(Role::Owner);

        $this->getJson('/api/items/9999')->assertStatus(404);
    }

    // ---------- Create (owner and manager only) ----------

    #[DataProvider('createItemRoles')]
    public function test_create_item_permission(Role $role, int $expectedStatus): void
    {
        $this->actingAsRole($role);
        $category = Category::factory()->create();

        $this->postJson('/api/items', $this->validPayload($category->id))
            ->assertStatus($expectedStatus);

        $this->assertDatabaseCount('items', $expectedStatus === 201 ? 1 : 0);
    }

    public function test_item_unit_defaults_to_tub(): void
    {
        $this->actingAsRole(Role::Owner);
        $category = Category::factory()->create();
        $payload = $this->validPayload($category->id);
        unset($payload['unit']);

        $this->postJson('/api/items', $payload)->assertStatus(201);

        $this->assertDatabaseHas('items', ['name' => 'Choco Chip Cone', 'unit' => 'tub']);
    }

    // ---------- Validation ----------

    public function test_create_item_requires_fields(): void
    {
        $this->actingAsRole(Role::Owner);

        $this->postJson('/api/items', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'name', 'price', 'quantity']);
    }

    public function test_negative_price_is_rejected(): void
    {
        $this->actingAsRole(Role::Owner);
        $category = Category::factory()->create();

        $this->postJson('/api/items', $this->validPayload($category->id, ['price' => -5]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    public function test_decimal_or_negative_quantity_is_rejected(): void
    {
        $this->actingAsRole(Role::Owner);
        $category = Category::factory()->create();

        $this->postJson('/api/items', $this->validPayload($category->id, ['quantity' => 2.5]))
            ->assertStatus(422)->assertJsonValidationErrors(['quantity']);

        $this->postJson('/api/items', $this->validPayload($category->id, ['quantity' => -1]))
            ->assertStatus(422)->assertJsonValidationErrors(['quantity']);
    }

    public function test_unknown_category_is_rejected(): void
    {
        $this->actingAsRole(Role::Owner);

        $this->postJson('/api/items', $this->validPayload(999))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_duplicate_name_in_same_category_is_rejected(): void
    {
        $this->actingAsRole(Role::Owner);
        $category = Category::factory()->create();
        Item::factory()->create(['category_id' => $category->id, 'name' => 'Choco Chip Cone']);

        $this->postJson('/api/items', $this->validPayload($category->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_same_name_in_different_category_is_allowed(): void
    {
        $this->actingAsRole(Role::Owner);
        $first = Category::factory()->create();
        $second = Category::factory()->create();
        Item::factory()->create(['category_id' => $first->id, 'name' => 'Choco Chip Cone']);

        $this->postJson('/api/items', $this->validPayload($second->id))
            ->assertStatus(201);
    }

    // ---------- Update (all roles) ----------

    #[DataProvider('allRoles')]
    public function test_every_role_can_update_an_item(Role $role): void
    {
        $this->actingAsRole($role);
        $item = Item::factory()->create(['quantity' => 10]);

        $this->patchJson("/api/items/{$item->id}", ['quantity' => 50])
            ->assertOk()
            ->assertJsonPath('data.quantity', 50);

        $this->assertDatabaseHas('items', ['id' => $item->id, 'quantity' => 50]);
    }

    public function test_put_updates_all_fields(): void
    {
        $this->actingAsRole(Role::Cashier);
        $item = Item::factory()->create();

        $this->putJson("/api/items/{$item->id}", $this->validPayload($item->category_id, [
            'name' => 'Updated Test Item',
            'price' => 3500,
            'quantity' => 25,
        ]))->assertOk();

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Updated Test Item',
            'quantity' => 25,
        ]);
    }

    public function test_item_can_be_saved_with_its_own_name(): void
    {
        $this->actingAsRole(Role::Owner);
        $item = Item::factory()->create(['name' => 'Same Name']);

        $this->patchJson("/api/items/{$item->id}", ['name' => 'Same Name', 'quantity' => 5])
            ->assertOk();
    }

    // ---------- Delete (owner only) ----------

    #[DataProvider('deleteRoles')]
    public function test_delete_item_permission(Role $role, int $expectedStatus): void
    {
        $this->actingAsRole($role);
        $item = Item::factory()->create();

        $this->deleteJson("/api/items/{$item->id}")->assertStatus($expectedStatus);

        if ($expectedStatus === 200) {
            $this->assertDatabaseMissing('items', ['id' => $item->id]);
        } else {
            $this->assertDatabaseHas('items', ['id' => $item->id]);
        }
    }

    // ---------- Data providers ----------

    public static function allRoles(): array
    {
        return [
            'owner' => [Role::Owner],
            'manager' => [Role::Manager],
            'cashier' => [Role::Cashier],
        ];
    }

    public static function createItemRoles(): array
    {
        return [
            'owner can create' => [Role::Owner, 201],
            'manager can create' => [Role::Manager, 201],
            'cashier cannot create' => [Role::Cashier, 403],
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