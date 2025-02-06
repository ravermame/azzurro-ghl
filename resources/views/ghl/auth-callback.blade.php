@extends('layout')
@section('section')
<div class="container mt-5">
    <h2>Save GHL Location Auth Code</h2>
    @if($code)
    <form action="{{ route('ghl.save.authcode')}}" method="POST">
        @csrf
        <div class="row">
            <div class="col-6">
                <label for="code">Auth Code</label>
                <input class="form-control" type="text" name="code" value="{{ $code }}" readonly>
            </div>
            <div class="col-6">
                <label for="property_id">Respective Location</label>
                <select class="form-control" name="location_id" id="location_id" required>
                    @foreach ($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-4">
            <button class="btn btn-sm btn-primary" type="submit">Save Auth Code</button>
        </div>
    </form>
    @else
    <p><i>No Code Found </i></p>
    @endif
</div>
@endsection
