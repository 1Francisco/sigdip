<?php

namespace Tests\Feature\Api;

use App\Models\Animal;
use App\Models\Predio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Administrador']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
        Sanctum::actingAs($this->admin);
    }

    public function test_paginacion_segunda_pagina()
    {
        $predio = Predio::factory()->create();
        Animal::factory()->count(15)->create(['predio_id' => $predio->id]);

        $page1 = $this->getJson('/api/animales?perPage=5&page=1');
        $page2 = $this->getJson('/api/animales?perPage=5&page=2');

        $page1->assertStatus(200);
        $page2->assertStatus(200);
        $this->assertLessThanOrEqual(5, count($page1->json('data')));
        $this->assertLessThanOrEqual(5, count($page2->json('data')));
        $this->assertNotEquals(
            $page1->json('data.0.id'),
            $page2->json('data.0.id')
        );
    }

    public function test_paginacion_pagina_vacia()
    {
        $predio = Predio::factory()->create();
        Animal::factory()->count(3)->create(['predio_id' => $predio->id]);

        $response = $this->getJson('/api/animales?perPage=5&page=999');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
        $this->assertEquals(999, $response->json('pagination.current_page'));
    }

    public function test_paginacion_per_page_personalizado()
    {
        $predio = Predio::factory()->create();
        Animal::factory()->count(10)->create(['predio_id' => $predio->id]);

        $response = $this->getJson('/api/animales?perPage=3');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(3, count($response->json('data')));
        $this->assertEquals(3, $response->json('pagination.per_page'));
    }

    public function test_paginacion_estructura_completa()
    {
        $predio = Predio::factory()->create();
        Animal::factory()->count(7)->create(['predio_id' => $predio->id]);

        $response = $this->getJson('/api/animales?perPage=5&page=1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'pagination' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
        $this->assertEquals(1, $response->json('pagination.current_page'));
        $this->assertEquals(2, $response->json('pagination.last_page'));
        $this->assertEquals(5, $response->json('pagination.per_page'));
        $this->assertEquals(7, $response->json('pagination.total'));
    }
}
