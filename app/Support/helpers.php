<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Shortcut untuk mendapatkan user yang sedang login
 *
 * @return \App\Models\User|null
 */
function user(): ?User
{
    return Auth::user();
}

if (!function_exists('wa_session_set')) {
    function wa_session_set(string $phone, array $data, int $ttlSeconds = 600): void
    {
        // Normalize phone key
        $key = 'wa_session:' . preg_replace('/[^0-9]/', '', $phone);
        cache()->put($key, $data, $ttlSeconds);
    }
}

if (!function_exists('wa_session_get')) {
    function wa_session_get(string $phone): ?array
    {
        $key = 'wa_session:' . preg_replace('/[^0-9]/', '', $phone);
        return cache()->get($key);
    }
}

if (!function_exists('wa_session_forget')) {
    function wa_session_forget(string $phone): void
    {
        $key = 'wa_session:' . preg_replace('/[^0-9]/', '', $phone);
        cache()->forget($key);
    }
}

// Manage multiple pending letters per phone (for race conditions)
if (!function_exists('wa_multi_session_add_letter')) {
    function wa_multi_session_add_letter(string $phone, int $letterId, int $ttlSeconds = 1800): void
    {
        $norm = preg_replace('/[^0-9]/', '', $phone);
        $key = 'wa_session_multi:' . $norm;
        $data = cache()->get($key) ?? ['active_letter_id' => null, 'letters' => []];
        if (!in_array($letterId, $data['letters'], true)) {
            $data['letters'][] = $letterId;
        }
        // Set newly added letter active if none active
        if (!$data['active_letter_id']) {
            $data['active_letter_id'] = $letterId;
        }
        // Keep only last 5 letters
        $data['letters'] = array_slice($data['letters'], -5);
        cache()->put($key, $data, $ttlSeconds);
    }
}
if (!function_exists('wa_multi_session_get')) {
    function wa_multi_session_get(string $phone): ?array
    {
        $key = 'wa_session_multi:' . preg_replace('/[^0-9]/', '', $phone);
        return cache()->get($key);
    }
}
if (!function_exists('wa_multi_session_set_active')) {
    function wa_multi_session_set_active(string $phone, int $letterId): void
    {
        $key = 'wa_session_multi:' . preg_replace('/[^0-9]/', '', $phone);
        $data = cache()->get($key);
        if (!$data) return;
        if (in_array($letterId, $data['letters'], true)) {
            $data['active_letter_id'] = $letterId;
            cache()->put($key, $data, 1800);
        }
    }
}

if (!function_exists('wa_multi_session_remove_letter')) {
    /**
     * Hapus satu surat dari daftar multi-session (misal setelah selesai diproses).
     * Jika surat yang dihapus adalah yang aktif, otomatis set aktif ke yang pertama tersisa.
     */
    function wa_multi_session_remove_letter(string $phone, int $letterId): void
    {
        $norm = preg_replace('/[^0-9]/', '', $phone);
        $key  = 'wa_session_multi:' . $norm;
        $data = cache()->get($key);
        if (!$data) return;
        $data['letters'] = array_values(array_filter($data['letters'], fn($id) => $id !== $letterId));
        if ($data['active_letter_id'] === $letterId) {
            $data['active_letter_id'] = $data['letters'][0] ?? null;
        }
        if (empty($data['letters'])) {
            cache()->forget($key);
        } else {
            cache()->put($key, $data, 1800);
        }
    }
}

if (!function_exists('wa_session_is_mid_flow')) {
    /**
     * Cek apakah user sedang di tengah alur aktif (bukan idle/notif awal).
     * Phase "idle" dianggap aman untuk ditimpa oleh notifikasi surat baru.
     */
    function wa_session_is_mid_flow(?array $session): bool
    {
        if (!$session) return false;
        $safePhases = [null, 'template_sent', 'template_sent_manual', 'switched', 'awaiting_action'];
        return !in_array($session['phase'] ?? null, $safePhases, true);
    }
}

