<?php

namespace App\Events;

use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\UserData;
use FacebookAds\Object\ServerSide\CustomData;

class ViewList extends Event
{
    public static function create()
    {
        return (new self())
            ->setEventName('ViewList')
            ->setEventTime(time())
            ->setUserData(new UserData())
            ->setCustomData(new CustomData())
            ->setEventId((string) \Illuminate\Support\Str::uuid());
    }
} 