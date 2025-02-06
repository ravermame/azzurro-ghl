<?php

use App\Http\Controllers\CloudbedsController;
use App\Http\Controllers\GhlController;
use App\Models\GhlLocation;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [CloudbedsController::class, 'home'])->name("home");

// cloudbeds
Route::get('/oauth/{property_id}', [CloudbedsController::class, 'getOAuth'])->name('property.auth');
Route::get('tokens', [CloudbedsController::class, 'tokens'])->name('tokens');
Route::put('update/token/{id}', [CloudbedsController::class, 'updateToken'])->name('token.update');

Route::get('/auth-response', [CloudbedsController::class, 'authResponse']);

Route::get('/token', [CloudbedsController::class, 'getAccessToken']);
Route::get('property/{property_id}/guests', [CloudbedsController::class, 'getGuestList'])->name('property.guest.list');
Route::get('property/{property_id}/guest/{id}', [CloudbedsController::class, 'getGuest'])->name('property.guest');
Route::get('cloudbeds/{property_id}/webhooks', [CloudbedsController::class, 'cloudbedsWebhooksList'])->name('cloudbeds.property.webhooks');
Route::get('cloudbeds/{property_id}/reservations', [CloudbedsController::class, 'reservations'])->name('cloudbeds.property.reservations');


Route::post('map/property/location', [CloudbedsController::class, 'mapPropertyLocation'])->name('map.property.location');
Route::get('map/edit/{id}', [GhlController::class, 'mapEdit'])->name('map.edit');
Route::delete('map/delete/{id}', [GhlController::class, 'mapDelete'])->name('map.delete');
Route::post('map/save/{id}', [GhlController::class, 'saveLocationPipeline'])->name('map.save');



Route::group([
    'as' => 'cb.',
    'prefix' => 'cb/'
], function () {
    Route::get('properties', [CloudbedsController::class, 'properties'])->name('properties');
    Route::post('save/property', [CloudbedsController::class, 'saveProperty'])->name('save.property');
    Route::delete('delete/property/{id}', [CloudbedsController::class, 'deleteProperty'])->name('delete.property');
    Route::post('save/auth-code', [CloudbedsController::class, 'savePropertyAuthCode'])->name('save.authcode');
    Route::get('/{property_id}/subscribe-webhook', [CloudbedsController::class, 'subscribeWebhook'])->name('property.subscribe_webhook');
    Route::get('/{property_id}/unsubscribe-webhook', [CloudbedsController::class, 'unsubscribeWebhook'])->name('property.unsubscribe_webhook');
});

// GGL
Route::get('auth-callback', [GhlController::class, 'authCallback'])->name('ghl.callback');


Route::group([
    'as' => 'ghl.',
    'prefix' => 'ghl/'
], function () {

    Route::get('locations', [GhlController::class, 'locations'])->name('locations');
    Route::post('save/location', [GhlController::class, 'saveLocation'])->name('save.location');
    Route::delete('delete/location/{id}', [GhlController::class, 'deleteLocation'])->name('delete.location');
    Route::post('save/auth-code', [GhlController::class, 'saveLocationAuthCode'])->name('save.authcode');


    Route::get('oauth/{id}', [GhlController::class, 'requestAuthCode'])->name('auth');
    Route::get('token/{id}', [GhlController::class, 'generateToken'])->name('token');

    Route::get('{location_id}/contacts', [GhlController::class, 'contacts'])->name("contacts");
    Route::get('{location_id}/contact/{id}', [GhlController::class, 'getContact'])->name('contact');
    Route::get('{location_id}/calendars', [GhlController::class, 'calendars'])->name("calendars");
    Route::get('{location_id}/opportunities', [GhlController::class, 'opportunities'])->name("opportunities");
    Route::get('{location_id}/pipelines', [GhlController::class, 'pipelines'])->name("pipelines");
});

Route::get('webhooks/{source}', [GhlController::class, 'webhooks'])->name('webhooks');