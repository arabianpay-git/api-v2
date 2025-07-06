<?php

namespace App\Traits;

use Illuminate\Support\Facades\Mail;

trait EmailSender
{
    /**
     * Send a simple raw email.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return void
     */
    protected function sendEmail($view, $to, $subject, $data = [])
    {
        Mail::send($view, $data, function ($message) use ($to, $subject) {
            $message->to($to)
                ->subject($subject);
        });
    }
}
