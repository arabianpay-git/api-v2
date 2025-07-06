<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait LogsModelActions
{
    public function logModelAction(string $event, string $description, array $properties = [])
    {
        activity($this->getTable())
            ->performedOn($this)
            ->causedBy(Auth::user())
            ->withProperties($properties)
            ->event($event)
            ->log($description);
    }

    public static function generateBatchUuid(): string
    {
        return (string) Str::uuid();
    }
}
