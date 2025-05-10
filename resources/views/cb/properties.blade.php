@extends('layout')
@section('section')
<div class="container mt-5">
    <h2 class="mb-4">New CB Property</h2>
    <form action="{{ route('cb.save.property')}}" method="POST">
        @csrf
        <div class="row">
            <!-- Property ID -->
            <div class="mb-3 col-6">
                <label for="propertyId" class="form-label">Property ID</label>
                <input type="number" class="form-control" id="propertyId" name="property_id" value="{{ old('property_id') }}" placeholder="Enter Property ID" required>
            </div>
            <!-- Name -->
            <div class="mb-3 col-6">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="Enter Name" required>
            </div>
        </div>
        <div class="row">

            <!-- Client ID -->
            <div class="mb-3 col-6">
                <label for="clientId" class="form-label">Client ID</label>
                <input type="text" class="form-control" id="clientId" name="client_id" value="{{ old('client_id') }}" placeholder="Enter Client ID" required>
            </div>

            <!-- Client Secret -->
            <div class="mb-3 col-6">
                <label for="clientSecret" class="form-label">Client Secret</label>
                <input type="text" class="form-control" id="clientSecret" name="client_secret" value="{{ old('client_secret') }}" placeholder="Enter Client Secret" required>
            </div>

        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>

    </form>

    <div class="">
        <p>All Properties</p>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Property ID</th>
                <th scope="col">Name</th>
                <th scope="col">APIs</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($properties as $property)
            <tr>
                <td> {{ $loop->index + 1 }}</td>
                <td>{{ $property->property_id }}</td>
                <td> {{ $property->name }}</td>
                <td>
                    <a href="{{ route('property.auth',$property->id) }}" class="btn btn-warning btn-sm mt-2">Generate Auth Code</a> <br>
                    <a href="{{ route('property.guest.list',$property->property_id) }}" class="btn btn-success btn-sm mt-2">Guests</a> <br>
                    <a href="{{ route('cloudbeds.property.webhooks',$property->property_id) }}" class="btn btn-success btn-sm mt-2">Subscribed Webhooks</a> <br>
                    <a href="{{ route('cloudbeds.property.reservations',$property->property_id) }}" class="btn btn-success btn-sm mt-2">Reservations</a> <br>

                    @if(!empty($property->reservation_sub_id))
                    <a href="{{ route('cb.property.unsubscribe_webhook',$property->property_id) }}" class="btn btn-danger btn-sm mt-2">Unsubscribe Reservation</a> <br>
                    @else
                    <a href="{{ route('cb.property.subscribe_webhook',$property->property_id) }}" class="btn btn-success btn-sm mt-2">Subscribe Reservation Created</a> <br>
                    @endif

                </td>
                <td>
                    <div class="d-flex">
                        <form action="{{ route('cb.delete.property', $property->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this cloudbeds property?');" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm ml-2">DELETE</button>
                        </form>
                    </div>
                </td>

            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
