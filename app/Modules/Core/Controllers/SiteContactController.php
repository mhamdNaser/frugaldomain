<?php

namespace App\Modules\Core\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Mail\ContactMessageSubmitted;
use App\Modules\Core\Models\ContactMessage;
use App\Modules\Core\Requests\StoreContactMessageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SiteContactController extends Controller
{
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $message = ContactMessage::query()->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'subject' => $payload['subject'],
            'message' => $payload['message'],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $recipient = (string) env('CONTACT_RECEIVER_EMAIL', 'info@frugaldomain.online');

        try {
            Mail::to($recipient)->send(new ContactMessageSubmitted($message));

            $message->forceFill([
                'email_sent' => true,
                'email_sent_at' => now(),
                'email_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $message->forceFill([
                'email_sent' => false,
                'email_error' => $exception->getMessage(),
            ])->save();

            Log::error('Contact form email send failed.', [
                'contact_message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Your message has been received successfully.',
            'data' => [
                'id' => $message->id,
                'email_sent' => (bool) $message->email_sent,
            ],
        ], 201);
    }
}

