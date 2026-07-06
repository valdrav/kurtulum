<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentTranslation extends Model
{
    protected $fillable = [
        'text_hash',
        'source_locale',
        'target_locale',
        'source_text',
        'translated_text',
    ];
}
