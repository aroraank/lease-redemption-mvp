@extends('layout')
@section('title', 'Available leases')

@section('content')
<div class="card">
    <h2>Available leases</h2>
    <p class="meta">
        Dealer <b>{{ strtoupper($dealerCode) }}</b> &middot; ZIP {{ $zip }} &middot;
        {{ count($leases) }} lease(s) found
    </p>

    <form method="POST" action="{{ route('leases.redeem') }}" id="redeemForm">
        @csrf
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:42px">
                            <input type="checkbox" id="selectAll" aria-label="Select all leases">
                        </th>
                        <th>Lease ID</th>
                        <th>Customer</th>
                        <th>Vehicle</th>
                        <th>Maturity</th>
                        <th>Payoff</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($leases as $lease)
                        <tr>
                            <td>
                                <input type="checkbox" class="rowCheck" name="lease_ids[]"
                                       value="{{ $lease['id'] }}" aria-label="Select lease {{ $lease['id'] }}">
                            </td>
                            <td><strong>{{ $lease['id'] }}</strong></td>
                            <td>{{ $lease['customer'] }}</td>
                            <td>{{ $lease['vehicle'] }}</td>
                            <td>{{ $lease['maturity'] }}</td>
                            <td class="money">${{ number_format($lease['payoff'], 2) }}</td>
                            <td><span class="pill">{{ $lease['status'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="selbar">
            <div class="count"><b id="selCount">0</b> of {{ count($leases) }} selected</div>
            <div class="actions">
                <a href="{{ route('home') }}" class="btn secondary">Cancel</a>
                <button type="submit" class="btn" id="redeemBtn" disabled>Start redemption</button>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    (function () {
        var selectAll = document.getElementById('selectAll');
        var rows      = Array.prototype.slice.call(document.querySelectorAll('.rowCheck'));
        var countEl   = document.getElementById('selCount');
        var redeemBtn = document.getElementById('redeemBtn');
        var form      = document.getElementById('redeemForm');

        function refresh() {
            var checked = rows.filter(function (c) { return c.checked; });
            countEl.textContent = checked.length;
            redeemBtn.disabled = checked.length === 0;
            selectAll.checked = checked.length === rows.length && rows.length > 0;
            selectAll.indeterminate = checked.length > 0 && checked.length < rows.length;
        }

        selectAll.addEventListener('change', function () {
            rows.forEach(function (c) { c.checked = selectAll.checked; });
            refresh();
        });
        rows.forEach(function (c) { c.addEventListener('change', refresh); });

        form.addEventListener('submit', function () {
            redeemBtn.disabled = true;
            redeemBtn.textContent = 'Processing...';
        });

        refresh();
    })();
</script>
@endsection
