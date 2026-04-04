<x-layouts.admin title="Edit Order Pekerjaan">
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-3xl border border-slate-200 bg-blue-900 px-6 py-6 text-white shadow-sm">
            <div class="grid gap-6 xl:grid-cols-[1.25fr_0.75fr]">
                <div class="space-y-3">
                    <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]">
                        Order Admin
                    </span>
                    <div class="space-y-2">
                        <h1 class="text-3xl font-bold tracking-tight">Edit {{ $order->nomor_order }}</h1>
                        <p class="max-w-2xl text-sm leading-7 text-blue-100">
                            Sesuaikan data pekerjaan, prioritas, atau target penyelesaian agar tetap sinkron dengan kondisi order terbaru.
                        </p>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/10 bg-white/10 p-5">
                    <div class="space-y-3 text-sm text-blue-100">
                        <div>
                            <div class="text-xs uppercase tracking-[0.16em] text-blue-200">Dokumen</div>
                            <div class="mt-1 text-base font-semibold text-white">{{ $order->documentCompletionRatio() }}</div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-[0.16em] text-blue-200">Prioritas</div>
                            <div class="mt-1 text-base font-semibold text-white">{{ $order->priorityLabel() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="space-y-6">
            @method('PUT')
            @include('admin.orders.partials.form', ['submitLabel' => 'Perbarui Order'])
        </form>
    </div>
</x-layouts.admin>
