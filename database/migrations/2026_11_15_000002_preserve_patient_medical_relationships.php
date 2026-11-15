<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medico_paciente', function (Blueprint $table) {
            $table->foreignId('medico_id')->constrained()->restrictOnDelete();
            $table->foreignId('paciente_id')->constrained()->cascadeOnDelete();
            $table->primary(['medico_id', 'paciente_id']);
        });

        // Preserve existing assignments before replacing the single-doctor column.
        DB::table('pacientes')->select('id', 'medico_id')->orderBy('id')->chunkById(500, function ($patients) {
            $rows = $patients->filter(fn ($patient) => $patient->medico_id !== null)
                ->map(fn ($patient) => ['medico_id' => $patient->medico_id, 'paciente_id' => $patient->id])->all();
            if ($rows !== []) {
                DB::table('medico_paciente')->insert($rows);
            }
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('medico_id');
            $table->text('alergias')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Refuse a lossy downgrade of clinical data.
        if (DB::table('pacientes')->exists()) {
            throw new RuntimeException('Cannot revert patient relationships while patients exist. Restore a verified backup instead.');
        }

        Schema::dropIfExists('medico_paciente');
        Schema::table('pacientes', function (Blueprint $table) {
            $table->foreignId('medico_id')->constrained()->restrictOnDelete();
            $table->string('alergias')->nullable(false)->change();
        });
    }
};
