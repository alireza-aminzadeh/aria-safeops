export type PermitType = {
  id: string;
  code: string;
  nameFa: string;
  requiresGasTest?: boolean;
  requiresIsolation?: boolean;
};

export type Person = { id: string; fullName?: string; username?: string };

export type GasReading = {
  id: string;
  gasType: string;
  readingValue: string;
  recordedAt?: string;
};

export type Permit = {
  id: string;
  status: string;
  equipmentTag: string;
  locationPlotRef?: string | null;
  workDescription?: string;
  permitType?: PermitType;
  requestedBy?: Person | null;
  approvedBy?: Person | null;
  validFrom?: string | null;
  validTo?: string | null;
  isolationConfirmed?: boolean;
  isolationPoints?: string[];
  isolationConfirmedAt?: string | null;
  gasTestReadings?: GasReading[];
  createdAt?: string;
};

export type Moc = {
  id: string;
  status: string;
  description: string;
  changeType: string;
  equipmentTag?: string | null;
  pssrCompletedAt?: string | null;
  requestedBy?: Person | null;
};

export type Capa = {
  id: string;
  description: string;
  status: string;
  dueDate?: string | null;
};

export type Incident = {
  id: string;
  type: string;
  severity: string;
  status: string;
  description: string;
  location?: string | null;
  rcaNotes?: string | null;
  rootCauseWhys?: string[];
  rootCauseCategory?: string | null;
  lostDays?: number;
  recordable?: boolean;
  reportedAt?: string;
  capaActions?: Capa[];
};

export type SafetyPeriodMetric = {
  id: string;
  periodStart: string;
  periodEnd: string;
  hoursWorked: string;
  employeeCount?: number | null;
  createdAt?: string;
};

export type Certification = {
  id: string;
  type: string;
  expiresAt: string;
  expiringSoon?: boolean;
};

export type TrainingRecord = {
  id: string;
  courseCode: string;
  courseName: string;
  completedAt: string;
  expiresAt?: string | null;
  expired?: boolean;
};

export type Contractor = {
  id: string;
  companyName: string;
  hsePrequalificationScore?: string;
  certifications?: Certification[];
  trainingRecords?: TrainingRecord[];
};

export type Me = {
  id: string;
  username: string;
  email: string;
  fullName: string;
  roles: string[];
  tenantName?: string;
};

export type Dashboard = {
  permits: Record<string, number>;
  mocs: Record<string, number>;
  incidents: Record<string, number>;
  expiringCertifications: Array<{
    id: string;
    type: string;
    expiresAt: string;
    companyName: string;
    expiringSoon: boolean;
  }>;
};

export type Api754 = {
  tier1: number;
  tier2: number;
  tier3: number;
  tier4: number;
  pseRatePerMillionHours: number;
  safety: {
    hoursWorkedTtm: number | null;
    lostTimeInjuriesTtm: number;
    recordableCountTtm: number;
    ltifr: number | null;
    trir: number | null;
  };
  leading: {
    openHighHazop: number;
    expiredCertifications: number;
    toolboxTalksThisMonth: number;
    openVisionEvents: number;
  };
};

export type HazopItem = {
  id: string;
  equipmentTag?: string | null;
  nodeDescription: string;
  deviation: string;
  cause: string;
  consequence: string;
  safeguards: string;
  riskRanking: string;
  status: string;
  lopa: Array<{ id: string; initiatingEvent: string; iplCount: number; targetFrequency: string; residualRisk: string }>;
  barriers: Array<{ id: string; side: string; description: string; effectiveness: string; status: string }>;
};

export type ShiftHandover = {
  id: string;
  shiftDate: string;
  shiftName: string;
  outgoingName: string;
  incomingName: string;
  summary: string;
  outstandingWork?: string | null;
  status: string;
};

export type LogbookEntry = {
  id: string;
  category: string;
  body: string;
  authorName: string;
  createdAt: string;
};

export type ToolboxTalk = {
  id: string;
  topic: string;
  location: string;
  heldAt: string;
  leaderName: string;
  attendeeCount: number;
  notes?: string | null;
};

export type VisionOverview = {
  simulator: boolean;
  gatewayUrl: string;
  cameras: Array<{ id: string; name: string; area: string; rtspUrl?: string | null; enabled: boolean }>;
  events: Array<{
    id: string;
    eventType: string;
    confidence: number;
    summary: string;
    status: string;
    detectedAt: string;
    camera: { id: string; name: string; area: string };
  }>;
};

export type AuditItem = {
  id: string;
  action: string;
  createdAt: string;
  payload?: Record<string, unknown>;
};

export type PsmAuditFinding = {
  id: string;
  elementCode: string;
  elementNameFa: string;
  rating: string;
  notes?: string | null;
  correctiveAction?: string | null;
  dueDate?: string | null;
  status: string;
};

export type PsmAudit = {
  id: string;
  title: string;
  auditDate: string;
  auditorName: string;
  status: string;
  overallScorePercent?: number | null;
  openFindings?: number;
  findings?: PsmAuditFinding[];
};

export type ErpPlan = {
  id: string;
  scenarioType: string;
  title: string;
  description: string;
  reviewedAt?: string | null;
  nextReviewDue: string;
  overdue: boolean;
};

export type EmergencyDrill = {
  id: string;
  erpPlanId?: string | null;
  scenario: string;
  heldAt: string;
  participantCount: number;
  durationMinutes: number;
  leaderName: string;
  findings?: string | null;
};

export type EffluentReading = {
  id: string;
  parameter: string;
  value: number;
  unit: string;
  limitValue?: number | null;
  location: string;
  compliant: boolean;
  sampledAt: string;
};

export type EmergencyOverview = {
  totalPlans: number;
  overduePlans: number;
  drillsLastYear: number;
  nonCompliantReadingsLastYear: number;
};

export type EquipmentStatus = {
  equipmentTag: string;
  hold: { status: string; blocked: boolean; score?: number | null; summary?: string | null };
  permits: Array<{ id: string; status: string; permitTypeCode: string; validFrom?: string | null; validTo?: string | null }>;
  mocs: Array<{ id: string; status: string; changeType: string; description: string }>;
  recentIncidents: Array<{ id: string; type: string; severity: string; status: string; reportedAt: string }>;
};

export type SignatureItem = {
  id: string;
  entityType: string;
  entityId: string;
  action: string;
  signerName: string;
  signedAt: string;
  contentHash: string;
};
