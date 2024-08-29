<?php

namespace Khairy\LaravelSSEStream\Controllers;

use DateTime;
use Illuminate\Routing\Controller;
use Khairy\LaravelSSEStream\Models\SSELog;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SSEController extends Controller
{
    /**
     * Summary of __construct
     */
    public function __construct()
    {
        if (config('sse.append_user_id')) {
            $this->middleware(config('sse.middleware_type', 'web'));
        }
    }

    /**
     * Summary of stream
     * @param \Khairy\LaravelSSEStream\Models\SSELog $SSELog
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function stream(SSELog $SSELog): StreamedResponse
    {
        $response = new StreamedResponse();

        $this->setHeaders($response);

        $this->deleteOldLogs();

        $response->setCallback(function () use ($SSELog) {
            $this->handleStream($SSELog);
        });

        return $response->send();
    }

    /**
     * Summary of setHeaders
     * @param \Symfony\Component\HttpFoundation\StreamedResponse $response
     * @return void
     */
    private function setHeaders(StreamedResponse $response): void
    {
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        $response->headers->set('Access-Control-Allow-Origin', config('sse.Access-Control-Allow-Origin')); // Adjust this to your frontend origin
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Summary of deleteOldLogs
     * @return void
     */
    private function deleteOldLogs(): void
    {
        $date = new DateTime;
        $date->modify('-' . (config('sse.interval') * 4) . ' seconds');

        // delete client-specific records
        if (!config('sse.keep_events_logs')) {
            SSELog::where('created_at', '<=', $date->format('Y-m-d H:i:s'))->where('delivered', '1')->whereNull('client')->delete();
        }

        if (!config('sse.keep_delivered_logs')) {
            SSELog::where('created_at', '<=', $date->format('Y-m-d H:i:s'))->where('delivered', '1')->delete();
        }

        // update actual message as delivered
        SSELog::where('created_at', '<=', $date->format('Y-m-d H:i:s'))->update(['delivered' => '1']);
    }

    /**
     * Summary of handleStream
     * @param \Khairy\LaravelSSEStream\Models\SSELog $SSELog
     * @return void
     */
    private function handleStream(SSELog $SSELog): void
    {
        $startTime = time();
        $maxDuration = config('sse.max_duration_request', 3600); // 1 hour in seconds

        while (true) {
            if (connection_aborted() || (time() - $startTime) >= $maxDuration) {
                break;
            }

            $models = $SSELog->notDelivered()->authenticated()->oldest()->get();

            if ($models->isEmpty()) {
                $this->sendHeartbeat();
            } else {
                $this->processModels($models, $SSELog);
            }

            $this->flushOutput();
        }

        // Send a final message to notify the client to reconnect
        echo "event: reconnect\n";
        echo "data: reconnecting\n\n";
        ob_flush();
        flush();
    }


    /**
     * Summary of sendHeartbeat
     * @return void
     */
    private function sendHeartbeat(): void
    {
        echo ':' . str_repeat(' ', 1048) . "\n"; // 1 kB padding for IE
        echo "retry: 5000\n"; // Retry every 5 seconds if the connection fails
        echo ": heartbeat\n\n";
    }

    /**
     * Summary of processModels
     * @param mixed $models
     * @param \Khairy\LaravelSSEStream\Models\SSELog $SSELog
     * @return void
     */
    private function processModels($models, SSELog $SSELog): void
    {
        foreach ($models as $model) {

            $clientId = $this->getClientId();

            if ($this->clientAlreadyNotified($SSELog, $model, $clientId)) {
                $this->sendHeartbeat();
            } else {
                $this->sendModelData($model);
                $this->markAsDelivered($SSELog, $model, $clientId);
            }
        }
    }

    /**
     * Summary of clientAlreadyNotified
     * @param \Khairy\LaravelSSEStream\Models\SSELog $SSELog
     * @param mixed $model
     * @param string $clientId
     * @return bool
     */
    private function clientAlreadyNotified(SSELog $SSELog, $model, string $clientId): bool
    {
        return $SSELog->where('message', $model->message)
            ->where('client', $clientId)
            ->exists();
    }

    /**
     * Summary of sendModelData
     * @param mixed $model
     * @return void
     */
    private function sendModelData($model): void
    {
        $data = json_encode([
            'message' => $model->message,
            'type' => strtolower($model->type),
            'time' => date('H:i:s A', strtotime($model->created_at)),
        ]);

        echo 'id: ' . $model->id . "\n";
        echo 'event: ' . $model->event . "\n";
        echo 'data: ' . $data . "\n\n";
    }

    /**
     * Summary of markAsDelivered
     * @param \Khairy\LaravelSSEStream\Models\SSELog $SSELog
     * @param mixed $model
     * @param string $clientId
     * @return void
     */
    private function markAsDelivered(SSELog $SSELog, $model, string $clientId): void
    {
        $SSELog->create([
            'user_id' => $model->user_id,
            'message' => $model->message,
            'event' => $model->event,
            'type' => $model->type,
            'client' => $clientId,
            'delivered' => '1'
        ]);
    }

    /**
     * Summary of flushOutput
     * @return void
     */
    private function flushOutput(): void
    {
        ob_flush();
        flush();

        sleep(config('sse.interval'));
    }

    /**
     * Tries to identify different SSE connections
     *
     * @return string
     */
    private function getClientId(): string
    {
        return md5(php_uname('n') . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
    }
}
