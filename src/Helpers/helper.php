<?php


if (!function_exists('sse_notify')) {
    /**
     * @param $message
     * @param string $type : alert, success, error, warning, info
     * @param string $event : Type of event such as "EmailSent", "UserLoggedIn", etc
     * @param array $user_ids
     * @return mixed
     */
    function sse_notify($message = null, $type = 'notification', $event = 'message', $user_ids = null)
    {
        return app('SSE')->notify(message: $message, type: $type, event: $event, user_ids: $user_ids);
    }
}
