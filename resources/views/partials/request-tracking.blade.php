{{-- Riwayat pelacakan pengajuan ala jasa pengiriman. Butuh variabel $events (RequestTimeline::events). --}}
<ol class="px-4 py-4">
    @foreach ($events as $event)
        @php
            $dot = match ($event['state']) {
                'current' => 'bg-amber-500 ring-4 ring-amber-100',
                'done' => 'bg-emerald-500 ring-4 ring-emerald-100',
                'rejected' => 'bg-red-500 ring-4 ring-red-100',
                default => 'bg-slate-400 ring-4 ring-slate-100',
            };

            $titleColor = match (true) {
                $event['state'] === 'rejected' => 'text-red-700',
                $loop->first && $event['state'] === 'current' => 'text-amber-700',
                $loop->first => 'text-emerald-700',
                default => 'text-slate-700',
            };
        @endphp

        <li class="flex gap-3">
            <div class="flex flex-col items-center">
                <span class="mt-1 h-3 w-3 shrink-0 rounded-full {{ $dot }}"></span>

                @unless ($loop->last)
                    <span class="my-1 w-px flex-1 bg-slate-200"></span>
                @endunless
            </div>

            <div class="min-w-0 flex-1 {{ $loop->last ? '' : 'pb-4' }}">
                <p class="text-xs font-semibold {{ $titleColor }}">{{ $event['title'] }}</p>

                @if ($event['detail'])
                    <p class="mt-0.5 text-[11px] leading-4 text-slate-600">{{ $event['detail'] }}</p>
                @endif

                @if ($event['time'])
                    <p class="mt-0.5 text-[10px] font-medium text-slate-400">{{ $event['time'] }}</p>
                @elseif ($event['state'] === 'current')
                    <p class="mt-0.5 text-[10px] font-medium text-amber-600">Sedang berlangsung</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
