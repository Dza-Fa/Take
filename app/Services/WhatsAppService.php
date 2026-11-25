<?php

namespace App\Services;

use App\Models\Borrowing;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WhatsAppService
{
    /**
     * @return array{phone: string, text: string, wa_url: string}
     */
    public function preview(Borrowing $borrowing, string $type): array
    {
        $borrowing->loadMissing(['user', 'asset']);

        [$phone, $text] = match ($type) {
            'new_request' => [
                $this->configuredAdminPhone(),
                $this->newRequestMessage($borrowing),
            ],
            'status' => [
                $borrowing->user->phone,
                $this->statusMessage($borrowing),
            ],
            'reminder' => [
                $borrowing->user->phone,
                $this->returnReminderMessage($borrowing),
            ],
            default => throw new InvalidArgumentException('Jenis pesan WhatsApp tidak valid.'),
        };

        $normalizedPhone = $this->normalizePhone($phone);

        return [
            'phone' => $normalizedPhone,
            'text' => $text,
            'wa_url' => 'https://wa.me/'.$normalizedPhone.'?text='.rawurlencode($text),
        ];
    }

    /**
     * @return array{phone: string, text: string, wa_url: string, sent: bool, delivery: string}
     */
    public function send(Borrowing $borrowing, string $type): array
    {
        $preview = $this->preview($borrowing, $type);
        $apiUrl = trim((string) config('services.whatsapp.api_url'));
        $apiToken = trim((string) config('services.whatsapp.api_token'));

        if ($apiUrl === '' || $apiToken === '') {
            return [...$preview, 'sent' => false, 'delivery' => 'wa_link'];
        }

        if (filter_var($apiUrl, FILTER_VALIDATE_URL) === false
            || parse_url($apiUrl, PHP_URL_SCHEME) !== 'https') {
            Log::warning('WhatsApp gateway URL must be a valid HTTPS URL.');

            return [...$preview, 'sent' => false, 'delivery' => 'wa_link'];
        }

        try {
            $response = Http::acceptJson()
                ->withToken($apiToken)
                ->connectTimeout(2)
                ->timeout(5)
                ->post($apiUrl, [
                    'phone' => $preview['phone'],
                    'message' => $preview['text'],
                ]);
        } catch (ConnectionException) {
            Log::warning('WhatsApp gateway connection failed.');

            return [...$preview, 'sent' => false, 'delivery' => 'wa_link'];
        }

        if (! $response->successful()) {
            Log::warning('WhatsApp gateway returned an unsuccessful response.', [
                'status' => $response->status(),
            ]);

            return [...$preview, 'sent' => false, 'delivery' => 'wa_link'];
        }

        return [...$preview, 'sent' => true, 'delivery' => 'gateway'];
    }

    public function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone ?? '') ?? '';

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        if (preg_match('/^628\d{8,11}$/', $digits) !== 1) {
            throw new InvalidArgumentException('Nomor WhatsApp tidak valid atau belum diisi.');
        }

        return $digits;
    }

    public function newRequestMessage(Borrowing $borrowing): string
    {
        $borrowing->loadMissing(['user', 'asset']);

        return implode("\n", [
            'Halo Pengurus Take, ada pengajuan peminjaman aset baru.',
            '',
            'Kode pengajuan: '.$borrowing->booking_code,
            'Nama peminjam: '.$borrowing->user->name,
            'Alamat/RT-RW: '.($borrowing->user->address ?: 'Belum diisi'),
            'Aset: '.$borrowing->asset->name.' ('.$borrowing->asset->code.')',
            'Jumlah: '.$borrowing->quantity.' unit',
            'Tanggal pinjam: '.$borrowing->borrow_date->format('d/m/Y'),
            'Tanggal kembali: '.$borrowing->return_date->format('d/m/Y'),
            'Keperluan: '.$borrowing->purpose,
            '',
            'Mohon ditinjau melalui aplikasi Take. Terima kasih.',
        ]);
    }

    public function statusMessage(Borrowing $borrowing): string
    {
        $borrowing->loadMissing(['user', 'asset']);

        $statusLabels = [
            'approved' => 'disetujui',
            'borrowed' => 'barang telah diserahkan',
            'rejected' => 'ditolak',
        ];

        if (! isset($statusLabels[$borrowing->status])) {
            throw new InvalidArgumentException('Status peminjaman belum dapat dikirim melalui WhatsApp.');
        }

        $lines = [
            'Halo '.$borrowing->user->name.', informasi terbaru peminjaman aset Take:',
            '',
            'Kode peminjaman: '.$borrowing->booking_code,
            'Aset: '.$borrowing->asset->name,
            'Status: '.$statusLabels[$borrowing->status],
            'Tanggal pinjam: '.$borrowing->borrow_date->format('d/m/Y'),
            'Tanggal kembali: '.$borrowing->return_date->format('d/m/Y'),
        ];

        if ($borrowing->admin_note !== null && $borrowing->admin_note !== '') {
            $lines[] = 'Catatan pengurus: '.$borrowing->admin_note;
        }

        $lines[] = '';
        $lines[] = 'Silakan hubungi pengurus jika ada pertanyaan. Terima kasih.';

        return implode("\n", $lines);
    }

    public function returnReminderMessage(Borrowing $borrowing): string
    {
        $borrowing->loadMissing(['user', 'asset']);

        if (! in_array($borrowing->status, ['approved', 'borrowed'], true)) {
            throw new InvalidArgumentException('Pengingat hanya tersedia untuk peminjaman yang masih aktif.');
        }

        $opening = $borrowing->isOverdue()
            ? 'Pengingat: peminjaman Anda sudah melewati batas pengembalian.'
            : 'Pengingat: masa peminjaman aset Anda akan segera berakhir.';

        return implode("\n", [
            'Halo '.$borrowing->user->name.',',
            $opening,
            '',
            'Kode peminjaman: '.$borrowing->booking_code,
            'Aset: '.$borrowing->asset->name,
            'Jumlah: '.$borrowing->quantity.' unit',
            'Batas pengembalian: '.$borrowing->return_date->format('d/m/Y'),
            '',
            'Mohon menghubungi pengurus Take untuk mengatur pengembalian. Terima kasih.',
        ]);
    }

    private function configuredAdminPhone(): ?string
    {
        $phone = trim((string) config('services.whatsapp.admin_phone'));

        return $phone === '' ? null : $phone;
    }
}
