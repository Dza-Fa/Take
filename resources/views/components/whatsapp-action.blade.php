@props([
    'borrowing',
    'type',
    'label',
])

<div
    x-data="{
        busy: false,
        error: '',
        feedback: '',
        async sendMessage() {
            if (this.busy) {
                return;
            }

            const tab = window.open('about:blank', '_blank');

            if (! tab) {
                this.error = 'Izinkan tab baru untuk membuka WhatsApp.';
                return;
            }

            this.busy = true;
            this.error = '';
            this.feedback = '';

            try {
                const response = await fetch(@js(route('api.borrowings.whatsapp.send', $borrowing->booking_code)), {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ type: @js($type) }),
                });
                const payload = await response.json();

                if (! response.ok || ! payload.success) {
                    throw new Error(payload.message || 'Pesan WhatsApp tidak dapat disiapkan.');
                }

                if (payload.data.sent) {
                    tab.close();
                    this.feedback = payload.message;
                } else if (payload.data.wa_url) {
                    tab.location = payload.data.wa_url;
                } else {
                    tab.close();
                    throw new Error('Tautan WhatsApp tidak tersedia.');
                }
            } catch (error) {
                tab.close();
                this.error = error.message || 'Koneksi tidak tersedia. Silakan coba kembali.';
            } finally {
                this.busy = false;
            }
        },
    }"
>
    <button
        type="button"
        @click="sendMessage()"
        :disabled="busy"
        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-200 px-4 py-2.5 text-sm font-bold text-emerald-800 transition hover:bg-emerald-50 disabled:cursor-wait disabled:opacity-60"
    >
        <span x-show="! busy">{{ $label }}</span>
        <span x-cloak x-show="busy">Menyiapkan WhatsApp…</span>
    </button>
    <p x-cloak x-show="error" x-text="error" class="mt-2 text-xs font-medium text-rose-700" role="alert"></p>
    <p x-cloak x-show="feedback" x-text="feedback" class="mt-2 text-xs font-medium text-emerald-700" role="status"></p>
</div>
