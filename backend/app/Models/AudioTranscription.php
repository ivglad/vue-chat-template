<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AudioTranscription extends Model
{
    protected $fillable = ['audio_file', 'transcription'];
}
