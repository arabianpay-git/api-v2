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
            // === إعدادات ثابتة لكل طلب ===
            static $cfg;
            static $memo = [];

            if (!$cfg) {
                $hosts = config('services.media_hosts', []);
                $cfg = [
                    'hosts'   => $hosts,
                    'resolve' => (bool) config('services.media_resolve_enabled', true),
                    'order'   => array_values(array_filter(array_map('trim', explode(',', config('services.media_resolve_order', 'partners,core'))))),
                    'fallback'=> $fallback ?? config('services.media_fallback', '/uploads/sliders/default_cover.png'),
                    'baseApp' => url('/'),
                ];
            }

            // ميمو-زة داخل نفس الطلب
            $key = $value.'|'.$cfg['fallback'];
            if (isset($memo[$key])) return $memo[$key];

            $ensure = static function (string $path, string $base) {
                if ($path === '') return rtrim($base, '/');
                if (stripos($path, 'http://') === 0 || stripos($path, 'https://') === 0) return $path;
                if (str_starts_with($path, '//')) return 'https:'.$path;
                return rtrim($base, '/') . '/' . ltrim($path, '/');
            };

            $hosts = $cfg['hosts'];

            // لا قيمة → رجّع الافتراضي على api (أو partners لو هذا المعتمد عندك)
            if (blank($value)) {
                return $memo[$key] = $ensure($cfg['fallback'], $hosts['api'] ?? $cfg['baseApp']);
            }

            $value = trim($value);

            // URL جاهزة
            if (stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0 || str_starts_with($value, '//')) {
                return $memo[$key] = (str_starts_with($value, '//') ? 'https:'.$value : $value);
            }

            // /uploads → partners (سريع، بدون شبكة)
            if (str_starts_with($value, 'uploads') || str_starts_with($value, '/uploads')) {
                return $memo[$key] = $ensure($value, $hosts['partners'] ?? $cfg['baseApp']);
            }

            // /storage → إمّا قاعدة ثابتة أو فحص محدود مع كاش
            if (str_starts_with($value, 'storage') || str_starts_with($value, '/storage')) {
                $cacheKey = 'media_resolve:'.md5($value);

                // كاش إيجابي/سلبي
                $hostKey = Cache::get($cacheKey);
                if ($hostKey && ($hostKey === 'none' || isset($hosts[$hostKey]))) {
                    $base = $hostKey === 'none' ? ($hosts[$cfg['order'][0]] ?? ($hosts['core'] ?? $cfg['baseApp'])) : $hosts[$hostKey];
                    return $memo[$key] = $ensure($value, $base);
                }

                // إن لزم الفحص الشبكي
                if ($cfg['resolve']) {
                    // جرّب أول هوستين كحد أقصى مع مهلة صغيرة
                    $tryList = array_slice($cfg['order'], 0, 2);
                    foreach ($tryList as $hk) {
                        $base = $hosts[$hk] ?? null;
                        if (!$base) continue;

                        $url = $ensure($value, $base);
                        try {
                            $res = Http::withOptions([
                                    'http_errors'     => false,
                                    'timeout'         => 0.35,
                                    'connect_timeout' => 0.2,
                                ])->head($url);

                            if ($res->successful()) {
                                Cache::put($cacheKey, $hk, now()->addDays(7)); // أطول
                                return $memo[$key] = $url;
                            }
                        } catch (\Throwable $e) {
                            // تجاهل
                        }
                    }

                    // كاش سلبي لمنع إعادة المحاولة بكثرة
                    Cache::put($cacheKey, 'none', now()->addHours(6));
                }

                // بدون فحص أو بعد فشل الفحص: اختَر أول ترتيب أو core
                $fallbackBase = $hosts[$cfg['order'][0]] ?? ($hosts['core'] ?? $cfg['baseApp']);
                return $memo[$key] = $ensure($value, $fallbackBase);
            }

            // أي مسار آخر → core
            return $memo[$key] = $ensure($value, $hosts['core'] ?? $cfg['baseApp']);
        }

    }
}
