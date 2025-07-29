<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Pgvector\Laravel\HasNeighbors;
use Pgvector\Laravel\Vector;

class DocumentEmbedding extends Model
{
    use HasFactory, HasNeighbors;

    protected $fillable = [
        'document_id',
        'chunk_text',
        'chunk_index',
        'embedding',
        'embedding_vector',
    ];

    protected $casts = [
        'embedding' => 'array',
        'embedding_vector' => Vector::class,
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
