<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Upload extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'file_path',
        'file_type',
        'extracted_text',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
