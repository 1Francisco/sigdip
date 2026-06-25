---
name: create-php-test
description: Generate PHP Feature + API test files following SIGDIP test conventions
---

## What it does

Generates test files for an entity:
- `tests/Feature/{Entity}Test.php` — web CRUD tests with session auth
- `tests/Feature/Api/{Entity}ApiTest.php` — API CRUD tests with Sanctum

## Instructions

### 1. Ask for details

1. **Entity name** (PascalCase, e.g. `Categoria`)
2. **Table name** (`categorias`)
3. **Route name** (kebab plural, e.g. `categorias`)
4. **Model class** (e.g. `App\Models\Categoria`)
5. **Related models needed in setUp** (e.g. Productor + Predio for dependent entities)
6. **Fields for store/update payloads** and their valid values
7. **HTTP verb for update** (PUT or PATCH)
8. **Admin-only?** (test role denial) or mixed-role?
9. **Unique fields** (for update tests that need `unique:table,column,ignore`)

### 2. Generate Feature Test

File: `tests/Feature/{Entity}Test.php`

```php
<?php

namespace Tests\Feature;

use App\Models\{Entity};
use App\Models\{RelatedModel};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class {Entity}Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        // Seed related models if needed
        // $productor = Productor::factory()->create();
        // $this->predio = Predio::factory()->create(['productor_id' => $productor->id]);
    }

    public function test_index(): void
    {
        {Entity}::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('{entity}.index'));

        $response->assertStatus(200);
    }

    public function test_create(): void
    {
        $response = $this->actingAs($this->admin)->get(route('{entity}.create'));

        $response->assertStatus(200);
    }

    public function test_store(): void
    {
        $data = [
            // 'field' => 'value',
        ];

        $response = $this->actingAs($this->admin)->post(route('{entity}.store'), $data);

        $response->assertRedirect(route('{entity}.index'));
        $this->assertDatabaseHas('{table}', [
            // 'field' => 'value',
        ]);
    }

    public function test_show(): void
    {
        ${model} = {Entity}::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('{entity}.show', ${model}->id));

        $response->assertStatus(200);
    }

    public function test_edit(): void
    {
        ${model} = {Entity}::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('{entity}.edit', ${model}->id));

        $response->assertStatus(200);
    }

    public function test_update(): void
    {
        ${model} = {Entity}::factory()->create();

        $data = [
            // 'field' => 'updated value',
        ];

        $response = $this->actingAs($this->admin)->patch(route('{entity}.update', ${model}->id), $data);

        $response->assertRedirect(route('{entity}.index'));
        $this->assertDatabaseHas('{table}', [
            // 'field' => 'updated value',
        ]);
    }

    public function test_destroy(): void
    {
        ${model} = {Entity}::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('{entity}.destroy', ${model}->id));

        $response->assertRedirect(route('{entity}.index'));
        $this->assertDatabaseMissing('{table}', ['id' => ${model}->id]);
    }
}
```

For admin-only entities, add:
```php
public function test_medico_no_puede_acceder(): void
{
    $medico = User::factory()->create();
    $medico->assignRole('Medico_Campo');

    $response = $this->actingAs($medico)->get(route('{entity}.index'));

    $response->assertForbidden();
}
```

### 3. Generate API Test

File: `tests/Feature/Api/{Entity}ApiTest.php`

```php
<?php

namespace Tests\Feature\Api;

use App\Models\{Entity};
use App\Models\{RelatedModel};
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class {Entity}ApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Medico_Campo']);

        $this->user = User::factory()->create();
        $this->user->assignRole('Administrador');
        Sanctum::actingAs($this->user);

        // Seed related models if needed
    }

    public function test_index(): void
    {
        {Entity}::factory()->count(3)->create();

        $response = $this->getJson('/api/{entity}');

        $response->assertStatus(200);
    }

    public function test_show(): void
    {
        ${model} = {Entity}::factory()->create();

        $response = $this->getJson('/api/{entity}/'.${model}->id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', ${model}->id);
    }

    public function test_store(): void
    {
        $data = [
            // 'field' => 'value',
        ];

        $response = $this->postJson('/api/{entity}', $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('{table}', [
            // 'field' => 'value',
        ]);
    }

    public function test_update(): void
    {
        ${model} = {Entity}::factory()->create();

        $data = [
            // 'field' => 'updated value',
        ];

        $response = $this->putJson('/api/{entity}/'.${model}->id, $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('{table}', [
            // 'field' => 'updated value',
        ]);
    }

    public function test_destroy(): void
    {
        ${model} = {Entity}::factory()->create();

        $response = $this->deleteJson('/api/{entity}/'.${model}->id);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('{table}', ['id' => ${model}->id]);
    }

    public function test_search(): void
    {
        {Entity}::factory()->create(['field' => 'ABC123']);
        {Entity}::factory()->create(['field' => 'XYZ999']);

        $response = $this->getJson('/api/{entity}?search=ABC');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
```

### 4. Verify

Run `./vendor/bin/phpunit --filter {Entity}` to ensure tests pass.
