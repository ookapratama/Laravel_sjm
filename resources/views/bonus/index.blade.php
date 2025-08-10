@extends('layouts.app')

@section('title', 'Bonus Pairing')

@section('content')
<div class="page-inner">
    <div
              class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4"
            >
              <div>
                <h3 class="fw-bold mb-3">Bonus Pasangan</h3>

              </div>
            </div>

    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Jumlah Pasangan</th>
                    <th>Bonus Kotor</th>
                    <th>Pajak</th>
                    <th>Bonus Bersih</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bonuses as $bonus)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $bonus->created_at->format('d M Y H:i') }}</td>
                        <td>
                            {{ preg_match('/(\d+)/', $bonus->notes, $matches) ? $matches[1] : '1' }} pasang
                        </td>
                        <td class="text-end">Rp. {{ number_format($bonus->amount, 0, ',', '.') }}</td>
                        <td class="text-end">Rp. {{ number_format($bonus->tax, 0, ',', '.') }}</td>
                        <td class="text-end"><strong>Rp {{ number_format($bonus->net_amount, 0, ',', '.') }}</strong></td>
                        <td>{{ $bonus->notes }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">Belum ada bonus pairing.</td>
                    </tr>
                @endforelse
                <tr class="table-secondary fw-bold">
    <td colspan="2" class="text-end">TOTAL</td>
    <td class="text-end">
        {{ $bonuses->sum(function ($b) {
            preg_match('/(\d+)/', $b->notes, $m);
            return isset($m[1]) ? (int)$m[1] : 1;
        }) }} pasang
    </td>
    <td class="text-end">Rp. {{ number_format($bonuses->sum('amount'), 0, ',', '.') }}</td>
    <td class="text-end">Rp. {{ number_format($bonuses->sum('tax'), 0, ',', '.') }}</td>
    <td class="text-end"><strong>Rp {{ number_format($bonuses->sum('net_amount'), 0, ',', '.') }}</strong></td>
    <td></td>
</tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
