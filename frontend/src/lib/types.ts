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
  reportedAt?: string;
  capaActions?: Capa[];
};

export type Certification = {
  id: string;
  type: string;
  expiresAt: string;
  expiringSoon?: boolean;
};

export type Contractor = {
  id: string;
  companyName: string;
  hsePrequalificationScore?: string;
  certifications?: Certification[];
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

export type AuditItem = {
  id: string;
  action: string;
  createdAt: string;
  payload?: Record<string, unknown>;
};
