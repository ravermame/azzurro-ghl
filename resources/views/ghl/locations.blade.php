@extends('layout')
@section('section')
<div class="container mt-5">
    <h2 class="mb-4">Add New GHL Location</h2>
    <form action="{{ route('ghl.save.location')}}" method="POST">
        @csrf
        <div class="row">

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
                <th scope="col">Location ID</th>
                <th scope="col">Name</th>
                <th scope="col">APIs</th>
                <th scope="col">Action</th>
            </tr>
        </thead>
        <tbody>

            @foreach($locations as $location)

            <tr>
                <td> {{ $loop->index + 1 }}</td>
                <td>{{ $location->location_id ? $location->location_id : 'Not genereated yet' }}</td>
                <td> {{ $location->name }}</td>

                <td>



                    <a href="{{ route('ghl.auth',['id' => $location->id ]) }}" class="btn btn-warning btn-sm mt-2">Request Auth Code</a> <br>
                    <a href="{{ route('ghl.token',['id' => $location->id ]) }}" class="btn btn-success btn-sm mt-2">Generate Access Token</a> <br>


                    @if(!empty($location->location_id))

                    <a href="{{ route('ghl.contacts',['location_id'=> $location->location_id ]) }}" class="btn btn-success btn-sm mt-2">Contacts</a> <br>
                    <a href="{{ route('ghl.calendars',['location_id'=> $location->location_id ]) }}" class="btn btn-success btn-sm mt-2"> GHL Calendars</a> <br>
                    {{-- <a href="{{ route('ghl.opportunities',['location_id'=> $location->location_id ]) }}" class=""> GHL Opportunities</a> <br> --}}
                    <a href="{{ route('ghl.pipelines',['location_id'=> $location->location_id ]) }}" class="btn btn-success btn-sm mt-2"> GHL Pipelines</a> <br>

                    @endif


                </td>
                <td>

                    <div class="d-flex">


                        <form action="{{ route('ghl.delete.location', $location->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this GHL location?');" class="d-inline">
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
