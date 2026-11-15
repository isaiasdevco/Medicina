<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PatientPhotoService
{
    public function store(UploadedFile $file): string
    {
        $image = @imagecreatefromstring($file->get());
        if ($image === false) {
            throw ValidationException::withMessages(['url' => 'La fotografía no es una imagen válida.']);
        }

        try {
            // Re-encode pixels: do not retain filenames or EXIF metadata.
            ob_start();
            imagejpeg($image, null, 85);
            $contents = ob_get_clean();
        } finally {
            imagedestroy($image);
        }

        $path = 'fotos/'.Str::uuid().'.jpg';
        Storage::disk('patients')->put($path, $contents);

        return $path;
    }

    public function exists(?string $path): bool
    {
        return is_string($path) && preg_match('/\Afotos\/[a-f0-9-]{36}\.jpg\z/', $path) === 1
            && Storage::disk('patients')->exists($path);
    }

    public function delete(?string $path): void
    {
        if ($this->exists($path)) {
            Storage::disk('patients')->delete($path);
        }
    }

    public function dataUri(?string $path): ?string
    {
        return $this->exists($path)
            ? 'data:image/jpeg;base64,'.base64_encode(Storage::disk('patients')->get($path))
            : null;
    }
}
