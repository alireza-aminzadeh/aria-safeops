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

export const incidentType: Record<string, string> = {
  near_miss: 'شبه‌حادثه',
  incident: 'حادثه',
  injury: 'آسیب',
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
