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
            $base = $hosts[$preferred] ?? $hosts['partners'] ?? url('/');
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

    if (!function_exists('media_url_guess')) {
        /**
         * يحوّل القيمة المخزّنة إلى URL صالح:
         * - إن كانت URL كاملة: تُعاد كما هي
         * - إن كانت /uploads/... → على api
         * - إن كانت /storage/... → يجرّب partners ثم core (أو حسب الترتيب في env) عبر HEAD ويكاش النتيجة
         * - فارغة → fallback
         */
        function media_url_guess(?string $value, ?string $fallback = null): string
        {
            $hosts    = config('services.media_hosts', []);
            $fallback = $fallback ?? config('services.media_fallback', '/uploads/sliders/default_cover.png');
            $order    = array_filter(array_map('trim', explode(',', config('services.media_resolve_order', 'partners,core'))));
            $resolve  = (bool) config('services.media_resolve_enabled', true);

            $ensure = function (string $path, string $base) {
                if (preg_match('#^https?://#i', $path)) return $path;
                if (Str::startsWith($path, '//')) return 'https:'.$path;
                return rtrim($base, '/') . '/' . ltrim($path, '/');
            };

            // لا قيمة: رجّع الافتراضي على api
            if (blank($value)) {
                return $ensure($fallback, $hosts['api'] ?? url('/'));
            }

            $value = trim($value);

            // URL كاملة: كما هي
            if (preg_match('#^https?://#i', $value) || Str::startsWith($value, '//')) {
                return Str::startsWith($value, '//') ? 'https:'.$value : $value;
            }

            // /uploads → api
            if (Str::startsWith($value, ['uploads', '/uploads'])) {
                return $ensure($value, $hosts['partners'] ?? url('/'));
            }

            // /storage → جرّب الترتيب مع HEAD + كاش
            if (Str::startsWith($value, ['storage','/storage'])) {
                $cacheKey = 'media_resolve:'.md5($value);
                if ($hostKey = Cache::get($cacheKey)) {
                    if (isset($hosts[$hostKey])) return $ensure($value, $hosts[$hostKey]);
                }

                if ($resolve) {
                    foreach ($order as $hostKey) {
                        $base = $hosts[$hostKey] ?? null;
                        if (!$base) continue;

                        $url = $ensure($value, $base);
                        try {
                            $res = Http::timeout(2)->head($url);
                            if ($res->successful()) {
                                Cache::put($cacheKey, $hostKey, now()->addDay());
                                return $url;
                            }
                        } catch (\Throwable $e) {
                            // تجاهل وجرّب التالي
                        }
                    }
                }

                // فشل الفحص: اختَر أول ترتيب أو core
                $fallbackBase = $hosts[$order[0]] ?? ($hosts['core'] ?? url('/'));
                return $ensure($value, $fallbackBase);
            }

            // أي مسار آخر: استخدم core كافتراضي
            return $ensure($value, $hosts['core'] ?? url('/'));
        }
    }
}
