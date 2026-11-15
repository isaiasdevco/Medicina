<?php

namespace Tests\Feature;

use App\Models\Paciente;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyUpgradeTest extends TestCase
{
    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_upgrade_preserves_existing_patients_and_their_doctor(): void
    {
        $this->artisan('migrate:fresh', ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/2025_01_23_194841_create_medicos_table.php',
            'database/migrations/2025_01_24_180133_create_pacientes_table.php',
        ], '--force' => true])->assertSuccessful();
        $doctorId = DB::table('medicos')->insertGetId(['nombre' => 'Médico ficticio anterior', 'especialidad' => 'General']);
        $patientId = DB::table('pacientes')->insertGetId([
            'nombre' => 'Paciente ficticio anterior', 'apellido' => 'Prueba',
            'fecha_nacimiento' => '2000-01-01', 'enfermedades' => 'Datos de prueba',
            'alergias' => 'Polen', 'medico_id' => $doctorId,
        ]);
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $patient = Paciente::findOrFail($patientId);
        $this->assertSame('Paciente ficticio anterior', $patient->nombre);
        $this->assertSame('Polen', $patient->alergias);
        $this->assertEquals([$doctorId], $patient->medicos->modelKeys());
        $this->expectException(\RuntimeException::class);
        (require database_path('migrations/2026_11_15_000002_preserve_patient_medical_relationships.php'))->down();
    }

    public function test_legacy_public_and_private_photos_move_to_private_disk_without_losing_references(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        Storage::fake('patients');
        Storage::fake('public');
        Storage::fake('local');
        $image = UploadedFile::fake()->image('old.png')->get();
        Storage::disk('public')->put('fotos/old.png', $image);
        Storage::disk('local')->put('fotos/old.png', $image);
        $attributes = ['nombre' => 'Prueba', 'apellido' => 'Sintético', 'fecha_nacimiento' => '2000-01-01', 'enfermedades' => 'Ficticio', 'url' => 'fotos/old.png'];
        $first = Paciente::create($attributes);
        $second = Paciente::create($attributes);
        $this->artisan('app:privatize-photos')->assertSuccessful();
        $this->assertSame($first->fresh()->url, $second->fresh()->url);
        Storage::disk('patients')->assertExists($first->fresh()->url);
        Storage::disk('public')->assertMissing('fotos/old.png');
        Storage::disk('local')->assertMissing('fotos/old.png');
        $this->artisan('app:privatize-photos')->assertSuccessful();
        $this->assertCount(1, Storage::disk('patients')->allFiles());
    }

    public function test_photo_migration_reports_missing_files_without_erasing_the_reference(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        Storage::fake('patients');
        Storage::fake('public');
        Storage::fake('local');
        $patient = Paciente::create(['nombre' => 'Prueba', 'apellido' => 'Sintético', 'fecha_nacimiento' => '2000-01-01', 'enfermedades' => 'Ficticio', 'url' => 'fotos/missing.jpg']);
        $this->artisan('app:privatize-photos')->assertFailed();
        $this->assertSame('fotos/missing.jpg', $patient->fresh()->url);
    }

    public function test_photo_migration_reports_unreferenced_public_photos_without_deleting_them(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        Storage::fake('public');
        Storage::disk('public')->put('fotos/orphan.jpg', UploadedFile::fake()->image('orphan.jpg')->get());
        $this->artisan('app:privatize-photos')->assertFailed();
        Storage::disk('public')->assertExists('fotos/orphan.jpg');
    }
}
