<?php

namespace Tests\Feature;

use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClinicTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_staff' => true])->save();

        return $user;
    }

    private function doctor(string $name = 'Médico ficticio'): Medico
    {
        return Medico::create(['nombre' => $name, 'especialidad' => 'Medicina general']);
    }

    private function patient(array $attributes = []): Paciente
    {
        $patient = Paciente::create($attributes + [
            'nombre' => 'Paciente de prueba', 'apellido' => 'Sintético',
            'fecha_nacimiento' => '1995-06-15', 'enfermedades' => 'Datos ficticios',
        ]);
        $patient->medicos()->attach($this->doctor());

        return $patient;
    }

    private function payload(array $attributes = []): array
    {
        return $attributes + [
            'nombre' => 'Paciente ficticio', 'apellido' => 'Prueba',
            'fecha_nacimiento' => '1990-01-01', 'enfermedades' => 'Sin diagnóstico real',
            'medico_id' => [$this->doctor()->id],
        ];
    }

    public static function clinicalRoutes(): array
    {
        return [
            ['GET', '/lista'], ['GET', '/form'], ['POST', '/form'],
            ['GET', '/form/1/edit'], ['PUT', '/form/1'], ['DELETE', '/form/1'],
            ['GET', '/paciente/1/foto'], ['GET', '/paciente/pdf/1'], ['GET', '/lista/qr/1'],
            ['GET', '/listaMedico'], ['GET', '/formMedico'], ['POST', '/formMedico'],
            ['GET', '/formMedico/1/edit'], ['PUT', '/formMedico/1'], ['DELETE', '/formMedico/1'],
        ];
    }

    #[DataProvider('clinicalRoutes')]
    public function test_guests_cannot_access_clinical_routes(string $method, string $url): void
    {
        $this->call($method, $url)->assertRedirect(route('login'));
    }

    #[DataProvider('clinicalRoutes')]
    public function test_registered_users_without_staff_permission_are_forbidden(string $method, string $url): void
    {
        $this->patient();
        $this->actingAs(User::factory()->create())->call($method, $url)->assertForbidden();
    }

    public function test_staff_can_render_all_existing_management_forms_and_lists(): void
    {
        $patient = $this->patient();
        $doctor = $patient->medicos->first();
        $this->actingAs($this->staff());
        foreach (['/lista', '/listaMedico', '/form', '/formMedico', '/home', "/form/{$patient->id}/edit", "/formMedico/{$doctor->id}/edit"] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/lista')->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_patient_creation_stores_every_selected_doctor_and_optional_allergies(): void
    {
        $doctors = [$this->doctor()->id, $this->doctor('Segundo médico ficticio')->id];
        $this->actingAs($this->staff())->post('/form', $this->payload(['medico_id' => $doctors]))
            ->assertSessionHasNoErrors()->assertRedirect(route('paciente.index'));
        $patient = Paciente::firstOrFail();
        $this->assertEqualsCanonicalizing($doctors, $patient->medicos->modelKeys());
        $this->assertNull($patient->alergias);
        $this->assertNull($patient->url);
    }

    public static function invalidPatientData(): array
    {
        return [
            [['nombre' => ''], 'nombre'],
            [['nombre' => str_repeat('a', 256)], 'nombre'],
            [['enfermedades' => str_repeat('a', 256)], 'enfermedades'],
            [['fecha_nacimiento' => '2999-12-31'], 'fecha_nacimiento'],
            [['fecha_nacimiento' => 'not-a-date'], 'fecha_nacimiento'],
            [['medico_id' => []], 'medico_id'],
            [['medico_id' => [999999]], 'medico_id.0'],
            [['medico_id' => '1'], 'medico_id'],
            [['alergia' => [str_repeat('a', 101)]], 'alergia.0'],
            [['alergia' => 'invalid'], 'alergia'],
        ];
    }

    #[DataProvider('invalidPatientData')]
    public function test_invalid_patient_data_is_rejected_on_create_and_update(array $attributes, string $field): void
    {
        $patient = $this->patient();
        $this->actingAs($this->staff());
        $this->post('/form', $this->payload($attributes))->assertSessionHasErrors($field);
        $this->put('/form/'.$patient->id, $this->payload($attributes))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('pacientes', 1);
        $this->assertSame('Paciente de prueba', $patient->fresh()->nombre);
    }

    public function test_duplicate_doctor_assignments_are_rejected(): void
    {
        $id = $this->doctor()->id;
        $this->actingAs($this->staff())->post('/form', $this->payload(['medico_id' => [$id, $id]]))
            ->assertSessionHasErrors('medico_id.0');
        $this->assertDatabaseCount('pacientes', 0);
    }

    public function test_updating_patient_syncs_doctors_and_retains_then_replaces_private_photo(): void
    {
        Storage::fake('patients');
        $this->actingAs($this->staff());
        $this->post('/form', $this->payload(['url' => UploadedFile::fake()->image('ficticia.png')]))->assertSessionHasNoErrors();
        $patient = Paciente::firstOrFail();
        $previous = $patient->url;
        Storage::disk('patients')->assertExists($previous);
        $doctor = $this->doctor('Nueva asignación ficticia');
        $data = $this->payload(['medico_id' => [$doctor->id], 'alergia' => ['Polen', ' ']]);
        $this->put('/form/'.$patient->id, $data)->assertSessionHasNoErrors();
        $this->assertSame($previous, $patient->fresh()->url);
        $this->assertSame('Polen', $patient->fresh()->alergias);
        $this->assertEquals([$doctor->id], $patient->fresh()->medicos->modelKeys());
        $this->put('/form/'.$patient->id, $data + ['url' => UploadedFile::fake()->image('camara.jpg')])->assertSessionHasNoErrors();
        Storage::disk('patients')->assertMissing($previous);
        Storage::disk('patients')->assertExists($patient->fresh()->url);
    }

    public function test_photo_upload_is_private_and_jpeg_encoded(): void
    {
        Storage::fake('patients');
        $this->actingAs($this->staff())->post('/form', $this->payload(['url' => UploadedFile::fake()->image('foto.png')]))
            ->assertSessionHasNoErrors();
        $patient = Paciente::firstOrFail();
        $this->assertMatchesRegularExpression('/^fotos\/[a-f0-9-]{36}\.jpg$/', $patient->url);
        $this->assertSame('image/jpeg', Storage::disk('patients')->mimeType($patient->url));
        $this->get('/paciente/'.$patient->id.'/foto')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get('/storage/'.$patient->url)->assertNotFound();
    }

    public function test_non_images_svg_oversized_and_oversized_dimensions_are_rejected(): void
    {
        Storage::fake('patients');
        $this->actingAs($this->staff());
        foreach ([
            UploadedFile::fake()->create('script.php', 1, 'text/plain'),
            UploadedFile::fake()->createWithContent('foto.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            UploadedFile::fake()->image('big.jpg')->size(4097),
            UploadedFile::fake()->image('wide.png', 3001, 1),
        ] as $file) {
            $this->post('/form', $this->payload(['url' => $file]))->assertSessionHasErrors('url');
        }
        $this->assertDatabaseCount('pacientes', 0);
        $this->assertCount(0, Storage::disk('patients')->allFiles());
    }

    public function test_failed_database_transaction_cleans_up_new_photo(): void
    {
        Storage::fake('patients');
        // Validation passes, then a real database uniqueness failure aborts the pivot insert.
        DB::statement("CREATE TRIGGER fail_assignment BEFORE INSERT ON medico_paciente BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $response = $this->actingAs($this->staff())->post('/form', $this->payload(['url' => UploadedFile::fake()->image('photo.jpg')]));
        $response->assertStatus(500);
        $this->assertDatabaseCount('pacientes', 0);
        $this->assertCount(0, Storage::disk('patients')->allFiles());
    }

    public function test_deleting_patient_removes_photo_and_assignments_but_preserves_doctors(): void
    {
        Storage::fake('patients');
        $this->actingAs($this->staff())->post('/form', $this->payload(['url' => UploadedFile::fake()->image('photo.jpg')]));
        $patient = Paciente::firstOrFail();
        $doctor = $patient->medicos->first();
        $path = $patient->url;
        $this->delete('/form/'.$patient->id)->assertRedirect(route('paciente.index'));
        $this->assertDatabaseMissing('pacientes', ['id' => $patient->id]);
        $this->assertDatabaseHas('medicos', ['id' => $doctor->id]);
        $this->assertDatabaseCount('medico_paciente', 0);
        Storage::disk('patients')->assertMissing($path);
    }

    public function test_assigned_doctor_cannot_be_deleted_and_patient_is_preserved(): void
    {
        $patient = $this->patient();
        $doctor = $patient->medicos->first();
        $this->actingAs($this->staff())->delete('/formMedico/'.$doctor->id)->assertSessionHasErrors('medico');
        $this->assertDatabaseHas('pacientes', ['id' => $patient->id]);
        $this->assertDatabaseHas('medicos', ['id' => $doctor->id]);
    }

    public function test_foreign_key_protects_assigned_doctor_even_without_controller(): void
    {
        $patient = $this->patient();
        $this->expectException(QueryException::class);
        $patient->medicos->first()->delete();
    }

    public function test_doctor_crud_and_validation(): void
    {
        $this->actingAs($this->staff())->post('/formMedico', [])->assertSessionHasErrors(['nombre', 'especialidad']);
        $this->post('/formMedico', ['nombre' => 'Médico de prueba', 'especialidad' => 'General'])->assertSessionHasNoErrors();
        $doctor = Medico::firstOrFail();
        $this->put('/formMedico/'.$doctor->id, ['nombre' => 'Médico actualizado', 'especialidad' => 'Pediatría'])->assertSessionHasNoErrors();
        $this->assertSame('Pediatría', $doctor->fresh()->especialidad);
        $this->delete('/formMedico/'.$doctor->id)->assertRedirect(route('medico.index'));
        $this->assertDatabaseCount('medicos', 0);
    }

    public function test_missing_records_return_404_without_null_property_errors(): void
    {
        $this->actingAs($this->staff());
        foreach (['/form/999/edit', '/formMedico/999/edit', '/paciente/pdf/999', '/lista/qr/999', '/paciente/999/foto'] as $url) {
            $this->get($url)->assertNotFound();
        }
        $this->delete('/form/999')->assertNotFound();
        $this->delete('/formMedico/999')->assertNotFound();
    }

    public function test_pdf_contains_embedded_photo_and_works_without_photo_or_allergies(): void
    {
        Storage::fake('patients');
        $this->actingAs($this->staff())->post('/form', $this->payload(['url' => UploadedFile::fake()->image('photo.jpg')]));
        $patient = Paciente::firstOrFail();
        $response = $this->get('/paciente/pdf/'.$patient->id)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('/Subtype /Image', $response->getContent());
        $this->assertStringContainsString(Storage::disk('patients')->get($patient->url), $response->getContent());
        $withoutPhoto = $this->patient();
        $this->get('/paciente/pdf/'.$withoutPhoto->id)->assertOk();
    }

    public function test_qr_is_valid_svg_and_the_destination_remains_protected(): void
    {
        $patient = $this->patient();
        $response = $this->actingAs($this->staff())->get('/lista/qr/'.$patient->id)
            ->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertSame('svg', simplexml_load_string($response->getContent())->getName());
        $this->assertStringNotContainsString($patient->nombre, $response->getContent());
        auth()->logout();
        $this->get(route('paciente.pdf', $patient))->assertRedirect(route('login'));
    }

    public function test_unexpected_photo_path_is_never_served_or_read_into_pdf(): void
    {
        $patient = $this->patient(['url' => '../../.env']);
        $this->actingAs($this->staff())->get('/paciente/'.$patient->id.'/foto')->assertNotFound();
        $this->get('/paciente/pdf/'.$patient->id)->assertOk();
    }
}
