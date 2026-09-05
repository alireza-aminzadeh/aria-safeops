import { permitStatus, mocStatus, incidentStatus } from '../lib/labels';

const tone: Record<string, string> = {
  draft: 'bg-panel-2 text-muted',
  submitted: 'bg-ember/20 text-ember',
  hse_review: 'bg-ember/20 text-ember',
  approved: 'bg-mint/20 text-mint',
  active: 'bg-mint/25 text-mint',
  suspended: 'bg-rust/20 text-rust',
  closed: 'bg-panel-2 text-muted',
  cancelled: 'bg-panel-2 text-muted',
  rejected: 'bg-rust/25 text-rust',
  proposed: 'bg-panel-2 text-muted',
  risk_assessment: 'bg-ember/20 text-ember',
  approval: 'bg-ember/20 text-ember',
  implementation: 'bg-mint/20 text-mint',
  pssr: 'bg-mint/25 text-mint',
  reported: 'bg-ember/20 text-ember',
  under_investigation: 'bg-ember/20 text-ember',
  capa_assigned: 'bg-mint/20 text-mint',
};

export function StatusBadge({ value, kind = 'permit' }: { value: string; kind?: 'permit' | 'moc' | 'incident' }) {
  const labels = kind === 'moc' ? mocStatus : kind === 'incident' ? incidentStatus : permitStatus;
  return (
    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs ${tone[value] ?? 'bg-panel-2 text-muted'}`}>
      {labels[value] ?? value}
    </span>
  );
}
