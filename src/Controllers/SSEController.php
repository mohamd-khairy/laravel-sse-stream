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
     * Notifies SSE events.
     *
     * @param SSELog $SSELog
     * @return StreamedResponse
     * @throws \Exception
     */
    public function stream(SSELog $SSELog): StreamedResponse
    {
        $response = new StreamedResponse();

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        // delete expired/old
        $this->deleteOld();

        $response->setCallback(function () use ($SSELog) {

            // if the connection has been closed by the client we better exit the loop
            if (connection_aborted()) {
                return;
            }

            $models = $SSELog->notDelivered()->authenticated()->oldest()->get();

            echo ':' . str_repeat(' ', 1048) . "\n"; // 2 kB padding for IE
            echo "retry: 5000\n";

            foreach ($models as $model) {
                if (!$model) {
                    // no new data to send
                    echo ": heartbeat\n\n";
                } else {

                    $clientId = $this->getClientId();

                    // check if we have notified this client
                    $clientModel = $SSELog
                        ->where('message', $model->message)
                        ->where('client', $clientId)
                        ->first();

                    if ($clientModel) {
                        // no new data to send
                        echo ": heartbeat\n\n";
                    } else {

                        $data = json_encode([
                            'message' => $model->message,
                            'type' => strtolower($model->type),
                            'time' => date('H:i:s A', strtotime($model->created_at)),
                        ]);

                        echo 'id: ' . $model->id . "\n";
                        echo 'event: ' . $model->event . "\n";
                        echo 'data: ' . $data . "\n\n";

                        // $model->update(['delivered' => '1']);

                        $SSELog->create([
                            'user_id' => $model->user_id,
                            'message' => $model->message,
                            'event' => $model->event,
                            'type' => $model->type,
                            'client' => $clientId,
                            'delivered' => '1'
                        ]);
                    }
                }

                ob_flush();
                flush();

                sleep(config('sse.interval'));
            }
        });

        return $response->send();
    }

    /**
     * Tries to identify different SSE connections
     *
     * @return string
     */
    protected function getClientId(): string
    {
        return md5(php_uname('n') . $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
    }

    /**
     * @param SSELog $SSELog
     * @throws \Exception
     */
    public function deleteOld()
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
}
