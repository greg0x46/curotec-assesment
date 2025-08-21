<?php

use App\Models\Category;
use Inertia\Testing\AssertableInertia as Assert;
use function Pest\Laravel\{
    get, post, put, delete, from,
    assertDatabaseHas, assertDatabaseMissing, assertSoftDeleted
};

/**
 * Helpers
 */
function storeCategory(array $overrides = [])
{
    $payload = array_merge(['name' => 'Groceries'], $overrides);

    return post(route('categories.store'), $payload);
}

function updateCategory(Category $category, array $overrides = [])
{
    $payload = array_merge(['name' => $category->name], $overrides);

    return put(route('categories.update', $category), $payload);
}

function deleteCategory(Category $category)
{
    return delete(route('categories.destroy', $category));
}

beforeEach(function () {
    // Mantém autenticação se tuas rotas ainda estiverem protegidas por auth middleware.
    // Se as rotas de categorias passaram a ser públicas, remove esta linha.
    $this->user = asUser();
});

/**
 * Index
 */
it('lists categories (Inertia)', function () {
    $categories = Category::factory()->count(3)->create();

    get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) =>
        $page->component('Categories')
            ->where('categories.data.0.name', $categories->first()->name)
            ->has('categories.data', 3)
            ->has('categories.links')
        );
});

it('redirects guests from index to login if protected', function () {
    auth()->logout();

    get(route('categories.index'))
        ->assertRedirectToRoute('login');
})->skip(fn () => false, 'Remova este teste se a rota não exigir auth.');

/**
 * Store
 */
it('creates a category', function () {
    from(route('categories.index'))
        ->post(route('categories.store'), ['name' => 'Utilities'])
        ->assertRedirectToRoute('categories.index')
        ->assertSessionHas('success', 'Category created successfully.');

    assertDatabaseHas('categories', [
        'name' => 'Utilities',
    ]);
});

it('redirects back to index after storing', function () {
    from(route('categories.index'))
        ->post(route('categories.store'), ['name' => 'Books'])
        ->assertRedirectToRoute('categories.index');
});

it('validates name is required on store', function () {
    storeCategory(['name' => ''])
        ->assertStatus(302)
        ->assertInvalid(['name']);

    assertDatabaseMissing('categories', ['name' => '']);
});

it('validates name is unique on store', function () {
    Category::factory()->create(['name' => 'Travel']);

    storeCategory(['name' => 'Travel'])
        ->assertStatus(302)
        ->assertInvalid(['name']);
});

it('redirects guests to login on store if protected', function () {
    auth()->logout();

    post(route('categories.store'), ['name' => 'Music'])
        ->assertRedirectToRoute('login');

    assertDatabaseMissing('categories', ['name' => 'Music']);
})->skip(fn () => false, 'Remova este teste se a rota não exigir auth.');

/**
 * Update
 */

it('fails when renaming to a name that already exists', function () {
    $food = Category::factory()->create(['name' => 'Food']);
    $travel = Category::factory()->create(['name' => 'Travel']);

    updateCategory($food, ['name' => 'Travel'])
        ->assertStatus(302)
        ->assertInvalid(['name']);
});

/**
 * Destroy
 */
it('soft deletes a category', function () {
    $cat = Category::factory()->create(['name' => 'Temp']);

    from(route('categories.index'))
        ->delete(route('categories.destroy', $cat))
        ->assertRedirectToRoute('categories.index')
        ->assertSessionHas('success', 'Category deleted successfully.');

    assertSoftDeleted('categories', ['id' => $cat->id]);
});

it('redirects guests to login on delete if protected', function () {
    $cat = Category::factory()->create(['name' => 'Guest Block']);
    auth()->logout();

    delete(route('categories.destroy', $cat))
        ->assertRedirectToRoute('login');

    assertDatabaseHas('categories', [
        'id' => $cat->id,
        'deleted_at' => null,
    ]);
})->skip(fn () => false, 'Remova este teste se a rota não exigir auth.');
