<?php

namespace App\Http\Controllers\Api;

use App\Enums\WebhookSourceEnums;
use App\Http\Controllers\CloudbedsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\GhlController;
use App\Http\Requests\CloudbedsWebhookRequest;
use App\Http\Requests\GhlWebhookRequest;
use App\Models\UserToken;
use App\Models\Webhook;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WebhookController extends Controller
{
    /**
     * Handle Ai face swap API webhook response
     */
    public function handleGhlResponse(GhlWebhookRequest $request)
    {
        $webhook = Webhook::create([
            'source'   => WebhookSourceEnums::GHL,
            'response' => json_encode($request->all()),
        ]);
        $data = [
            'guestPhone' => $request->get('phone'),
            'guestEmail' => $request->get('email')
        ];
        $updated = CloudbedsController::updateCloudbedsGuest($request->get('Property ID'), $request->get('Guest ID'), $data);
        if ($updated) {
            $webhook->processed = true; // Set the 'processed' column to true
            $webhook->save();
            return response()->json('Cloudbeds Guest Updated!', 200);
        }
        return response()->json('Cloudbeds Not Updated!', 200);
    }
    /**
     * Handle Ai face swap API webhook response
     */
    public function handleCloudbedsResponse(CloudbedsWebhookRequest $request)
    {
        $webhook = Webhook::create([
            'source'   => WebhookSourceEnums::CLOUDBEDS,
            'response' => json_encode($request->all()),
            'processed' => false
        ]);

        if ($request->event = WebhookSourceEnums::RESERVATION_CREATED) {
            $reservation = CloudbedsController::getReservation($request->propertyID, $request->reservationID);
            if ($reservation['success']) {
                $reservation = $reservation['data'];
                $opportunity = GhlController::createOpportunity($reservation);
                if ($opportunity['status'] == true) {
                    $webhook->processed = true;
                    $webhook->save();
                    return response()->json('Cloudbeds Webhook Processed!', 200);
                } else {
                    return response()->json(json_encode($opportunity), 500);
                }
            }
        }
        return response()->json('Cloudbeds Webhook Captured!', 200);
    }
}
