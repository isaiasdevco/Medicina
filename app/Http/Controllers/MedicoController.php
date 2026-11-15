<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    public function index()
    {
        return view('listaMedico', ['medicos' => Medico::withCount('pacientes')->orderBy('nombre')->paginate(15)]);
    }

    public function create()
    {
        return view('formMedico');
    }

    public function store(Request $request)
    {
        Medico::create($this->validated($request));

        return redirect()->route('medico.index')->with('success', 'Médico registrado correctamente.');
    }

    public function edit(Medico $medico)
    {
        return view('formMedico', compact('medico'));
    }

    public function update(Request $request, Medico $medico)
    {
        $medico->update($this->validated($request));

        return redirect()->route('medico.index')->with('success', 'Médico actualizado correctamente.');
    }

    public function destroy(Medico $medico)
    {
        if ($medico->pacientes()->exists()) {
            return $this->assignedDoctorResponse();
        }
        try {
            $medico->delete();
        } catch (QueryException $exception) {
            // The foreign key also protects against concurrent assignments.
            if (in_array((string) $exception->getCode(), ['23000', '23503'], true)) {
                return $this->assignedDoctorResponse();
            }
            throw $exception;
        }

        return redirect()->route('medico.index')->with('success', 'Médico eliminado correctamente.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'especialidad' => ['required', 'string', 'max:255'],
        ]);
    }

    private function assignedDoctorResponse()
    {
        return redirect()->route('medico.index')->withErrors([
            'medico' => 'No se puede eliminar un médico con pacientes asignados. Reasigne sus pacientes primero.',
        ]);
    }
}
