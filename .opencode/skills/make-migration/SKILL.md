---
name: make-migration
description: Generate a Laravel migration with automatic SQLite/MySQL driver branching for renameColumn
---

## What it does

Creates a migration file in `database/migrations/` that handles the SQLite vs MySQL driver branching pattern used in the project.

## Instructions

### 1. Ask the user

1. **Migration description** (e.g. `add_telefono_to_productores_table`)
2. **Table name** to modify
3. **Operations** — for each operation:
   - Type: `add_column`, `rename_column`, `drop_column`, `modify_column`
   - Column name, type, modifiers (nullable, default, after, unique, comment)
   - For `rename_column`: old name → new name

### 2. Generate the migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // rename_column operations need driver branching
        if (DB::connection($this->getConnection())->getDriverName() === 'sqlite') {
            Schema::table('{table}', function (Blueprint $table) {
                $table->renameColumn('{old}', '{new}');
            });
        } else {
            DB::statement('ALTER TABLE {table} CHANGE {old} {new} {type} NULL');
        }

        // add_column operations (no branching needed)
        Schema::table('{table}', function (Blueprint $table) {
            $table->{type}('{column}')->nullable()->after('{after}');
            // or: $table->string('column', 100)->default('value');
            // or: $table->foreignId('fk')->constrained('table')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Reverse in opposite order

        // drop added columns
        Schema::table('{table}', function (Blueprint $table) {
            $table->dropColumn('{column}');
        });

        // rename back (with driver branching)
        if (DB::connection($this->getConnection())->getDriverName() === 'sqlite') {
            Schema::table('{table}', function (Blueprint $table) {
                $table->renameColumn('{new}', '{old}');
            });
        } else {
            DB::statement('ALTER TABLE {table} CHANGE {new} {old} {type} NULL');
        }
    }
};
```

### 3. Rules

- `renameColumn` MUST be wrapped in `DB::connection()->getDriverName() === 'sqlite'` check
- MySQL/MariaDB branch uses `DB::statement('ALTER TABLE ... CHANGE ...')`
- `add_column` and `drop_column` need NO branching
- Always implement `down()` reversing operations in opposite order
- File naming: `{timestamp}_{description}.php`
- Remind user that `renameColumn` requires `doctrine/dbal` (already in composer.json)
