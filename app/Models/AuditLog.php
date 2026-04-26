<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'action', 'entity_type', 'entity_id', 'changes', 'ip_address'])]
#[WithoutTimestamps]
class AuditLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'changes'    => 'array',
            'created_at' => 'datetime',
        ];
    }
}