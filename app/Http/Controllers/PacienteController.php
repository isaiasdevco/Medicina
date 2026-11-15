<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePacienteRequest;
use App\Models\Medico;
use App\Models\Paciente;
use App\Services\PatientPhotoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class PacienteController extends Controller
{
    public function index()
    {
        $pacientes = Paciente::with('medicos')->latest('id')->paginate(15);

        return view('lista', compact('pacientes'));
    }

    public function create()
    {
        return view('form', ['datos' => Medico::orderBy('nombre')->get()]);
    }

    public function store(SavePacienteRequest $request, PatientPhotoService $photos)
    {
        $newPhoto = $request->hasFile('url') ? $photos->store($request->file('url')) : null;
        try {
            DB::transaction(function () use ($request, $newPhoto) {
                $paciente = Paciente::create($this->attributes($request) + ['url' => $newPhoto]);
                $paciente->medicos()->sync($request->validated('medico_id'));
            });
        } catch (Throwable $exception) {
            $photos->delete($newPhoto);
            throw $exception;
        }

        return redirect()->route('paciente.index')->with('success', 'Paciente registrado correctamente.');
    }

    public function edit(Paciente $paciente)
    {
        return view('form', [
            'paciente' => $paciente->load('medicos'),
            'datos' => Medico::orderBy('nombre')->get(),
        ]);
    }

    public function update(SavePacienteRequest $request, Paciente $paciente, PatientPhotoService $photos)
    {
        $previousPhoto = $paciente->url;
        $newPhoto = $request->hasFile('url') ? $photos->store($request->file('url')) : null;
        try {
            DB::transaction(function () use ($request, $paciente, $newPhoto) {
                $paciente->update($this->attributes($request) + ($newPhoto ? ['url' => $newPhoto] : []));
                $paciente->medicos()->sync($request->validated('medico_id'));
            });
        } catch (Throwable $exception) {
            $photos->delete($newPhoto);
            throw $exception;
        }
        if ($newPhoto !== null) {
            $photos->delete($previousPhoto);
        }

        return redirect()->route('paciente.index')->with('success', 'Paciente actualizado correctamente.');
    }

    public function destroy(Paciente $paciente, PatientPhotoService $photos)
    {
        $path = $paciente->url;
        DB::transaction(fn () => $paciente->delete());
        $photos->delete($path);

        return redirect()->route('paciente.index')->with('success', 'Paciente eliminado correctamente.');
    }

    public function photo(Paciente $paciente, PatientPhotoService $photos)
    {
        abort_unless($photos->exists($paciente->url), 404);

        return Storage::disk('patients')->response($paciente->url, 'fotografia.jpg', ['Content-Type' => 'image/jpeg']);
    }

    public function pdf(Paciente $paciente, PatientPhotoService $photos)
    {
        $paciente->load('medicos');
        $photoDataUri = $photos->dataUri($paciente->url);

        return Pdf::loadView('pdfPaciente', compact('paciente', 'photoDataUri'))
            ->setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultFont' => 'DejaVu Sans'])
            ->download('Paciente_'.$paciente->id.'.pdf');
    }

    public function qr(Paciente $paciente)
    {
        // The QR contains a protected URL, never clinical details or a bypass token.
        $qr = QrCode::format('svg')->size(200)->generate(route('paciente.pdf', $paciente));

        return response($qr)->header('Content-Type', 'image/svg+xml');
    }

    private function attributes(SavePacienteRequest $request): array
    {
        return Arr::only($request->validated(), ['nombre', 'apellido', 'fecha_nacimiento', 'enfermedades']) + [
            'alergias' => collect($request->validated('alergia', []))->filter()->map(fn ($item) => trim($item))->filter()->implode(', ') ?: null,
        ];
    }
}
