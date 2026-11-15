<?php

namespace App\Console\Commands;

use App\Models\Paciente;
use App\Services\PatientPhotoService;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PrivatizePatientPhotos extends Command
{
    protected $signature = 'app:privatize-photos';

    protected $description = 'Move referenced legacy patient photos out of public storage (run in maintenance mode)';

    public function handle(PatientPhotoService $photos): int
    {
        $failed = false;
        foreach (Paciente::whereNotNull('url')->lazyById() as $patient) {
            $patient->refresh();
            $path = $patient->url;
            if ($photos->exists($path)) {
                continue;
            }
            if (! preg_match('/\Afotos\/[a-z0-9_-]+\.(jpe?g|png|webp)\z/i', $path)) {
                $this->error('Ruta no válida para el paciente '.$patient->id.'. Revise el archivo manualmente.');
                $failed = true;

                continue;
            }
            $sources = collect(['public', 'local'])->filter(fn ($disk) => Storage::disk($disk)->exists($path));
            if ($sources->isEmpty()) {
                $this->error('Falta la fotografía del paciente '.$patient->id.'. Restáurela desde una copia privada.');
                $failed = true;

                continue;
            }
            $newPath = null;
            try {
                $source = Storage::disk($sources->first())->path($path);
                $size = getimagesize($source);
                if (! $size || max($size[0], $size[1]) > 3000 || filesize($source) > 4 * 1024 * 1024) {
                    throw new \RuntimeException('Unsupported image dimensions or size');
                }
                $newPath = $photos->store(new UploadedFile($source, basename($path), null, null, true));
                // Several old patients can refer to the same source image.
                DB::transaction(fn () => Paciente::where('url', $path)->update(['url' => $newPath]));
            } catch (Throwable $exception) {
                $photos->delete($newPath);
                $this->error('No se pudo convertir la fotografía del paciente '.$patient->id.'.');
                $failed = true;

                continue;
            }
            foreach ($sources as $disk) {
                Storage::disk($disk)->delete($path);
            }
            $this->info('Fotografía protegida para el paciente '.$patient->id.'.');
        }

        if (Storage::disk('public')->allFiles('fotos') !== []) {
            $this->error('Quedan fotografías sin referencia en storage/app/public/fotos. Archívelas fuera del directorio público antes de habilitar el sistema.');
            $failed = true;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
