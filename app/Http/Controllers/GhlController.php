<?php

namespace App\Http\Controllers;

use App\Enums\WebhookSourceEnums;
use App\Models\GhlLocation;
use App\Models\PropertyLocationMap;
use App\Models\UserToken;
use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GhlController extends Controller
{

    public $userId = 1;



    /**
     * List All Saved GHL locations
     */
    public function locations()
    {
        $locations = GhlLocation::all();
        return view('ghl.locations', compact('locations'));
    }

    /**
     * Save new ghl location
     */
    public function saveLocation(Request $request)
    {

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'location_id' => 'required|string|max:255',
            'client_id' => 'required|string|max:255',
            'client_secret' => 'required|string|max:255',
        ]);

        $location = new GhlLocation();
        $location->name = $request->name;
        $location->location_id = $request->location_id;
        $location->client_id = $request->client_id;
        $location->client_secret = $request->client_secret;
        $location->save();
        return back()->with('success', 'GHL location created successfully!');
    }

    /**
     * Delete GHL Location
     */
    public function deleteLocation($id)
    {
        $deleted = GhlLocation::findOrFail($id)->delete();
        if ($deleted) {
            return back()->with('success', 'GHL location deleted successfully!');
        }
        return back()
            ->with('error', 'Some error occurred while deleting the ghl location!');
    }

    /**
     *Request Go High Level Auth Code
     */
    public function requestAuthCode($id)
    {
        $location =  GhlLocation::find($id);
        if (empty($location)) {
            return back()->with('error', 'GHL location not exist!');
        }
        $redirectUri = route('ghl.callback');
        $scopes = implode(' ', config('ghl.scopes'));
        $url = config('ghl.auth_base_url') . "?response_type=" . config('ghl.response_type') . "&redirect_uri=" . $redirectUri . "&client_id=" . $location->client_id . "&scope=" . $scopes;
        return redirect($url);
    }

    /**
     * Handle Go High Level Auth Callback
     */
    public function authCallback(Request $request)
    {
        $code = null;
        if ($request->has('code')) {
            $code  = $request->code;
        }
        $locations = GhlLocation::all();
        return view('ghl.auth-callback', compact('code', 'locations'));
    }

    /**
     * Save Cloudbeds Property Auth Code
     */
    public function saveLocationAuthCode(Request $request)
    {
        $validatedData = $request->validate([
            'location_id' => 'required|string',
            'code' => 'required|string',
        ]);

        $saved = UserToken::updateOrCreate(
            ['user_id' => $this->userId, 'type' => WebhookSourceEnums::GHL, 'property_id' => $request->location_id],
            [
                'code' => $request->code,
            ]
        );

        if ($saved) {
            return redirect()->route('ghl.locations')->with('success', 'GHL Location Auth Code Saved Successfully!');
        }
        return back()->with('error', 'Some error occurred.');
    }

    /**
     * Save Go High Level API auth token into database
     */
    public static function saveGhlToken($locationId, $accessToken, $refreshToken)
    {
        $userToken = UserToken::updateOrCreate(
            ['user_id' => UserToken::$DEFAULT_USER_ID, 'type' => WebhookSourceEnums::GHL, 'property_id' => $locationId],
            [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ]
        );
        return $userToken;
    }

    /**
     * Get GHL API access token
     */
    public static function getToken($property_id)
    {
        $userToken = UserToken::where('user_id', UserToken::$DEFAULT_USER_ID)->where('property_id', $property_id)->whereType(WebhookSourceEnums::GHL)->first();
        if (empty($userToken)) {
            return ['status' => false, 'message' => 'HighLevel Auth Code Not Genereated!'];
        } else {
            return ['status' => true, 'userToken' => $userToken];
        }
    }

    /**
     * Generate Go High Level API access token
     */
    public function generateToken($id)
    {
        $location = GhlLocation::find($id);
        if (empty($location)) {
            return back()->with('error', 'High Level Location Not Exist');
        }
        $userToken = UserToken::whereType(WebhookSourceEnums::GHL)->where('property_id', $location->location_id)->first();
        if (empty($userToken)) {
            return back()->with('error', 'High Level Auth Code Not Genereated');
        }
        $params = [
            'client_id' => $location->client_id,
            'client_secret' => $location->client_secret,
            'grant_type' => config('ghl.token_grant_type'),
            'code' => $userToken->code,
            'redirect_uri' => env('APP_URL') . '/auth-response',
        ];
        $response = Http::asForm()->post(config('ghl.token_url'), $params);
        if ($response->successful()) {
            $json = $response->json();
            $userToken = $this->saveGhlToken($json['locationId'],  $json['access_token'], $json['refresh_token']);
            return back()->with('success', 'GHL Access Token Genereated!');
        }
        if ($response->failed()) {
            $resp = self::apiFailedResponse($response);
            return back()->with('error', $resp['body']);
        }
    }


    /**
     * Refresh Go High Level API access token
     */
    public static function refreshToken($userToken)
    {
        try {
            //code...
            if (empty($userToken->refresh_token)) {
                return null;
            }

            $location = GhlLocation::find($userToken->property_id);
            $params = [
                'client_id' => $location->client_id,
                'client_secret' => $location->client_secret,
                'grant_type' => config('ghl.token_refresh'),
                'refresh_token' => $userToken->refresh_token,
            ];

            $response = Http::asForm()->post(config('ghl.token_url'), $params);

            if ($response->successful()) {
                $json = $response->json();
                $userToken = self::saveGhlToken($userToken->property_id, $json['locationId'],  $json['access_token'], $json['refresh_token']);
            }
            if ($response->failed()) {
                $failed = self::apiFailedResponse($response);
                dd($failed);
            }
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function contacts($locationId)
    {
        $token = self::getToken($locationId);
        if (empty($token['status'])) {
            return back()->with('error', $token['message']);
        }
        $userToken = $token['userToken'];


        $url = config('ghl.base_url') . 'contacts';
        $response = Http::withHeaders([
            'Accept' => config('ghl.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('ghl.api_version_header'),
        ])->get($url, [
            'locationId' => $locationId
        ]);
        if ($response->successful()) {
            $data = $response->json();
            dd('GHL Contacts', $data['contacts']);
        }
        if ($response->getStatusCode() == '401' && str_contains($response->body(), 'Invalid JWT')) {
            $token = self::refreshToken($userToken);
            if (!empty($token)) {
                self::contacts($locationId);
            }
        }
        if ($response->failed()) {
            $resp = self::apiFailedResponse($response);
            return back()->with('error', $resp['body']);
        }
    }

    /**
     * Get Ghl Contact
     */
    public static function getContact($locationId, $id)
    {
        $token = self::getToken($locationId);
        if (empty($token['status'])) {
            return back()->with('error', $token['message']);
        }
        $userToken = $token['userToken'];

        $url = config('ghl.base_url') . 'contacts/' . $id;
        $response = Http::withHeaders([
            'Accept' => config('ghl.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('ghl.api_version_header'),
        ])->get($url);
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['contact'])) {
                return $data['contact'];
            }
        }
        if ($response->failed()) {
            $resp = self::apiFailedResponse($response);
            return back()->with('error', $resp['body']);
        }
    }
    /**
     * Get Ghl Contact
     */
    public static function getContactByCloudbedsId($locationId, $cbId)
    {
        $token = self::getToken($locationId);
        if (empty($token['status'])) {
            return back()->with('error', $token['message']);
        }
        $userToken = $token['userToken'];
        $map = PropertyLocationMap::where('location_id', $locationId)->first();
        if (empty($map)) {
            return [
                'status' => false,
                'data' => 'Contact Custom Field Id Not Exist'
            ];
        }

        $url = config('ghl.base_url') . 'contacts/search';
        $response = Http::withHeaders([
            'Accept' => config('ghl.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('ghl.api_version_header'),
        ])->post($url, [
            'locationId' => $locationId,
            'page' => 1,
            'pageLimit' => 1,
            'filters' => [
                [
                    "field" => "customFields." . $map->contact_field_id,
                    "operator" => "eq",
                    "value" => $cbId
                ]
            ]
        ]);
        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['contacts'])) {
                if (empty($data['contacts'])) {
                    return [
                        'status' => false,
                        'data' => 'NOT FOUND'
                    ];
                }
                return [
                    'status' => true,
                    'data' => reset($data['contacts'])
                ];
            }
        }
        if ($response->failed()) {
            return [
                'status' => false,
                'data' => self::apiFailedResponse($response)
            ];
        }
    }

    /**
     * Prepare contact custom fields array
     */
    public static function prepareContactPayload($locationId, $reservationPayload)
    {

        $contact = reset($reservationPayload['guestList']);
        $map = PropertyLocationMap::where('location_id', $locationId)->first();
        $customFields = [];

        if ($map && !empty($map->fields_map)) {

            if (isset($map->fields_map['guest_id']) && isset($contact['guestID'])) {
                $customFields[] = array(
                    'name' => 'guestID',
                    'id' => $map->fields_map['guest_id'],
                    'field_value' => $contact['guestID']
                );
            }

            if (isset($map->fields_map['property_id']) && isset($reservationPayload['propertyID'])) {
                $customFields[] = array(
                    'name' => 'propertyID',
                    'id' => $map->fields_map['property_id'],
                    'field_value' => $reservationPayload['propertyID']
                );
            }

            if (isset($map->fields_map['reservation_id']) && isset($reservationPayload['reservationID'])) {
                $customFields[] = array(
                    'name' => 'reservationID',
                    'id' => $map->fields_map['reservation_id'],
                    'field_value' => $reservationPayload['reservationID']
                );
            }

            if (isset($map->fields_map['reservation_status']) && isset($reservationPayload['status'])) {
                $customFields[] = array(
                    'name' => 'reservation_status',
                    'id' => $map->fields_map['reservation_status'],
                    'field_value' => $reservationPayload['status']
                );
            }

            $reserveFields = array('start_date', 'end_date', 'guest_status', 'balance');
            foreach ($reserveFields as $reserveField) {
                if (isset($map->fields_map[$reserveField]) && isset($reservationPayload[toCamelCase($reserveField)])) {
                    $customFields[] = array(
                        'name' => $reserveField,
                        'id' => $map->fields_map[$reserveField],
                        'field_value' => $reservationPayload[toCamelCase($reserveField)]
                    );
                }
            }

            $room = reset($reservationPayload['unassigned']);
            if (!empty($room)) {

                if (isset($map->fields_map['sub_reservation_id']) && isset($room['subReservationID'])) {
                    $customFields[] = array(
                        'name' => 'subReservationID',
                        'id' => $map->fields_map['sub_reservation_id'],
                        'field_value' => $room['subReservationID']
                    );
                }

                if (isset($map->fields_map['guest_adults']) && isset($room['adults'])) {
                    $customFields[] = array(
                        'name' => 'adults',
                        'id' => $map->fields_map['guest_adults'],
                        'field_value' => $room['adults']
                    );
                }

                if (isset($map->fields_map['guest_children']) && isset($room['children'])) {
                    $customFields[] = array(
                        'name' => 'children',
                        'id' => $map->fields_map['guest_children'],
                        'field_value' => $room['children']
                    );
                }

                $roomFields = array('room_type_name', 'room_type_name_short', 'room_total');
                foreach ($roomFields as $roomField) {
                    if (isset($map->fields_map[$roomField]) && isset($room[toCamelCase($roomField)])) {
                        $customFields[] = array(
                            'name' => $roomField,
                            'id' => $map->fields_map[$roomField],
                            'field_value' => $room[toCamelCase($roomField)]
                        );
                    }
                }

                if (isset($map->fields_map['room_type_id']) && isset($room['roomTypeID'])) {
                    $customFields[] = array(
                        'name' => 'roomTypeID',
                        'id' => $map->fields_map['room_type_id'],
                        'field_value' => $room['roomTypeID']
                    );
                }

                $dailyRates = reset($room['dailyRates']);
                if (isset($map->fields_map['assigned_daily_rates']) && isset($dailyRates['rate'])) {
                    $customFields[] = array(
                        'name' => 'date',
                        'id' => $map->fields_map['assigned_daily_rates'],
                        'field_value' => $dailyRates['rate']
                    );
                }
                if (isset($map->fields_map['daily_rate_date']) && isset($dailyRates['date'])) {
                    $customFields[] = array(
                        'name' => 'rate',
                        'id' => $map->fields_map['daily_rate_date'],
                        'field_value' => $dailyRates['date']
                    );
                }
            }
        }

        return [
            "firstName" => $contact['guestFirstName'],
            "lastName" => $contact['guestLastName'],
            "email" => $contact['guestEmail'],
            "phone" => $contact['guestPhone'],
            "dateOfBirth" => $contact['guestBirthdate'],
            "source" => $reservationPayload['source'],
            // address
            "city" => $contact['guestCity'],
            "state" => $contact['guestState'],
            "postalCode" => $contact['guestZip'],
            "address1" => $contact['guestAddress'] . '' . $contact['guestAddress2'],
            "country" => $contact['guestCountry'],
            // custom fields
            "customFields" => $customFields
        ];
    }

    /**
     * Create New Contact HighLevel
     */
    public static function createNewContact($locationId, $reservationPayload)
    {
        $payload = self::prepareContactPayload($locationId, $reservationPayload);
        $payload['locationId'] = $locationId;
        $token = self::getToken($locationId);
        if (empty($token['status'])) {
            return [
                'status' => false,
                'data' => 'Token Expired'
            ];
        }
        $userToken = $token['userToken'];
        $url = config('ghl.base_url') . 'contacts';
        $response = Http::withHeaders([
            'Accept' => config('ghl.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('ghl.api_version_header'),
        ])->post($url, $payload);
        if ($response->successful()) {
            return [
                'status' => true,
                'data' => $response->json()['contact']
            ];
        }
        if ($response->failed()) {
            return [
                'status' => false,
                'data' => self::apiFailedResponse($response)
            ];
        }
    }
    /**
     * Create New Contact HighLevel
     */
    public static function updateContact($locationId, $id, $reservationPayload)
    {
        $payload = self::prepareContactPayload($locationId, $reservationPayload);
        $token = self::getToken($locationId);
        if (empty($token['status'])) {
            return [
                'status' => false,
                'data' => 'Token Expired'
            ];
        }
        $userToken = $token['userToken'];
        $url = config('ghl.base_url') . 'contacts/' . $id;
        $response = Http::withHeaders([
            'Accept' => config('ghl.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('ghl.api_version_header'),
        ])->put($url, $payload);
        if ($response->successful()) {
            return [
                'status' => true,
                'data' => $response->json()['contact']
            ];
        }
        if ($response->failed()) {
            return [
                'status' => false,
                'data' => self::apiFailedResponse($response)
            ];
        }
    }

    /**
     * Create or update contact on ghl
     */
    public static function createOrUpdateContact($locationId, $reservationPayload)
    {
        // Get Contacct
        $cbContact = reset($reservationPayload['guestList']);
        $contact = self::getContactByCloudbedsId($locationId, $cbContact['guestID']);
        if (!$contact['status']) {
            if ($contact['data'] && str_contains($contact['data'], 'Field Id Not Exist')) {
                return $contact;
            } else if ($contact['data'] && str_contains($contact['data'], 'NOT FOUND')) {
                return self::createNewContact($locationId, $reservationPayload);
            }
        }
        $contact = self::updateContact($locationId, $contact['data']['id'], $reservationPayload);
        // todo: update contact if required
        return $contact;
    }


    public function calendars($locationId)
    {
        $token = self::getToken($locationId);
        if (empty($token['status'])) {
            return back()->with('error', $token['message']);
        }
        $userToken = $token['userToken'];

        $url = config('ghl.base_url') . 'calendars/?locationId=' . $userToken->property_id;
        $response = Http::withHeaders([
            'Accept' => config('ghl.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('ghl.api_version_header'),
        ])->get($url, [
            'locationId' => $locationId
        ]);
        if ($response->successful()) {
            $data = $response->json();
            dd('GHL Calendars', $data);
        }
        if ($response->failed()) {
            $resp = self::apiFailedResponse($response);
            return back()->with('error', $resp['body']);
        }
    }

    /**
     * Get List of all pipelines
     */
    public static function pipelines($locationId)
    {
        $token = self::getToken($locationId);
        if (empty($token['status'])) {
            return back()->with('error', $token['message']);
        }
        $userToken = $token['userToken'];
        $url = config('ghl.base_url') . 'opportunities/pipelines?locationId=' . $locationId;
        $response = Http::withHeaders([
            'Accept' => config('ghl.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('ghl.api_version_header'),
        ])->get($url);
        if ($response->successful()) {
            return [
                'status' => true,
                'pipelines' => $response->json()['pipelines']
            ];
        }
        if ($response->failed()) {
            $resp = self::apiFailedResponse($response);
            return [
                'status' => false,
                'message' => "Failed: " . $resp['body']
            ];
        }
    }

    /**
     * Get List of all Custom fields
     */
    public static function customFields($locationId)
    {
        $token = self::getToken($locationId);
        if (empty($token['status'])) {
            return back()->with('error', $token['message']);
        }
        $userToken = $token['userToken'];
        $url = config('ghl.base_url') . 'locations/' . $locationId . '/customFields?model=contact';
        $response = Http::withHeaders([
            'Accept' => config('ghl.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('ghl.api_version_header'),
        ])->get($url);
        if ($response->successful()) {
            return [
                'status' => true,
                'fields' => $response->json()['customFields']
            ];
        }
        if ($response->getStatusCode() == '401') {
            $token = self::refreshToken($userToken);
            if (!empty($token)) {
                self::customFields($locationId);
            }
        }
        if ($response->failed()) {
            $resp = self::apiFailedResponse($response);
            return [
                'status' => false,
                'message' => "Failed: " . $resp['body']
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

    /**
     * Get attached p
     */
    public static function getGHLLocationViaCBProperty($propertyID)
    {
        $map = PropertyLocationMap::where("property_id", $propertyID)->first();
        if (empty($map)) {
            return [
                'status' => false,
                'data' => 'property location map not exist'
            ];
        }
        if (empty($map->pipeline_id) || empty($map->pipeline_stage_id)) {
            return [
                'status' => false,
                'data' => 'map pipeline_id or pipeline_stage_id not exist'
            ];
        }
        return [
            'status' => true,
            'data' => $map
        ];
    }


    /**
     * Create Highlevel Opportunity
     */
    public static function createOpportunity($reservation)
    {
        $map = self::getGHLLocationViaCBProperty($reservation['propertyID']);
        if (!$map['status']) {
            return $map;
        }
        $map = $map['data'];
        $locationId = $map->location_id;

        $ghlContact = self::createOrUpdateContact($locationId, $reservation);
        if (!$ghlContact['status']) {
            return $ghlContact;
        }

        // Get Token
        $token = self::getToken($locationId);
        if (empty($token['status'])) {
            return ['status' => false, 'data' => 'Token Expired'];
        }
        $userToken = $token['userToken'];
        // Create opportunity
        $payload = [
            "pipelineId" => $map->pipeline_id,
            "locationId" => $locationId,
            "name" => $reservation['guestName'],
            "pipelineStageId" => $map->pipeline_stage_id,
            "status" => config('ghl.opportunity_status'),
            "source" => $reservation['source'],
            "contactId" => $ghlContact['data']['id'],
            "monetaryValue" => $reservation['total'],
            // "assignedTo" => "cvF6wxdQbuOkULSMPmUK", // TODO: Whom assigned this eg..Ashish
        ];
        $url = config('ghl.base_url') . 'opportunities/';
        $response = Http::withHeaders([
            'Accept' => config('ghl.accept'),
            'Authorization' => 'Bearer ' . $userToken->access_token,
            'Version' => config('ghl.api_version_header'),
        ])->post($url, $payload);

        if ($response->successful()) {
            return ['status' => true, 'data' => $response->json()];
        }
        if ($response->failed()) {
            return ['status' => false, 'data' => self::apiFailedResponse($response)];
        }
    }

    public function mapEdit($id)
    {
        $map = PropertyLocationMap::find($id);
        if ($map) {
            $pipelines = self::pipelines($map->location_id);
            if (!$pipelines['status']) {
                return back()->with('error', $pipelines['message']);
            }
            $pipelines = $pipelines['pipelines'];
            $fields = self::customFields($map->location_id);
            if (!$fields['status']) {
                return back()->with('error', $fields['message']);
            }
            $fields = $fields['fields'];
            return view('map-edit', compact('map', 'pipelines', 'fields'));
        }
        return back()->with('error', 'Map Not Found!');
    }

    public static function saveLocationPipeline(Request $request, $id)
    {
        $validatedData = $request->validate([
            'property_id' => 'required|string|max:255',
            'location_id' => 'required|string|max:255',
            'pipeline_name' => 'required|string|max:255',
            'pipeline_stage_name' => 'required|string|max:255',
            'pipeline_id' => 'required|string|max:255',
            "pipeline_stage_id" => 'required|string|max:255',
            "contact_field_id" => 'required|string|max:255',
            "contact_field_name" => 'required|string|max:255',
            "contact_property_field_id" => 'required|string|max:255',
            "contact_property_field_name" => 'required|string|max:255',
        ]);

        // Prepare fields map
        $fieldsNames = $request->get('fields_cb_name', []);  // array of names
        $fieldsIds = $request->get('fields_ghl_id', []);     // array of IDs
        $fields_map = array_map(function ($name, $id) {
            return [$name => $id];
        }, $fieldsNames, $fieldsIds);
        $fields_map = array_merge(...$fields_map); // Flatten the array 

        $map = PropertyLocationMap::find($id);
        if ($map) {
            $map->pipeline_id = $request->pipeline_id;
            $map->pipeline_name = $request->pipeline_name;
            $map->pipeline_stage_id = $request->pipeline_stage_id;
            $map->pipeline_stage_name = $request->pipeline_stage_name;
            $map->contact_field_id = $request->contact_field_id;
            $map->contact_field_name = $request->contact_field_name;
            $map->contact_property_field_id = $request->contact_property_field_id;
            $map->contact_property_field_name = $request->contact_property_field_name;
            $map->fields_map = $fields_map;
            $map->save();
            return redirect()->route('home')->with('success', 'Property-Location Map Saved!');
        }
        return back()->with('error', 'Map Not Found!');
    }

    public function mapDelete($id)
    {
        $deleted = PropertyLocationMap::findOrFail($id)->delete();
        if ($deleted) {
            return back()->with('success', 'Map deleted successfully!');
        }
        return back()
            ->with('error', 'Some error occurred while deleting the map!');
    }

    /**
     * Fetch all webhooks
     */
    public function webhooks($source)
    {
        $webhooks = Webhook::where("source", $source)->orderBy('id', 'desc')->limit(10)->get();
        return view('webhook', compact('webhooks'));
    }
}
