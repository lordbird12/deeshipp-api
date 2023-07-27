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

    public function SendMessageFromLiveToUser($pageId, $token, $commentId, $text)
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
            // dd($response->body());
            throw new LogicException($response->body());
        }
    }

    public function SendPrivateMessageToUser($pageId, $token, $id, $text)
    {

        $url = "https://graph.facebook.com/v16.0/" . $pageId . "/messages";

        $recipient = new stdClass();
        $recipient->id = $id;

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
