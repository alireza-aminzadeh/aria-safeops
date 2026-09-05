<?php

namespace App\Infrastructure\AiGateway;

final class HseKnowledgePack
{
    /**
     * @return list<array{id: string, title: string, citation: string, text: string}>
     */
    public static function documents(): array
    {
        return [
            [
                'id' => 'gas-test',
                'title' => 'گاز‌تست کار گرم و فضای بسته',
                'citation' => 'API 2015 / OSHA 1910.146 analogue — guidance',
                'text' => 'قبل از کار گرم یا ورود به فضای بسته باید گاز‌تست اکسیژن، LEL و H2S ثبت شود. حد اکسیژن معمولاً ۱۹.۵ تا ۲۳.۵ درصد، LEL زیر ۱۰ درصد برای ورود و صفر قابل‌قبول برای کار گرم، و H2S زیر ۱۰ ppm است. بدون ثبت گاز‌تست، فعال‌سازی مجوز کار گرم یا فضای بسته در ایمن‌کار مسدود است.',
            ],
            [
                'id' => 'loto',
                'title' => 'ایزولیشن و LOTO',
                'citation' => 'OSHA 1910.147 / site isolation procedure',
                'text' => 'برای کار روی انرژی خطرناک باید نقاط ایزولیشن قفل و برچسب شوند و تأیید ایزولیشن در مجوز ثبت گردد. ایمن‌کار برای انواع مجوز برق، حفاری و کار گرم تأیید LOTO را اجباری می‌کند. ایزولیشن ناقص یکی از علل رایج حادثه در تعمیرات است.',
            ],
            [
                'id' => 'moc-pssr',
                'title' => 'مدیریت تغییر و PSSR',
                'citation' => 'OSHA PSM 1910.119(l) / CCPS MOC',
                'text' => 'هر تغییر موقت، دائم یا اضطراری باید از مسیر MOC بگذرد. Pre-Startup Safety Review قبل از بسته‌شدن MOC الزامی است و در ایمن‌کار نمی‌توان PSSR را دور زد. HAZOP گره، انحراف، علت، پیامد و لایه‌های حفاظتی را به MOC وصل می‌کند.',
            ],
            [
                'id' => 'api-754',
                'title' => 'شاخص‌های ایمنی فرآیند API 754',
                'citation' => 'API RP 754 Process Safety Performance Indicators',
                'text' => 'Tier 1 و 2 رویدادهای پس‌رو (LOPC با پیامد جدی/خفیف) هستند. Tier 3 چالش سیستم‌های ایمنی و near-miss مهم است. Tier 4 شاخص‌های پیش‌رو مثل انضباط عملیاتی، گواهی منقضی، و HAZOP باز است. ایمن‌کار حوادث را روی این چهار لایه می‌شمارد.',
            ],
            [
                'id' => 'ppe-vision',
                'title' => 'PPE و ناحیهٔ ممنوعه',
                'citation' => 'OSHA 1910 Subpart I / site PPE matrix',
                'text' => 'ورود به ناحیهٔ فرآیندی بدون کلاه، عینک و در صورت نیاز H2S detector تخلف است. ماژول HSE Vision رویداد missing_ppe و restricted_zone را ثبت می‌کند. استنتاج روی GPU جدا انجام می‌شود؛ روی این سرور شبیه‌ساز یا ingest از سرویس Vision استفاده می‌شود.',
            ],
            [
                'id' => 'toolbox',
                'title' => 'Toolbox Talk و تحویل شیفت',
                'citation' => 'site HSE procedure — shift handover / TBT',
                'text' => 'هر شیفت باید مجوزهای باز، ایزولیشن فعال، و آنومالی تجهیز را تحویل دهد. Toolbox Talk موضوع، مکان، تعداد حاضران و نکات را ثبت می‌کند. لاگ‌بوک ساخت‌یافته جایگزین دفتر کاغذی سرپرست است.',
            ],
        ];
    }

    /**
     * @return list<array{id: string, title: string, citation: string, text: string}>
     */
    public static function retrieve(string $query, int $limit = 2): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query)) ?: [];
        $tokens = array_values(array_filter($tokens, static fn (string $t) => mb_strlen($t) >= 3));
        $docs = self::documents();
        if ($tokens === []) {
            return array_slice($docs, 0, $limit);
        }
        $scored = [];
        foreach ($docs as $doc) {
            $hay = mb_strtolower($doc['title'].' '.$doc['text'].' '.$doc['citation']);
            $score = 0;
            foreach ($tokens as $token) {
                if (str_contains($hay, $token)) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scored[] = ['score' => $score, 'doc' => $doc];
            }
        }
        usort($scored, static fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_map(static fn (array $row) => $row['doc'], array_slice($scored, 0, $limit));
    }
}
