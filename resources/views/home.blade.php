@extends('layout')
@section('title', 'Find your leases')

@section('content')
<div class="card">
    <h2>Find your leases</h2>
    <p class="sub">Enter your dealer code and ZIP code to view leases available for redemption.</p>

    <form method="POST" action="{{ route('leases.lookup') }}" novalidate>
        @csrf
        <div class="row">
            <div class="field">
                <label for="dealer_code">Dealer code</label>
                <input type="text" id="dealer_code" name="dealer_code"
                       value="{{ old('dealer_code') }}" placeholder="DLR1024" autocomplete="off">
            </div>
            <div class="field">
                <label for="zip">ZIP code</label>
                <input type="text" id="zip" name="zip"
                       value="{{ old('zip') }}" placeholder="80229" inputmode="numeric" autocomplete="off">
            </div>
        </div>
        <div class="actions">
            <button type="submit" class="btn">Find leases &rarr;</button>
        </div>
    </form>

    <div class="demo-note">
        <strong>Demo dealer codes</strong> (any 5-digit ZIP works):
        <span class="codes">
            <code>DLR1024</code> &middot; <code>DLR2048</code> &middot;
            <code>DLR3072</code> &middot; <code>DLR4096</code>
        </span>
        <a href="{{ route('demo.reset') }}" class="reset-link"
           onclick="return confirm('Reset demo data? All leases become available again.');">
            Reset demo data
        </a>
    </div>
</div>
@endsection
