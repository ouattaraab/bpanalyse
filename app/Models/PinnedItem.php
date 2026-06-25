<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PinnedItem extends Model
{
    protected $fillable = [
        'explorer_session_id',
        'interaction_id',
        'note',
    ];

    /** @return BelongsTo<ExplorerSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ExplorerSession::class, 'explorer_session_id');
    }

    /** @return BelongsTo<Interaction, $this> */
    public function interaction(): BelongsTo
    {
        return $this->belongsTo(Interaction::class);
    }
}
