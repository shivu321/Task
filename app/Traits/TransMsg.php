<?php

namespace App\Traits;

trait TransMsg
{
    public function transMsg($message, $attributes = [])
    {
        if (strpos($message, "messages.") !== false) {
            $message = str_replace("messages.", "", $message);
        }
        return trans("messages." . $message, $attributes);
    }
}
