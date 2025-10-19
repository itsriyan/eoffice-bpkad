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
