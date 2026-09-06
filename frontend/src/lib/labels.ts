export const permitStatus: Record<string, string> = {
  draft: 'پیش‌نویس',
  submitted: 'ارسال‌شده',
  hse_review: 'بررسی HSE',
  approved: 'تأییدشده',
  active: 'فعال',
  suspended: 'تعلیق',
  closed: 'بسته‌شده',
  cancelled: 'لغو',
  rejected: 'ردشده',
};

export const mocStatus: Record<string, string> = {
  proposed: 'پیشنهاد',
  risk_assessment: 'ارزیابی ریسک',
  approval: 'تأیید',
  implementation: 'اجرا',
  pssr: 'PSSR',
  closed: 'بسته',
  rejected: 'رد',
};

export const incidentStatus: Record<string, string> = {
  reported: 'گزارش‌شده',
  under_investigation: 'در حال بررسی',
  capa_assigned: 'اقدام اصلاحی',
  closed: 'بسته',
};

export const shiftStatus: Record<string, string> = {
  draft: 'پیش‌نویس',
  submitted: 'ارسال‌شده',
  accepted: 'پذیرفته',
};

export const visionType: Record<string, string> = {
  missing_ppe: 'نقص PPE',
  restricted_zone: 'ناحیهٔ ممنوعه',
};

export const incidentType: Record<string, string> = {
  near_miss: 'شبه‌حادثه',
  incident: 'حادثه',
  injury: 'آسیب',
};

export const rootCauseCategory: Record<string, string> = {
  human_factor: 'عامل انسانی',
  procedure_gap: 'نقص دستورالعمل',
  equipment_failure: 'خرابی تجهیز',
  design: 'نقص طراحی',
  training: 'کمبود آموزش',
  management_system: 'نظام مدیریت HSE',
  external: 'عامل بیرونی',
};

export const permitTransitions: Record<string, string> = {
  submit: 'ارسال',
  start_review: 'شروع بررسی',
  request_changes: 'درخواست اصلاح',
  approve: 'تأیید',
  reject: 'رد',
  activate: 'فعال‌سازی',
  suspend: 'تعلیق',
  resume: 'ازسرگیری',
  close: 'بستن',
  cancel: 'لغو',
};

export const mocTransitions: Record<string, string> = {
  start_risk_assessment: 'ارزیابی ریسک',
  submit_for_approval: 'ارسال برای تأیید',
  approve: 'تأیید',
  reject: 'رد',
  start_pssr: 'شروع PSSR',
  close: 'بستن',
};

export const changeType: Record<string, string> = {
  temporary: 'موقت',
  permanent: 'دائم',
  emergency: 'اضطراری',
};

export const psmRating: Record<string, string> = {
  compliant: 'منطبق',
  partial: 'نسبی',
  non_compliant: 'غیرمنطبق',
  not_applicable: 'قابل‌اجرا نیست',
};

export const psmAuditStatus: Record<string, string> = {
  draft: 'پیش‌نویس',
  in_progress: 'در حال انجام',
  completed: 'تکمیل‌شده',
};

export const scenarioType: Record<string, string> = {
  fire: 'آتش‌سوزی',
  gas_release: 'نشت گاز',
  spill: 'ریزش/نشت مایع',
  medical: 'پزشکی',
  security: 'امنیتی',
  natural_disaster: 'حوادث طبیعی',
};

export function faDate(value?: string | null): string {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString('fa-IR');
}

export function sumCounts(record: Record<string, number> | undefined, keys?: string[]): number {
  const source = record ?? {};
  const selected = keys ?? Object.keys(source);
  return selected.reduce((total, key) => total + (source[key] ?? 0), 0);
}
