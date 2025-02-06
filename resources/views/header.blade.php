<nav class="navbar navbar-expand-lg navbar-light bg-blue">

    <div class="m-1">
        <a href="/" class="navbar-brand" href="#"><img src="{{ asset('logo-azzurro-4-f71db4fe-269w.webp')}}" alt="Azzurro Hotels"></a>
    </div>

    
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item {{ Request::routeIs('home') ? 'active' : '' }}">
                <a class="nav-link text-white" href="/">Home <span class="sr-only">(current)</span></a>
            </li>
            <li class="nav-item {{ Request::routeIs('ghl.locations') ? 'active' : '' }}">
                <a href="{{ route('ghl.locations')}}" class="nav-link text-white" href="#">GHL Locations</a>
            </li>
            <li class="nav-item {{ Request::routeIs('cb.properties') ? 'active' : '' }}">
                <a href="{{ route('cb.properties')}}" class="nav-link text-white" href="#">CB Properties</a>
            </li>
            <li class="nav-item {{ Request::routeIs('tokens') ? 'active' : '' }}">
                <a href="{{ route('tokens')}}" class="nav-link text-white" href="#">Tokens</a>
            </li>
            <li class="nav-item {{ Request::routeIs('webhooks') ? 'active' : '' }}">
                <a href="{{ route('webhooks',['source' => App\Enums\WebhookSourceEnums::CLOUDBEDS])}}" class="nav-link text-white" href="#">CB Webhooks</a>
            </li>
            <li class="nav-item {{ Request::routeIs('webhooks') ? 'active' : '' }}">
                <a href="{{ route('webhooks',['source' => App\Enums\WebhookSourceEnums::GHL])}}" class="nav-link text-white" href="#">GHL Webhooks</a>
            </li>
        </ul>
    </div>
</nav>

<!-- Show Validation Errors -->
@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<!-- Show Success Message -->
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif

<!-- Display error message -->
@if(session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif
