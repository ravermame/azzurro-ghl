@extends('layout')
@section('section')
<div class="container mt-5">
    <h2>Webhooks</h2>
    <table class="table">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Source</th>
                <th scope="col">Data</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($webhooks as $webhook)
            <tr>
                <td> {{ $webhook->id }}</td>
                <td>{{ $webhook->source }}</td>
                <td>
                    <textarea class="form-control" name="" id="" cols="10" rows="2"> {{ $webhook->response }} </textarea>
                </td>
                <td>
                    @if($webhook->processed)
                    <p class="text-success">COMPLETE</p>
                    @else
                    <p class="text-info">CAPTURED </p>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
