<?php

namespace Khairy\LaravelSSEStream\Models;

use Illuminate\Database\Eloquent\Model;

class SSELog extends Model
{
    protected $table = 'sse_logs';

    protected $fillable = [
        'user_id',
        'message',
        'event',
        'type',
        'delivered',
        'client'
    ];

    /**
     * Saves SSE event in database table.
     *
     * @param $message
     * @param $type
     * @param $event
     * @param $user_id
     * @return bool
     */
    public function saveEvent($message, $type, $event, $user_id = null): object
    {
        return $this->create([
            'user_id' => $user_id,
            'message' => $message,
            'event' => $event,
            'type' => $type,
        ]);
    }

    /**
     * Summary of scopeAuthenticated
     * @param mixed $query
     * @return void
     */
    public function scopeAuthenticated($query)
    {
        if (auth()->check()) {
            $query->where(function ($q) {
                $q->where('user_id', auth()->id())
                    ->orWhereNull('user_id');
            });
        } else {
            $query->whereNull('user_id');
        }
    }

    /**
     * Summary of scopeDelivered
     * @param mixed $query
     * @return void
     */
    public function scopeNotDelivered($query)
    {
        $query->where('delivered', '0');
    }
}
