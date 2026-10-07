<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['site_title', 'logo_path'])]
class SiteSetting extends Model
{
    public static function current(): self
    {
        return self::query()->first() ?? new self(['site_title' => 'SIPENDAS']);
    }
}
