<?php

use Illuminate\Support\Str;

if (!function_exists('media_url')) {
    /**
     * طبّع مسار صورة إلى URL مطلق.
     *
     * @param  string|null  $value      القيمة المخزنة في العمود
     * @param  string|null  $fallback   مسار/رابط بديل عند عدم وجود قيمة
     * @param  string       $preferred  المفضّل لمسارات storage: core|partners|api
     * @return string
     */
    function media_url(?string $value, ?string $fallback = null, string $preferred = 'core'): string
    {
        $hosts    = config('services.media_hosts', []);
        $fallback = $fallback ?? config('services.media_fallback', '/uploads/sliders/default_cover.png');

        // دالة مساعدة: تضمن أن أي مسار يصبح URL كامل
        $ensure = function (string $path, string $base) {
            if (preg_match('#^https?://#i', $path)) return $path;
            if (Str::startsWith($path, '//')) return 'https:' . $path;
            return rtrim($base, '/') . '/' . ltrim($path, '/');
        };

        // لا قيمة → رجّع الافتراضي
        if (blank($value)) {
            return $ensure($fallback, $hosts['api'] ?? url('/'));
        }

        $value = trim($value);

        // إن كان URL كامل أصلاً → رجّعه كما هو
        if (preg_match('#^https?://#i', $value) || Str::startsWith($value, '//')) {
            return $value[0] === '/' && $value[1] === '/' ? 'https:' . $value : $value;
        }

        // مسارات نسبية
        if (Str::startsWith($value, ['/uploads', 'uploads'])) {
            // الرفع التقليدي على api
            $base = $hosts['api'] ?? url('/');
            return $ensure($value, $base);
        }

        if (Str::startsWith($value, ['/storage', 'storage'])) {
            // وسائط storage/media → اختَر الدومين المفضل
            $base = $hosts[$preferred] ?? $hosts['core'] ?? url('/');
            return $ensure($value, $base);
        }

        // أي شيء آخر → استخدم المفضّل (افتراضي core)
        $base = $hosts[$preferred] ?? url('/');
        return $ensure($value, $base);
    }
}
