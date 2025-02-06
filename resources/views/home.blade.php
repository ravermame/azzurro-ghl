@extends('layout')

@section('section')

<div class="container mt-5">
    <div class="d-flex flex-column">
        <!-- Section 1: GHL-CLOUDBEDS INTEGRATION -->

        <div class="card mb-4">
            <div class="card-header bg-grey text-dark">
                <h3 class="card-title mb-0">GHL-CLOUDBEDS INTEGRATION</h3>
            </div>
            <div class="card-body">
                <ol class="pl-3">
                    <li class="mb-3">
                        <strong>Create Cloudbeds Property</strong>
                        <ul class="pl-3 mt-2">
                            <li>Obtain Cloudbeds API credentials from <strong>Settings > Apps & Marketplace > API Credentials</strong>.</li>
                            <li>Retrieve the <strong>Property ID</strong>, <strong>Client ID</strong>, and <strong>Client Secret</strong> from the Marketplace.</li>
                            <li>Go to "CB properties" and create a new property.</li>
                            <li>Generate an <strong>Auth Code</strong> and <strong>Access Token</strong>.</li>
                            <li>Call the <strong>Contacts API</strong> to sync data.</li>
                        </ul>
                    </li>
                    <li class="mb-3">
                        <strong>Create GoHighLevel (GHL) Location</strong>
                        <ul class="pl-3 mt-2">
                            <li>Create a GHL Developer App and obtain the <strong>Client ID</strong> and <strong>Client Secret</strong> from <a href="https://marketplace.gohighlevel.com/apps" target="_blank">GHL Marketplace</a>.</li>
                            <li>Go to "GHL locations" and create a new location.</li>
                            <li>Generate an <strong>Auth Code</strong> and <strong>Access Token</strong>.</li>
                            <li>Create a custom contact field in GHL to store the Cloudbeds Guest ID and Propery Id if not exits (e.g., <strong>Guest ID, Propery ID</strong>).</li>
                        </ul>
                    </li>
                    <li class="mb-3">
                        <strong>Map GHL Location to Cloudbeds Property</strong>
                        <ul class="pl-3 mt-2">
                            <li>Map the GHL location to the corresponding Cloudbeds property.</li>
                            <li>Edit the mapping and select the respective location's pipeline, stage, and custom contact fields.</li>
                        </ul>
                    </li>
                    <li class="mb-3">
                        <strong>Subscribe to Cloudbeds Reservation Events</strong>
                        <ul class="pl-3 mt-2">
                            <li>Navigate to the Cloudbeds property listing.</li>
                            <li>Call the <strong>"Subscribe Reservation Created" API</strong> to enable event notifications.</li>
                        </ul>
                    </li>
                    <li class="mb-3">
                        <strong>Create GoHighLevel Automation</strong>
                        <ul class="pl-3 mt-2">
                            <li>Add a trigger for <strong>"Contact Change"</strong> in GHL.</li>
                            <li>Add a webhook with the post URL: <code> {{ env('APP_URL').'/api/webhook/ghl' }}</code>.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Expected Results</strong>
                        <ul class="pl-3 mt-2">
                            <li>Whenever a contact is updated in GHL, the email and phone number will be synchronized with Cloudbeds.</li>
                            <li>When a new reservation is created in Cloudbeds, a corresponding opportunity and contact will be created in GHL.</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>
        {{-- <div class="card mb-4">
            <div class="card-header bg-grey text-dark">
                <h3 class="card-title mb-0">GHL-CLOUDBEDS INTEGRATION</h3>
            </div>
            <div class="card-body">
                <ol class="pl-3">
                    <li class="mb-3">
                        <strong>Create Cloudbeds Property</strong>
                        <ul class="pl-3 mt-2">
                            <li>Get Cloudbeds API Credentials, Setting > Apps&Marketplace</li>
                            <li>Get PropertyId, ClientId & Client Secret From The Marketplace</li>
                            <li>Create Property</li>
                            <li>Generate Auth Code and Access Token</li>
                            <li>Hit Contacts API </li>
                        </ul>
                    </li>
                    <li class="mb-3">
                        <strong>Create HighLevel Location</strong>
                        <ul class="pl-3 mt-2">
                            <li>Create GHL Developer App And Get ClientId & Client Secret <em>https://marketplace.gohighlevel.com/apps</em></li>
                            <li>Create GHL Location</li>
                            <li>Generate Auth Code and Access Token</li>
                            <li>Create a Contact Field To Save Cloudbeds Id On Ghl contacts. eg.. Cloudbeds Reference (cb_reference) </li>
                        </ul>
                    </li>
                    <li class="mb-3">
                        <strong>Map GHL Location and CB Property</strong>
                        <ul class="pl-3 mt-2">
                            <li>Map GHL location with CB property</li>
                            <li>Edit listed map and select respective location's pipeline, stage, contact custom field</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Subscribed CB Reservation</strong>
                        <ul class="pl-3 mt-2">
                            <li>Go To CB Properties</li>
                            <li>Hit "Subscribe Reservation Created" API </li>
                        </ul>
                    </li>
                    <li>
                        <strong>Create GHL Automation</strong>
                        <ul class="pl-3 mt-2">
                            <li>Add trigger "Contact Change"</li>
                            <li>Add Webhook "http://localhost:8000/api/webhook/ghl" </li>
                        </ul>
                    </li>
                    <li>
                        <strong>Result</strong>
                        <ul class="pl-3 mt-2">
                            <li>On every change in ghl contact, Update email and phone in cloudbeds</li>
                            <li>On Reservation create in cloudbeds, Create Opportuntiy and contact in GHL</li>
                  
                        </ul>
                    </li>
                </ol>
            </div>
        </div> --}}

        <!-- Section 2: Map HighLevel Location With Cloudbeds Property -->
        <div class="card mb-4">
            <div class="card-header bg-grey text-dark">
                <h3 class="card-title mb-0">Map Cloudbeds Property With HighLevel Location </h3>
            </div>
            <div class="card-body">
                <form action="{{ route('map.property.location') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="property_id" class="form-label">Cloudbeds Property</label>
                            <select class="form-control" name="property_id" id="property_id">
                                @foreach ($properties as $property)
                                <option value="{{ $property->property_id }}">{{ $property->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="location_id" class="form-label">HighLevel Location</label>
                            <select class="form-control" name="location_id" id="location_id">
                                @foreach ($locations as $location)
                                <option value="{{ $location->location_id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 3: Mapped Properties and Locations Table -->
        <div class="card">
            <div class="card-header bg-grey text-dark">
                <h3 class="card-title mb-0">Mapped Listing</h3>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">Property</th>
                            <th scope="col">Location</th>
                            <th scope="col">Pipeline</th>
                            <th scope="col">Stage</th>
                            <th scope="col">GHL Contact Field</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($maps as $map)
                        <tr>
                            <td>{{ $map->property->name }}</td>
                            <td>{{ $map->location->name }}</td>
                            <td>{{ empty($map->pipeline_name) ? 'Not Set' : $map->pipeline_name }}</td>
                            <td>{{ empty($map->pipeline_stage_name) ? 'Not Set' : $map->pipeline_stage_name }}</td>
                            <td>{{ empty($map->contact_field_name) ? 'Not Set' : $map->contact_field_name }}</td>
                            <td>
                                <a class="btn btn-sm btn-warning" href="{{ route('map.edit', ['id' => $map->id]) }}">Edit</a>
                                <form action="{{ route('map.delete', $map->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this property map?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">DELETE</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
