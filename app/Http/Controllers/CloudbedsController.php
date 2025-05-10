<?php

namespace App\Http\Controllers;

use App\Models\CloudbedsProperty;
use App\Models\GhlLocation;
use App\Models\PropertyLocationMap;
use App\Models\UserToken;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CloudbedsController extends Controller
{


    /**
     * Generate CB Auth Code
     */
    public function getOAuth($id)
    {
        $property = CloudbedsProperty::find($id);
        if (empty($property)) {
            return back()->with('error', 'Cloudbeds property not exist!');
        }
        $redirectUri = env('APP_URL') . '/auth-response';
        $scopes = implode(' ', config('cloudbeds.scopes'));
        $url = config('cloudbeds.base_url') . 'oauth' . "?response_type=" . config('cloudbeds.response_type') . "&redirect_uri=" . $redirectUri . "&client_id=" . $property->client_id . "&scope=" . $scopes;
        return redirect($url);
    }


    /**
     * Handle Cloudbeds auth callback
     */
    public function authResponse(Request $request)
    {
        $code = null;
        if ($request->has('code')) {
            $code  = $request->code;
        }
        $properties = CloudbedsProperty::all();
        return view('cb.auth-callback', compact('code', 'properties'));
    }




    // Redirect endpoint "SAMPLE"
    public static function generateToken($userToken)
    {
        $url = config('cloudbeds.base_url') . 'access_token';
        $redirectUri = env('APP_URL') . '/auth-response';
        $property = CloudbedsProperty::where('property_id', $userToken->property_id)->first();
        $params = [
            'client_id' => $property->client_id,
            'client_secret' => $property->client_secret,
            'grant_type' => 'authorization_code',
            'code' => $userToken->code,
            'redirect_uri' => $redirectUri,
        ];

        $response = Http::asForm()->post($url, $params);

        if ($response->successful()) {
            //Get Access Token Response
            $json = $response->json();
            //             ^ array:5 [▼
            //   "access_token" => "eyJraWQiOiJBTzJHNklIUTdacHMtR3dZc1hUQUpQWXkySFp2SWlvd3NZZ2cxLV9RbTBBIiwiYWxnIjoiUlMyNTYifQ.eyJ2ZXIiOjEsImp0aSI6IkFULlVPSzBUN3p1clpsdGZ6RjhGU2VrUE9RaEVKeGRaZUktW ▶"
            //   "refresh_token" => "wwKmO1DdGnU_DbE5z1vOV1unBuATdxA4S2HvQHQvJ3A"
            //   "token_type" => "Bearer"
            //   "expires_in" => 3599
            //   "resources" => array:1 [▼
            //     0 => array:2 [▼
            //       "type" => "property"
            //       "id" => "  "
            //     ]
            //   ]
            // ]
            return self::saveTokensToDatabase($userToken, $json['access_token'], $json['refresh_token']);
        }
        if ($response->failed()) {
            return self::apiFailedResponse($response);
        }
    }

    /**
     * List All Tokens
     */
    public function tokens()
    {
        $tokens = UserToken::all();
        return view('tokens', compact('tokens'));
    }

    /**
     * List All Tokens
     */
    public function updateToken($id, Request $request)
    {

        $validatedData = $request->validate([
            'access_token' => 'required|string',
        ]);
        $userToken = UserToken::find($id);
        if ($userToken) {
            $userToken->access_token = $request->access_token;
            $userToken->save();
            return back()->with('success', 'Token updated successfully!');
        }
        return back()->with('error', 'User Token Not Found');
    }

    /**
     * Refresh Go High Level API access token
     */
    public static function refreshToken($userToken)
    {
        if (empty($userToken->refresh_token)) {
            return null;
        }

        $url = config('cloudbeds.base_url') . 'access_token';
        $property = CloudbedsProperty::where('property_id', $userToken->property_id)->first();
        $params = [
            'client_id' => $property->client_id,
            'client_secret' => $property->client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $userToken->refresh_token,
        ];

        $response = Http::asForm()->post($url, $params);
        if ($response->successful()) {
            $json = $response->json();
            $saved =  self::saveTokensToDatabase($userToken, $json['access_token'], $json['refresh_token']);
            return [
                'status' => $saved,
            ];
        }
        if ($response->failed()) {
            return [
                'status' => false,
                'data' => self::apiFailedResponse($response)
            ];
        }
    }

    public static function apiFailedResponse($response)
    {
        return [
            'status' => $response->status(),
            'body' => $response->body(),
            'headers' => $response->headers(),
            'json' => $response->json(),
        ];
    }

    public function getGuest($propertyId, $id)
    {
        // $data = [
        //     'guestPhone' => 406800254
        // ];
        // return self::updateCloudbedsGuest($property_id, $id, $data);
        $propertyAccessToken = self::cloudbedsPropertyAccessToken($propertyId);

        if (!$propertyAccessToken['status']) {
            return $propertyAccessToken;
        }
        $userToken = $propertyAccessToken['token'];

        $url = config('cloudbeds.base_url') . 'getGuest';
        $response = Http::withHeaders([
            'Accept' => config('cloudbeds.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('cloudbeds.version'),
        ])->get($url, [
            'guestID' => $id
        ]);

        if ($response->status() == '401') {
            $refreshToken = self::refreshToken($userToken);
            if (empty($refreshToken)) {
                return "Failed To Genereate Token";
            } else {
                self::getGuest($propertyId, $id);
            }
        }

        if ($response->successful()) {
            $data = $response->json();
            if ($data['success']) {
                dd('Guest', $data['data']);
            } else {
                dd($data);
            }
        }

        if ($response->failed()) {
            $ca = self::apiFailedResponse($response);
            dd($ca);
        }
    }

    public static function cloudbedsPropertyAccessToken($property_id)
    {
        $userToken = UserToken::where('user_id', UserToken::$DEFAULT_USER_ID)
            ->where('type', UserToken::$CLOUDBEDS)
            ->where('property_id', $property_id)->first();

        if (empty($userToken)) {
            return [
                'status' => false,
                'message' => 'No User Token Found, Please Genereate Cloudbeds Auth Code For This Property.'
            ];
        } elseif ($userToken && empty($userToken->access_token)) {
            $userToken = self::generateToken($userToken);
        }
        return [
            'status' => isset($userToken->access_token) ? true : false,
            'token' => $userToken
        ];
    }

    /**
     * Update Cloudbeds Guest
     */
    public static function updateCloudbedsGuest($property_id, $guestId, $params = array(), $recursiveAction = false)
    {
        $payload = [
            'propertyID' => $property_id,
            'guestID' => $guestId
        ];
        $payload = array_merge($payload, $params);
        $propertyAccessToken = self::cloudbedsPropertyAccessToken($property_id);
        if (!$propertyAccessToken['status']) {
            return $propertyAccessToken['message'];
        }

        $response = Http::withHeaders([
            'Accept' => config('cloudbeds.accept'),
            'Authorization' => 'Bearer ' . $propertyAccessToken['token']->access_token,
            'Version' => config('cloudbeds.version'),
        ])->put(config('cloudbeds.base_url') . 'putGuest', $payload);

        if ($response->successful()) {
            $data = $response->json();
            if ($data['success']) {
                return true;
            }
        }

        if ($response->status() == '401') {
            $refreshToken = self::refreshToken($propertyAccessToken['token']);
            if (!empty($refreshToken) && !$recursiveAction) {
                self::updateCloudbedsGuest($property_id, $guestId, $params, true);
            }
        }
        if ($response->failed()) {
            return ['status' => false, 'data' => self::apiFailedResponse($response)];
        }
    }


    /**
     * Get Property Guest List
     */
    public function getGuestList($property_id)
    {
        $userToken = self::cloudbedsPropertyAccessToken($property_id);
        if (!$userToken['status']) {
            $msg = isset($userToken['message']) ? $userToken['message'] : 'Some Error';
            return back()->with('error', $msg);
        }
        $userToken = $userToken['token'];
        $url = config('cloudbeds.base_url') . 'getGuestList';
        $response = Http::withHeaders([
            'Accept' => config('cloudbeds.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('cloudbeds.version'),
        ])->get($url, [
            'pageNumber' => 1,
            'pageSize' => 10,
        ]);

        if ($response->status() == '401') {
            $refreshToken = self::refreshToken($userToken);
            if (empty($refreshToken)) {
                return "Failed To Genereate Token";
            } else {
                self::getGuestList($property_id);
            }
        }

        if ($response->successful()) {
            $data = $response->json();
            dd('Guest List', $data);
        }

        if ($response->failed()) {
            return self::apiFailedResponse($response);
        }
    }
    public function reservations($property_id)
    {

        $propertyAccessToken = self::cloudbedsPropertyAccessToken($property_id);
        if (!$propertyAccessToken['status']) {
            return $propertyAccessToken;
        }
        $userToken = $propertyAccessToken['token'];
        $url = config('cloudbeds.base_url') . 'getReservations';
        $response = Http::withHeaders([
            'Accept' => config('cloudbeds.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('cloudbeds.version'),
        ])->get($url, [
            'pageNumber' => 1,
            'pageSize' => 10,
        ]);

        if ($response->status() == '401') {
            $refreshToken = self::refreshToken($userToken);
            if (empty($refreshToken)) {
                return "Failed To Genereate Token";
            } else {
                self::reservations($property_id);
            }
        }

        if ($response->successful()) {
            $data = $response->json();
            dd('Reservations List', $data);
        }

        if ($response->failed()) {
            dd([
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
                'json' => $response->json(),
            ]);
        }
    }

    /**
     * Fetch cloudbeds reservation
     */
    public static function getReservation($propertyId, $reservationID)
    {
        $propertyAccessToken = self::cloudbedsPropertyAccessToken($propertyId);
        if (!$propertyAccessToken['status']) {
            return $propertyAccessToken;
        }

        $userToken = $propertyAccessToken['token'];
        $url = config('cloudbeds.base_url') . 'getReservation?reservationID=' . $reservationID . '&propertyID=' . $propertyId;
        $response = Http::withHeaders([
            'Accept' => config('cloudbeds.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('cloudbeds.version'),
        ])->get($url);
        if ($response->status() == '401') {
            $refreshToken = self::refreshToken($userToken);
            if (empty($refreshToken)) {
                return "Failed To generate Token";
            } else {
                self::getReservation($propertyId, $reservationID);
            }
        }

        if ($response->successful()) {
            return $response->json();
        }

        if ($response->failed()) {
            return [
                'success' => false,
                'data' => self::apiFailedResponse($response)
            ];
        }
    }

    // Token Save "SAMPLE"
    public static function saveTokensToDatabase($userToken, $accessToken, $refreshToken)
    {
        if ($userToken) {
            return UserToken::updateOrCreate(
                ['user_id' => $userToken->user_id, 'type' => UserToken::$CLOUDBEDS, 'property_id' => $userToken->property_id],
                [
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                ]
            );
        }
    }

    /**
     * Save Cloudbeds Property Auth Code
     */
    public function savePropertyAuthCode(Request $request)
    {
        $validatedData = $request->validate([
            'property_id' => 'required|integer',
            'code' => 'required|string',
        ]);

        $property = CloudbedsProperty::find($request->property_id);
        if (empty($property)) {
            return back()->with('error', 'Property Not Found!');
        }

        $saved = UserToken::updateOrCreate(
            ['user_id' => UserToken::$DEFAULT_USER_ID, 'property_id' => $property->property_id, 'type' => UserToken::$CLOUDBEDS],
            [
                'code' => $request->code,
            ]
        );

        if ($saved) {
            return redirect('/')->with('success', 'CB Property Auth Code Saved Successfully!');
        }
        return back()->with('error', 'Some error occurred.');
    }

    /**
     * Fetch all cloudbeds webhooks
     */

    public function cloudbedsWebhooksList($propertyId)
    {

        //         $deleted = self::deleteCloudbedsWebhookSubscription($propertyId,'4adae94d97c5fd89994274c330ce4aa5');
        // dd('e',$deleted);

        $propertyAccessToken = self::cloudbedsPropertyAccessToken($propertyId);
        if (!$propertyAccessToken['status']) {
            return back()->with('error', $propertyAccessToken['message']);
        }

        // $refreshToken = self::refreshToken($propertyAccessToken['token']);
        $url = config('cloudbeds.base_url') . 'getWebhooks';
        $response = Http::withHeaders([
            'Accept' => config('cloudbeds.accept'),
            'Authorization' => 'Bearer ' . $propertyAccessToken['token']->access_token,
            'Version' => config('cloudbeds.version'),
        ])->get($url, [
            'propertyID' => $propertyId,
        ]);

        if ($response->status() == '401') {
            $refreshToken = self::refreshToken($propertyAccessToken['token']);
            if (empty($refreshToken)) {
                return "Failed To Genereate Token";
            } else {
                self::cloudbedsWebhooksList($propertyId);
            }
        }
        if ($response->successful()) {
            $data = $response->json();
            if ($data['success']) {
                $data = json_encode($data);
                return view('dumb', compact('data'));
                // dd('Cloudbeds Webhooks', $data);
            } else {
                dd($data);
            }
        }

        if ($response->failed()) {
            return self::apiFailedResponse($response);
        }
    }

    /**
     * Create New  cloudbeds webhooks
     */

    public function subscribeWebhook($propertyId)
    {
        $propertyAccessToken = self::cloudbedsPropertyAccessToken($propertyId);
        // $propertyAccessToken = self::refreshToken($propertyAccessToken['token']);
        if (!$propertyAccessToken['status']) {
            return back()->with('error', $propertyAccessToken['message']);
        }
        $endpointUrl = env('APP_URL') . '/api/webhook/cloudbeds';
        $client = new Client();
        $response = $client->post(config('cloudbeds.base_url') . 'postWebhook', [
            'headers' => [
                'Authorization' => 'Bearer ' .  $propertyAccessToken['token']->access_token, // Make sure to replace $accessToken with the actual token
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'form_params' => [
                'endpointUrl' => $endpointUrl,
                'object' => 'reservation',
                'action' => 'created',
            ]
        ]);

        $resp = json_decode($response->getBody());
        if ($resp->success) {
            $property = CloudbedsProperty::where('property_id', $propertyId)->first();
            $property->reservation_sub_id = $resp->data->subscriptionID;
            $property->save();
            return back()->with('success', 'Reservation Created Subscribed');
        }
        return view('dumb', compact('data'));
    }
    /**
     * Delete cloudbeds webhook subscription..
     * @param int $propertyId
     * @param int $subscriptionID
     */
    public function unsubscribeWebhook($propertyId)
    {
        $property = CloudbedsProperty::where('property_id', $propertyId)->first();

        if (empty($property)) {
            return back()->with('success', 'CB property not exist');
        }
        $subscriptionID = $property->reservation_sub_id;
        $propertyAccessToken = self::cloudbedsPropertyAccessToken($propertyId);
        // $propertyAccessToken = self::refreshToken($propertyAccessToken['token']);
        if (!$propertyAccessToken['status']) {
            return back()->with('error', $propertyAccessToken['message']);
        }

        $url = config('cloudbeds.base_url') . 'deleteWebhook?subscriptionID=' . $subscriptionID;
        $response = Http::withHeaders([
            'Accept' => config('cloudbeds.accept'),
            'Authorization' => 'Bearer ' . $propertyAccessToken['token']->access_token,
            'Version' => config('cloudbeds.version'),
        ])->delete($url);

        if ($response->successful()) {
            $data = $response->json();
            if ($data['success']) {
                $property->reservation_sub_id = null;
                $property->save();
                return back()->with('success', 'Reservation Unsubscribed');
            } else {
                dd($data);
            }
        }

        if ($response->failed()) {
            $data = self::apiFailedResponse($response);
            $data = json_encode($data);
            return view('dump', compact('data'));
        }
    }

    /**
     * List All Cloudbeds Properties
     */
    public function properties()
    {
        $properties = CloudbedsProperty::all();
        return view('cb.properties', compact('properties'));
    }

    /**
     * Save Cloudbeds Property
     */
    public function saveProperty(Request $request)
    {
        $validatedData = $request->validate([
            'property_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string|max:255',
        ]);

        $property = new CloudbedsProperty();
        $property->property_id = $request->property_id;
        $property->name = $request->name;
        $property->client_id = $request->client_id;
        $property->client_secret = $request->client_secret;
        $property->save();
        return back()->with('success', 'Cb property created successfully!');
    }
    /**
     * Delete Cloudbeds Properties
     */
    public function deleteProperty($id)
    {

        $deleted = CloudbedsProperty::findOrFail($id)->delete();
        if ($deleted) {
            return back()->with('success', 'Cb property deleted successfully!');
        }

        return back()
            ->with('error', 'Some error occurred while deleting the property!');
    }

    /**
     * home 
     */
    public function home()
    {
        $maps = PropertyLocationMap::all();
        $properties = CloudbedsProperty::all();
        $locations = GhlLocation::all();
        return view('home', compact('maps', 'properties', 'locations'));
    }
    /**
     * map cloudbeds property with  highlevel location
     */
    public function mapPropertyLocation(Request $request)
    {
        $validatedData = $request->validate([
            'property_id' => 'required',
            'location_id' => 'required',
        ]);


        $mapped = PropertyLocationMap::updateOrCreate(
            ['property_id' => $request->property_id],
            [
                'property_id' => $request->property_id,
                'location_id' => $request->location_id,
            ]
        );

        if ($mapped) {
            return back()->with('success', 'Property-Location Mapped successfully!');
        }
        return back()->with('error', 'Some error occurred');
    }
}
