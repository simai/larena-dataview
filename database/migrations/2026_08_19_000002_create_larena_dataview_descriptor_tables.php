<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('larena_dataview_descriptors')) {
            Schema::create('larena_dataview_descriptors', static function (Blueprint $table): void {
                $table->string('scope_ref', 128);
                $table->string('view_id', 120);
                $table->unsignedBigInteger('current_revision');
                $table->text('current_json');
                $table->char('semantic_hash', 64);
                $table->string('updated_by', 160);
                $table->primary(['scope_ref', 'view_id']);
            });
        }
        if (! Schema::hasTable('larena_dataview_descriptor_versions')) {
            Schema::create('larena_dataview_descriptor_versions', static function (Blueprint $table): void {
                $table->string('scope_ref', 128);
                $table->string('view_id', 120);
                $table->unsignedBigInteger('revision');
                $table->text('document_json');
                $table->char('semantic_hash', 64);
                $table->string('changed_by', 160);
                $table->primary(['scope_ref', 'view_id', 'revision']);
            });
        }
    }

    public function down(): void
    {
        foreach (['larena_dataview_descriptor_versions', 'larena_dataview_descriptors'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('dataview_descriptor_rollback_refuses_nonempty_state');
            }
        }
        Schema::dropIfExists('larena_dataview_descriptor_versions');
        Schema::dropIfExists('larena_dataview_descriptors');
    }
};
