<x-labs.shell :title="__('idempotency.page.title')" :description="__('idempotency.page.description')">
    @php($formatMinor = static fn (int $amount): string => intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT))

    <main id="main-content" class="lab-page mx-auto px-4 py-8 sm:px-6 lg:px-10 lg:py-12">
        <a href="{{ route('dashboard') }}" class="inline-flex text-sm font-semibold text-cyan-700 hover:text-cyan-900">
            {{ __('idempotency.page.back') }}
        </a>

        <header class="lab-reading-column mt-6">
            <div class="flex flex-wrap items-center gap-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-cyan-700">
                <span>BL-08</span><span class="text-slate-300">/</span>
                <span>{{ __('idempotency.page.eyebrow') }}</span>
                <span class="rounded-full bg-amber-100 px-2.5 py-1 tracking-normal text-amber-800">
                    {{ __('idempotency.page.level') }}
                </span>
            </div>
            <h1 class="mt-4 max-w-5xl text-3xl font-semibold leading-tight tracking-[-0.04em] sm:text-5xl">
                {{ __('idempotency.page.heading') }}
            </h1>
            <p class="lab-card-copy mt-5 text-base leading-7 text-slate-600">
                {{ __('idempotency.page.description') }}
            </p>
        </header>

        <x-labs.learning-overview lab="idempotency" />

        <section id="idempotency-examples" class="mt-8" aria-labelledby="idempotency-examples-title">
            <div class="max-w-3xl">
                <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-700">
                    {{ __('lab_learning.idempotency.examples_label') }}
                </p>
                <h2 id="idempotency-examples-title" class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">
                    {{ __('lab_learning.idempotency.examples_title') }}
                </h2>
            </div>
            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                @foreach (__('lab_learning.idempotency.examples') as $example)
                    <article class="lab-card overflow-hidden bg-slate-950 text-white">
                        <div class="p-5">
                            <h3 class="text-base font-semibold">{{ $example['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-300">{{ $example['description'] }}</p>
                        </div>
                        <pre class="lab-code-block overflow-x-auto border-t border-white/10 bg-black/20 p-5 font-mono text-xs leading-6 text-cyan-100"><code>{{ $example['code'] }}</code></pre>
                    </article>
                @endforeach
            </div>
        </section>

        <x-labs.production-learning lab="idempotency" phase="evidence" />

        <section id="idempotency-tradeoffs" class="mt-8 grid gap-6 lg:grid-cols-2">
            <article class="lab-card bg-amber-50 p-6 sm:p-8">
                <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-amber-700">
                    {{ __('lab_learning.idempotency.tradeoff_label') }}
                </p>
                <h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">
                    {{ __('lab_learning.idempotency.tradeoff_title') }}
                </h2>
                <div class="mt-6 grid gap-3">
                    @foreach (__('lab_learning.idempotency.tradeoffs') as $tradeoff)
                        <article class="lab-tradeoff-card {{ $tradeoff['class'] }}">
                            <h3 class="text-sm font-semibold text-slate-900">{{ $tradeoff['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-700">{{ $tradeoff['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </article>
            <article id="idempotency-threats" class="lab-card bg-slate-950 p-6 text-white sm:p-8">
                <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-rose-300">
                    {{ __('lab_learning.idempotency.threat_label') }}
                </p>
                <h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">
                    {{ __('lab_learning.idempotency.threat_title') }}
                </h2>
                <ul class="lab-review-list mt-6 text-slate-300">
                    @foreach (__('lab_learning.idempotency.threats') as $threat)
                        <li>{{ $threat }}</li>
                    @endforeach
                </ul>
            </article>
        </section>

        <x-labs.production-learning lab="idempotency" phase="tail" />

        <section id="idempotency-review" class="mt-8 rounded-2xl bg-emerald-50 p-6 sm:p-8">
            <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-emerald-700">
                {{ __('lab_learning.idempotency.review_label') }}
            </p>
            <h2 class="mt-2 text-2xl font-semibold leading-tight sm:text-3xl">
                {{ __('lab_learning.idempotency.review_title') }}
            </h2>
            <ul class="lab-severity mt-5 text-slate-700 sm:grid-cols-2">
                @foreach (__('lab_learning.idempotency.review') as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </section>

        <div class="my-10 flex items-center gap-4" aria-hidden="true">
            <div class="h-px flex-1 bg-slate-200"></div>
            <span class="rounded-full bg-white px-4 py-1.5 font-mono text-[10px] font-semibold uppercase tracking-widest text-slate-500">
                {{ __('idempotency.practice.divider') }}
            </span>
            <div class="h-px flex-1 bg-slate-200"></div>
        </div>

        <section id="idempotency-practice" aria-labelledby="idempotency-practice-title">
            <div class="max-w-3xl">
                <p class="font-mono text-[10px] font-semibold uppercase tracking-wider text-cyan-700">
                    {{ __('idempotency.practice.label') }}
                </p>
                <h2 id="idempotency-practice-title" class="mt-2 text-3xl font-semibold tracking-[-0.03em]">
                    {{ __('idempotency.practice.title') }}
                </h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    {{ __('idempotency.practice.description') }}
                </p>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <section class="rounded-2xl bg-white p-5 shadow-sm sm:p-6" aria-label="{{ __('idempotency.practice.title') }}">
                    <form id="idempotency-order-form" method="POST" action="{{ route('labs.idempotency.orders.store') }}" class="grid gap-4">
                        @csrf
                        <label for="idempotency-product" class="text-xs font-semibold text-slate-600">
                            {{ __('idempotency.practice.product') }}
                            <select id="idempotency-product" name="product_code" required class="mt-1.5 block h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                                @foreach ($products as $product)
                                    <option value="{{ $product->value }}">{{ __('idempotency.products.'.$product->value) }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label for="idempotency-quantity" class="text-xs font-semibold text-slate-600">
                            {{ __('idempotency.practice.quantity') }}
                            <input id="idempotency-quantity" name="quantity" type="number" min="1" max="50" value="1" required class="mt-1.5 block h-11 w-full rounded-lg border border-slate-200 px-3 text-sm focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                            <span class="mt-1 block font-normal text-slate-500">{{ __('idempotency.practice.quantity_hint') }}</span>
                        </label>

                        <label for="idempotency-key" class="text-xs font-semibold text-slate-600">
                            {{ __('idempotency.practice.key') }}
                            <input id="idempotency-key" type="text" value="{{ $idempotencyKey }}" maxlength="255" autocomplete="off" spellcheck="false" required class="mt-1.5 block h-11 w-full rounded-lg border border-slate-200 px-3 font-mono text-sm focus:border-cyan-600 focus:ring-4 focus:ring-cyan-100">
                            <span class="mt-1 block font-normal text-slate-500">{{ __('idempotency.practice.key_hint') }}</span>
                        </label>

                        <div class="flex flex-wrap gap-3">
                            <button id="idempotency-submit" type="submit" class="h-11 rounded-lg bg-[#102a43] px-4 text-sm font-semibold text-white hover:bg-[#173f60] focus:outline-none focus:ring-4 focus:ring-cyan-200">
                                {{ __('idempotency.practice.submit') }}
                            </button>
                            <button id="idempotency-new-key" type="button" class="h-11 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                {{ __('idempotency.practice.new_key') }}
                            </button>
                        </div>
                    </form>
                    <noscript>
                        <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900">{{ __('idempotency.practice.noscript') }}</p>
                    </noscript>

                    <section class="mt-6 rounded-xl bg-slate-950 p-4 text-white" aria-labelledby="idempotency-response-title">
                        <h3 id="idempotency-response-title" class="text-sm font-semibold">{{ __('idempotency.practice.response_title') }}</h3>
                        <p class="mt-1 text-xs leading-5 text-slate-300">{{ __('idempotency.practice.response_hint') }}</p>
                        <div id="idempotency-response" class="mt-4 rounded-lg bg-white/5 p-3 text-sm" aria-live="polite" tabindex="-1">
                            <p id="idempotency-response-summary" class="text-cyan-100"></p>
                            <pre id="idempotency-response-body" class="lab-code-block mt-3 hidden overflow-x-auto font-mono text-xs leading-5 text-slate-200"></pre>
                        </div>
                    </section>
                </section>

                <aside class="rounded-2xl bg-cyan-50 p-5 sm:p-6" aria-labelledby="idempotency-orders-title">
                    <h3 id="idempotency-orders-title" class="text-xl font-semibold">{{ __('idempotency.practice.orders') }}</h3>
                    @forelse ($orders as $order)
                        <article class="mt-3 rounded-xl bg-white/80 p-4">
                            <p class="text-sm font-semibold text-slate-900">
                                {{ __('idempotency.practice.order_row', ['id' => $order->id, 'product' => __('idempotency.products.'.$order->product_code->value), 'quantity' => $order->quantity, 'currency' => $order->currency, 'amount' => $formatMinor($order->total_minor), 'status' => __('idempotency.practice.status_'.$order->status->value)]) }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">{{ $order->created_at?->format('Y-m-d H:i:s') }} UTC</p>
                        </article>
                    @empty
                        <p class="mt-4 text-sm text-slate-600">{{ __('idempotency.practice.empty_orders') }}</p>
                    @endforelse
                </aside>
            </div>
        </section>
    </main>

    @push('scripts')
        <script>
            (() => {
                const form = document.getElementById('idempotency-order-form');
                const keyInput = document.getElementById('idempotency-key');
                const output = document.getElementById('idempotency-response');
                const summary = document.getElementById('idempotency-response-summary');
                const responseBody = document.getElementById('idempotency-response-body');
                const labels = {
                    replayed: @json(__('idempotency.practice.replayed')),
                    firstResponse: @json(__('idempotency.practice.first_response')),
                    requestError: @json(__('idempotency.practice.request_error')),
                    httpStatus: @json(__('idempotency.practice.http_status')),
                    networkError: @json(__('idempotency.practice.network_error')),
                };

                document.getElementById('idempotency-new-key').addEventListener('click', () => {
                    keyInput.value = crypto.randomUUID();
                    keyInput.focus();
                });

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const formData = new FormData(form);
                    const key = keyInput.value.trim();
                    const csrfToken = formData.get('_token');
                    const payload = {
                        product_code: formData.get('product_code'),
                        quantity: Number(formData.get('quantity')),
                    };
                    const controls = [...form.elements];

                    controls.forEach((control) => { control.disabled = true; });
                    form.setAttribute('aria-busy', 'true');
                    summary.textContent = '';
                    responseBody.classList.add('hidden');

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Idempotency-Key': key,
                            },
                            body: JSON.stringify(payload),
                        });
                        const responseText = await response.text();
                        let body;

                        try {
                            body = JSON.parse(responseText);
                        } catch {
                            body = { message: labels.networkError };
                        }

                        const replayed = response.headers.get('Idempotency-Replayed') === 'true';
                        const outcome = replayed ? labels.replayed : (response.ok ? labels.firstResponse : labels.requestError);
                        summary.textContent = `${labels.httpStatus}: ${response.status} · ${outcome}`;
                        responseBody.textContent = JSON.stringify(body, null, 2);
                        responseBody.classList.remove('hidden');
                        output.setAttribute('role', response.ok ? 'status' : 'alert');
                    } catch {
                        summary.textContent = labels.networkError;
                        output.setAttribute('role', 'alert');
                    } finally {
                        controls.forEach((control) => { control.disabled = false; });
                        form.removeAttribute('aria-busy');
                        output.focus();
                    }
                });
            })();
        </script>
    @endpush
</x-labs.shell>
