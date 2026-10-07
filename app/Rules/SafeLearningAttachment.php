<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class SafeLearningAttachment implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Lampiran tidak dapat dibaca.');

            return;
        }

        $name = $value->getClientOriginalName();
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $types = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        if (strlen($name) > 200 || preg_match('/[\x00-\x1f\/\\\\]/', $name)
            || preg_match('/\.(php\d*|phtml|phar|exe|sh|bat|js|html|svg)(\.|$)/i', $name)
            || ! isset($types[$extension]) || $value->getMimeType() !== $types[$extension]) {
            $fail('Lampiran harus berupa PDF, JPG, JPEG, atau PNG yang valid.');

            return;
        }

        $handle = fopen($value->getRealPath(), 'rb');
        if ($handle === false) {
            $fail('Lampiran tidak dapat dibaca.');

            return;
        }
        try {
            $signature = fread($handle, 8);
        } finally {
            fclose($handle);
        }
        $valid = match ($extension) {
            'pdf' => str_starts_with($signature, '%PDF-'),
            'png' => $signature === "\x89PNG\r\n\x1a\n" && @getimagesize($value->getRealPath()) !== false,
            default => str_starts_with($signature, "\xff\xd8\xff") && @getimagesize($value->getRealPath()) !== false,
        };
        if (! $valid) {
            $fail('Isi lampiran tidak sesuai dengan jenis file yang diperbolehkan.');
        }
    }
}
