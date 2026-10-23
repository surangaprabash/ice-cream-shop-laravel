<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_list_categories(): void
    {
        $this->getJson('/api/categories')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_categories(): void
    {
        $this->actingAsRole(Role::Cashier);
        Category::factory()->count(3)->create();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }
}