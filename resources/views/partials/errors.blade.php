@if ($errors->any())
    <div class="form-alert error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('flash'))
    <div class="form-alert ok">{{ session('flash') }}</div>
@endif
