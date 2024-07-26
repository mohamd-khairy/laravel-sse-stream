<?php

namespace Khairy\LaravelSSEStream\Classes;

use Khairy\LaravelSSEStream\Models\SSELog;

class SSE
{
    /**
     * @var SSELog
     */
    protected $SSELog;

    public function __construct(SSELog $SSELog)
    {
        $this->SSELog = $SSELog;
    }

    /**
     * Notify SSE event.
     *
     * @param string $message : notification message
     * @param string $type : alert, success, error, warning, info
     * @param string $event : Type of event such as "EmailSent", "UserLoggedIn", etc
     * @param array $user_ids
     * @return bool
     */
    public function notify($message, $type = 'notification', $event = 'message', $user_ids = null): bool
    {
        $user_ids = $user_ids ? (is_array($user_ids) ? $user_ids : json_decode($user_ids)) : [];

        if (!config('sse.append_user_id') || count($user_ids ?? []) <= 0) {
            $this->SSELog->saveEvent($message, $type, $event);
        }

        if (count($user_ids ?? []) > 0) {
            foreach ($user_ids as $user_id) {
                $this->SSELog->saveEvent($message, $type, $event, $user_id);
            }
        }

        return true;
    }
}