if (!function_exists('wa_session_save_snapshot')) {
    /**
     * Simpan snapshot session saat ini agar bisa di-restore setelah konfirmasi GANTI.
     */
    function wa_session_save_snapshot(string $phone): void
    {
        $norm    = preg_replace('/[^0-9]/', '', $phone);
        $current = cache()->get('wa_session:' . $norm);
        if ($current) {
            cache()->put('wa_session_snap:' . $norm, $current, 300); // 5 menit
        }
    }
}

if (!function_exists('wa_session_restore_snapshot')) {
    /**
     * Restore session dari snapshot dan hapus snapshot-nya.
     */
    function wa_session_restore_snapshot(string $phone): bool
    {
        $norm = preg_replace('/[^0-9]/', '', $phone);
        $snap = cache()->get('wa_session_snap:' . $norm);
        if (!$snap) return false;
        cache()->put('wa_session:' . $norm, $snap, 600);
        cache()->forget('wa_session_snap:' . $norm);
        return true;
    }
}

if (!function_exists('wa_session_forget_snapshot')) {
    function wa_session_forget_snapshot(string $phone): void
    {
        $norm = preg_replace('/[^0-9]/', '', $phone);
        cache()->forget('wa_session_snap:' . $norm);
    }
}

// Simple rate limit helpers (fixed window 60s)
if (!function_exists('wa_rate_limit_hit')) {
    function wa_rate_limit_hit(string $phone, string $action): int
    {
        $norm = preg_replace('/[^0-9]/', '', $phone);
        $key = 'wa_rl:' . $action . ':' . $norm;
        $current = cache()->get($key, 0) + 1;
        cache()->put($key, $current, 60); // expire after 60s
        return $current;
    }
}
if (!function_exists('wa_rate_limit_exceeded')) {
    function wa_rate_limit_exceeded(string $phone, string $action, int $max): bool
    {
        $norm = preg_replace('/[^0-9]/', '', $phone);
        $key = 'wa_rl:' . $action . ':' . $norm;
        $count = cache()->get($key, 0);
        return $count >= $max;
    }
}

// ── Anti-spam: dedup by inboxid ──────────────────────────────────────────────
if (!function_exists('wa_dedup_seen')) {
    /**
     * Cek apakah inboxid ini sudah pernah diproses sebelumnya.
     * Return true → pesan duplikat, abaikan.
     */
    function wa_dedup_seen(string $phone, string $msgId): bool
    {
        $norm = preg_replace('/[^0-9]/', '', $phone);
        $key  = 'wa_dedup:' . $norm . ':' . md5($msgId);
        return cache()->has($key);
    }
}

if (!function_exists('wa_dedup_mark')) {
    /**
     * Tandai inboxid sebagai sudah diproses.
     */
    function wa_dedup_mark(string $phone, string $msgId, int $ttl = 60): void
    {
        $norm = preg_replace('/[^0-9]/', '', $phone);
        $key  = 'wa_dedup:' . $norm . ':' . md5($msgId);
        cache()->put($key, 1, $ttl);
    }
}

// ── Anti-spam: debounce per-user per-teks ────────────────────────────────────
if (!function_exists('wa_debounce_seen')) {
    /**
     * Cek apakah teks yang sama sudah dikirim user ini dalam window debounce.
     * Return true → terlalu cepat, abaikan.
     */
    function wa_debounce_seen(string $phone, string $text, int $windowSeconds = 5): bool
    {
        $norm = preg_replace('/[^0-9]/', '', $phone);
        $key  = 'wa_debounce:' . $norm . ':' . md5(mb_strtolower(trim($text)));
        return cache()->has($key);
    }
}

if (!function_exists('wa_debounce_mark')) {
    /**
     * Catat bahwa user mengirim teks ini sekarang (reset window).
     */
    function wa_debounce_mark(string $phone, string $text, int $windowSeconds = 5): void
    {
        $norm = preg_replace('/[^0-9]/', '', $phone);
        $key  = 'wa_debounce:' . $norm . ':' . md5(mb_strtolower(trim($text)));
        cache()->put($key, 1, $windowSeconds);
    }
}
