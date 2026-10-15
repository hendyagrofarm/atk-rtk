<tr>
    <td class="center">{{ $number }}</td>
    <td>{{ $detail->custom_item_name }}</td>
    <td>
        {{ $detail->brand_type }}
        @if ($detail->specification)
            <span class="muted">{{ \Illuminate\Support\Str::limit($detail->specification, 120) }}</span>
        @endif
    </td>
    <td class="center">{{ number_format($detail->quantity, 0, ',', '.') }}</td>
    <td class="center">{{ $detail->unit }}</td>
    <td>{{ $detail->usage_purpose }}</td>
    <td>
        {{ $detail->note }}
        @if ($pengajuan->status === 'approved' && (int) $detail->approved_quantity !== (int) $detail->quantity)
            <span class="muted">Disetujui: {{ number_format($detail->approved_quantity, 0, ',', '.') }} {{ $detail->unit }}</span>
        @endif
    </td>
</tr>
