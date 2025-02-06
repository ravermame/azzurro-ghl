@extends('layout')

@section('section')

<div class="container mt-5">
    <h2 class="mb-4">Tokens</h2>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Type</th>
                <th scope="col">User ID</th>
                <th scope="col">CB Property/GHL Location</th>
                <th scope="col">Auth Code</th>
                <th scope="col">Token</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tokens as $token)
            <tr>
                <td>{{ $loop->index + 1 }}</td>
                <td>{{ $token->type }}</td>
                <td>{{ $token->user_id }}</td>
                <td>{{ $token->property_id }}</td>
                <td>{{ $token->code }}</td>
                <td>
                    <form action="{{ route('token.update', ['id' => $token->id]) }}" method="POST">
                        @method('PUT')
                        @csrf
                        <input type="text" class="form-control" name="access_token" value="{{ $token->access_token }}">

                        <div class="d-flex justify-content-end">
                            <button class="btn btn-sm mt-2 btn-primary" type="submit">Update</button>
                        </div>
                        
                        
                    </form>
                    

                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>


@endsection
