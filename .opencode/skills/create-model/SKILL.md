---
name: create-model
description: Generate a Laravel Model + Migration + Factory + Seeder following SIGDIP conventions
---

## What it does

Scaffolds the 4 backend files for a new entity in the SIGDIP project:
- `app/Models/{Entity}.php`
- `database/migrations/{timestamp}_create_{table}_table.php`
- `database/factories/{Entity}Factory.php`
- `database/seeders/{Entity}Seeder.php`

## Instructions

### 1. Ask the user for entity details

Ask the user for:
1. **Entity name** (Spanish, singular, PascalCase, e.g. `Categoria`)
2. **Table name** (Spanish, plural, snake_case, e.g. `categorias`)
3. **Fields** — for each field ask: name, column type, nullable?, default?, unique?, comment?
4. **Foreign keys** — for each FK ask: column name, related model class, related table, onDelete behavior
5. **Relations in the model** — for each relation ask: type (BelongsTo/HasMany/HasOne/HasManyThrough), method name, related model, foreign key, local key
6. **Fillable columns** — which fields go in `$fillable`
7. **Casts** — which fields need date/boolean/json casting
8. **Seed dependency** — which seeder(s) must run first (e.g. `ProductoresAndPrediosSeeder`)

### 2. Generate the files

#### Model (`app/Models/{Entity}.php`)

Follow the exact patterns from `app/Models/`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
// Import only the relation types actually used

class {Entity} extends Model
{
    use HasFactory;

    protected $table = '{table}';

    protected $fillable = [
        // listed fields (each on its own line, alphabetically if possible)
    ];

    protected $casts = [
        // 'field' => 'date' | 'boolean' | 'datetime' | 'array'
    ];

    // Relations with full PHPDoc block and return type
    /** ... */
    public function {relation}(): BelongsTo|HasMany|HasOne
    {
        // standard relation call
    }
}
```

Rules:
- Use `$table` property (the project always sets it explicitly)
- `$fillable` array, not `$guarded`
- `$casts` if there are any date/boolean fields
- Each relation method has a PHPDoc comment describing its purpose
- Relation methods must have a return type using the Illuminate relationship class
- Import only used relation classes at the top

#### Migration (`database/migrations/{timestamp}_create_{table}_table.php`)

Use the pattern from existing create-table migrations:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{table}', function (Blueprint $table) {
            $table->id();
            // columns...
            // foreign keys: $table->foreignId('{col}')->constrained('{table}')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{table}');
    }
};
```

Rules:
- `$table->enum('col', ['Option1', 'Option2'])` for enum fields (see `animales` migration)
- `$table->foreignId()->constrained()->onDelete('cascade')` for FKs
- `->comment('...')` for clarifying field purpose
- `$table->string('col')->unique()` for unique fields
- `->nullable()` for optional fields
- `->default(value)` for defaults
- Do NOT use `renameColumn` in create-table migrations (only in modify-table migrations)

#### Factory (`database/factories/{Entity}Factory.php`)

Follow the pattern from existing factories:

```php
<?php

namespace Database\Factories;

use App\Models\{RelatedModel};
use Illuminate\Database\Eloquent\Factories\Factory;

class {Entity}Factory extends Factory
{
    public function definition(): array
    {
        return [
            // 'field' => fake()->...,
            // FK fields: {model}::factory(),
        ];
    }
}
```

Rules:
- Use `fake()->` (not `$this->faker`)
- FK fields use `RelatedModel::factory()`
- Use `fake()->unique()->bothify('PATTERN-####')` for unique codes
- Use `fake()->randomElement([...])` for enums
- Use `fake()->optional()->sentence()` for nullable text fields
- Use `fake()->boolean()` for booleans
- Use `fake()->dateTimeBetween('now', '+1 month')` for future dates

#### Seeder (`database/seeders/{Entity}Seeder.php`)

Follow the pattern from `AnimalesSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\{Entity};
use App\Models\{Dependency};
use Illuminate\Database\Seeder;

class {Entity}Seeder extends Seeder
{
    public function run(): void
    {
        ${dependency_plural} = {Dependency}::all();

        if (${dependency_plural}->isEmpty()) {
            $this->command->error('No hay {dependency_plural} para asignar. Ejecuta {DependencySeeder} primero.');
            return;
        }

        for ($i = 1; $i <= {count}; $i++) {
            {Entity}::create([
                // fields with realistic data
            ]);
        }
    }
}
```

Rules:
- Validate dependencies first with `->isEmpty()` check and `command->error()`
- Show informative error with which seeder to run first
- Use `rand()` or `array_rand()` for random selections
- Create a reasonable number of records (ask user)

### 3. Register the seeder

After creating the seeder, update `database/seeders/DatabaseSeeder.php`:
- Add the seeder class to the `$this->call([...])` array in the correct position
- Add the use import at the top if needed

### 4. Verify

Run `./vendor/bin/pint` to check the generated code follows Laravel Pint standards.
