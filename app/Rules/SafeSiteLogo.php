<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SafeSiteLogo implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Logo tidak dapat dibaca.');

            return;
        }

        $name = $value->getClientOriginalName();
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        if (strlen($name) > 200 || preg_match('/[\x00-\x1f\/\\\\]/', $name)
            || preg_match('/\.(php\d*|phtml|phar|exe|sh|bat|js|html|svg)(\.|$)/i', $name)
            || ! isset($types[$extension]) || $value->getMimeType() !== $types[$extension]) {
            $fail('Logo harus berupa JPG, JPEG, atau PNG yang valid.');

            return;
        }

        $image = @getimagesize($value->getRealPath());
        $signature = @file_get_contents($value->getRealPath(), false, null, 0, 8);
        $validSignature = $extension === 'png'
            ? $signature === "\x89PNG\r\n\x1a\n"
            : is_string($signature) && str_starts_with($signature, "\xff\xd8\xff");
        if ($image === false || ! $validSignature || $image[0] > 4096 || $image[1] > 4096) {
            $fail('Isi atau ukuran dimensi logo tidak valid. Maksimal 4096 × 4096 piksel.');
        }
    }
}
