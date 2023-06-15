<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use stdClass;
use LogicException;

class FacebookApi
{
    public function __construct()
    {
    }

    public function SendPrivateMessageToUser($pageId, $token, $commentId, $text)
    {

        $url = "https://graph.facebook.com/" . $pageId . "/messages";

        $recipient = new stdClass();
        $recipient->comment_id = $commentId;

        $message = new stdClass();
        $message->text = $text;

        $response = Http::post($url, [
            "recipient" => json_encode($recipient),
            "message" => json_encode($message),
            "message_type" => "RESPONSE",
            "access_token" => $token,
        ]);

        if ($response->successful()) {
            return $response->object();
        } else {
            throw new LogicException($response->body());
        }
    }
}
