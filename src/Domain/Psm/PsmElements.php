<?php

namespace App\Domain\Psm;

/**
 * ۱۴ عنصر مدیریت ایمنی فرآیند طبق OSHA 29 CFR 1910.119 — مبنای چک‌لیست ممیزی PSM.
 */
final class PsmElements
{
    /**
     * @return list<array{code: string, nameFa: string, nameEn: string}>
     */
    public static function all(): array
    {
        return [
            ['code' => 'employee_participation', 'nameFa' => 'مشارکت کارکنان', 'nameEn' => 'Employee Participation'],
            ['code' => 'process_safety_information', 'nameFa' => 'اطلاعات ایمنی فرآیند (PSI)', 'nameEn' => 'Process Safety Information'],
            ['code' => 'process_hazard_analysis', 'nameFa' => 'تحلیل خطر فرآیند (PHA/HAZOP)', 'nameEn' => 'Process Hazard Analysis'],
            ['code' => 'operating_procedures', 'nameFa' => 'دستورالعمل‌های عملیاتی', 'nameEn' => 'Operating Procedures'],
            ['code' => 'training', 'nameFa' => 'آموزش', 'nameEn' => 'Training'],
            ['code' => 'contractors', 'nameFa' => 'پیمانکاران', 'nameEn' => 'Contractors'],
            ['code' => 'pre_startup_safety_review', 'nameFa' => 'بازبینی ایمنی پیش از راه‌اندازی (PSSR)', 'nameEn' => 'Pre-Startup Safety Review'],
            ['code' => 'mechanical_integrity', 'nameFa' => 'یکپارچگی مکانیکی', 'nameEn' => 'Mechanical Integrity'],
            ['code' => 'hot_work_permit', 'nameFa' => 'مجوز کار گرم', 'nameEn' => 'Hot Work Permit'],
            ['code' => 'management_of_change', 'nameFa' => 'مدیریت تغییر (MOC)', 'nameEn' => 'Management of Change'],
            ['code' => 'incident_investigation', 'nameFa' => 'تحقیق حادثه', 'nameEn' => 'Incident Investigation'],
            ['code' => 'emergency_planning', 'nameFa' => 'برنامه‌ریزی و واکنش اضطراری', 'nameEn' => 'Emergency Planning and Response'],
            ['code' => 'compliance_audits', 'nameFa' => 'ممیزی انطباق', 'nameEn' => 'Compliance Audits'],
            ['code' => 'trade_secrets', 'nameFa' => 'اسرار تجاری', 'nameEn' => 'Trade Secrets'],
        ];
    }

    public static function count(): int
    {
        return count(self::all());
    }
}
