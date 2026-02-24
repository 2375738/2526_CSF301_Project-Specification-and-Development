// Data Service Layer - Mock API Integration
// Replace fetch calls with real API endpoints when integrating

// ============= TYPE DEFINITIONS =============

export interface OperationalMetrics {
  userId: string;
  period: 'week' | 'month';
  productivity: {
    unitsProcessed: number;
    targetUnits: number;
    rate: number;
    percentOfTarget: number;
    trend: 'up' | 'down' | 'stable';
  };
  quality: {
    score: number;
    errorRate: number;
    accuracyRate: number;
    defectCount: number;
    trend: 'up' | 'down' | 'stable';
  };
  attendance: {
    daysWorked: number;
    totalDays: number;
    punctualityScore: number;
  };
  historicalTrend: OperationalTrendData[];
}

export interface OperationalTrendData {
  day: string;
  productivity: number;
  quality: number;
  target: number;
}

export interface DepartmentOperationalMetrics {
  departmentId: string;
  period: 'week' | 'month';
  productivity: {
    totalUnitsProcessed: number;
    averageRate: number;
    percentOfTarget: number;
    trend: 'up' | 'down' | 'stable';
  };
  quality: {
    averageScore: number;
    errorRate: number;
    defectCount: number;
    trend: 'up' | 'down' | 'stable';
  };
  teamMembers: TeamOperationalMetrics[];
  historicalTrend: OperationalTrendData[];
}

export interface TeamOperationalMetrics {
  userId: string;
  name: string;
  role: string;
  productivity: number;
  quality: number;
  trend: 'up' | 'down' | 'stable';
}

export interface EmployeeMetrics {
  userId: string;
  activeTickets: number;
  resolvedThisWeek: number;
  slaAdherence: number;
  weekOverWeekChange: number;
  averageResolutionTime: string;
  qualityScore: number;
  performanceTrend: WeeklyPerformance[];
}

export interface WeeklyPerformance {
  week: string;
  resolved: number;
  created: number;
}

export interface DepartmentKPIs {
  departmentId: string;
  teamSize: number;
  activeTickets: number;
  avgResolutionTime: number;
  resolutionTimeChange: number;
  slaBreaches: number;
  performanceTrend: DepartmentTrend[];
  slaHealth: SLAHealth;
  categoryBreakdown: CategoryStats[];
  teamPerformance: TeamMemberStats[];
}

export interface DepartmentTrend {
  week: string;
  avgResolutionTime: number;
  slaAdherence: number;
}

export interface SLAHealth {
  withinSLA: number;
  atRisk: number;
  breached: number;
}

export interface CategoryStats {
  category: string;
  count: number;
  avgResolutionTime: number;
  trend: 'up' | 'down' | 'stable';
}

export interface TeamMemberStats {
  userId: string;
  name: string;
  role: string;
  ticketsResolved: number;
  avgResolutionTime: number;
  slaAdherence: number;
}

export interface Ticket {
  id: string;
  title: string;
  status: 'Open' | 'In Progress' | 'Waiting on User' | 'Resolved' | 'Breached';
  priority: 'low' | 'medium' | 'high' | 'critical';
  category: string;
  assignee: {
    name: string;
    initials: string;
    avatar?: string;
  };
  createdAt: Date;
  dueDate: Date;
  description?: string;
}

export interface BenchmarkData {
  metrics: {
    name: string;
    department: number;
    companyAvg: number;
    industryBenchmark: number;
  }[];
}

// ============= HELPER FUNCTIONS =============

export function getTimeRemaining(dueDate: Date): string {
  const now = new Date();
  const diff = dueDate.getTime() - now.getTime();
  const hours = Math.floor(diff / (1000 * 60 * 60));
  const days = Math.floor(hours / 24);

  if (days > 0) return `${days}d remaining`;
  if (hours > 0) return `${hours}h remaining`;
  if (diff < 0) return 'Overdue';
  return 'Due soon';
}

export function getStatusColor(status: Ticket['status']): string {
  const colors = {
    'Open': 'bg-blue-100 text-blue-700',
    'In Progress': 'bg-blue-100 text-blue-700',
    'Waiting on User': 'bg-green-100 text-green-700',
    'Resolved': 'bg-green-100 text-green-700',
    'Breached': 'bg-red-100 text-red-700',
  };
  return colors[status];
}

export function getPriorityColor(priority: Ticket['priority']): string {
  const colors = {
    low: 'bg-slate-100 text-slate-700',
    medium: 'bg-blue-100 text-blue-700',
    high: 'bg-orange-100 text-orange-700',
    critical: 'bg-red-100 text-red-700',
  };
  return colors[priority];
}

// ============= API FUNCTIONS =============

export async function fetchOperationalMetrics(userId: string): Promise<OperationalMetrics> {
  // Replace with: await fetch(`/api/adapt/employee-metrics/${userId}`)
  await new Promise(resolve => setTimeout(resolve, 300));

  return {
    userId,
    period: 'week',
    productivity: {
      unitsProcessed: 2847,
      targetUnits: 2500,
      rate: 47.5,
      percentOfTarget: 113.9,
      trend: 'up',
    },
    quality: {
      score: 96.8,
      errorRate: 3.2,
      accuracyRate: 96.8,
      defectCount: 12,
      trend: 'up',
    },
    attendance: {
      daysWorked: 5,
      totalDays: 5,
      punctualityScore: 98,
    },
    historicalTrend: [
      { day: 'Mon', productivity: 45, quality: 95, target: 42 },
      { day: 'Tue', productivity: 48, quality: 97, target: 42 },
      { day: 'Wed', productivity: 46, quality: 96, target: 42 },
      { day: 'Thu', productivity: 49, quality: 98, target: 42 },
      { day: 'Fri', productivity: 47.5, quality: 96.8, target: 42 },
    ],
  };
}

export async function fetchDepartmentOperationalMetrics(
  departmentId: string
): Promise<DepartmentOperationalMetrics> {
  // Replace with: await fetch(`/api/adapt/department-metrics/${departmentId}`)
  await new Promise(resolve => setTimeout(resolve, 300));

  return {
    departmentId,
    period: 'week',
    productivity: {
      totalUnitsProcessed: 45680,
      averageRate: 46.2,
      percentOfTarget: 108.5,
      trend: 'up',
    },
    quality: {
      averageScore: 95.4,
      errorRate: 4.6,
      defectCount: 187,
      trend: 'stable',
    },
    teamMembers: [
      { userId: '1', name: 'Jordan Smith', role: 'Picker', productivity: 47.5, quality: 96.8, trend: 'up' },
      { userId: '2', name: 'Alex Rivera', role: 'Packer', productivity: 52.3, quality: 98.2, trend: 'up' },
      { userId: '3', name: 'Sam Taylor', role: 'Sorter', productivity: 44.1, quality: 93.5, trend: 'stable' },
      { userId: '4', name: 'Morgan Lee', role: 'Picker', productivity: 48.9, quality: 94.8, trend: 'up' },
      { userId: '5', name: 'Casey Jones', role: 'Packer', productivity: 41.2, quality: 92.1, trend: 'down' },
    ],
    historicalTrend: [
      { day: 'Mon', productivity: 44, quality: 94, target: 42 },
      { day: 'Tue', productivity: 47, quality: 96, target: 42 },
      { day: 'Wed', productivity: 45, quality: 95, target: 42 },
      { day: 'Thu', productivity: 48, quality: 97, target: 42 },
      { day: 'Fri', productivity: 46.2, quality: 95.4, target: 42 },
    ],
  };
}

export async function fetchEmployeeMetrics(userId: string): Promise<EmployeeMetrics> {
  // Replace with: await fetch(`/api/tickets/metrics/employee/${userId}`)
  await new Promise(resolve => setTimeout(resolve, 300));

  return {
    userId,
    activeTickets: 8,
    resolvedThisWeek: 12,
    slaAdherence: 94.5,
    weekOverWeekChange: 8.3,
    averageResolutionTime: '3.2 hrs',
    qualityScore: 96.8,
    performanceTrend: [
      { week: 'W1', resolved: 10, created: 12 },
      { week: 'W2', resolved: 11, created: 10 },
      { week: 'W3', resolved: 9, created: 11 },
      { week: 'W4', resolved: 12, created: 9 },
    ],
  };
}

export async function fetchDepartmentKPIs(departmentId: string): Promise<DepartmentKPIs> {
  // Replace with: await fetch(`/api/tickets/department/${departmentId}`)
  await new Promise(resolve => setTimeout(resolve, 300));

  return {
    departmentId,
    teamSize: 18,
    activeTickets: 45,
    avgResolutionTime: 4.2,
    resolutionTimeChange: -12.5,
    slaBreaches: 3,
    performanceTrend: [
      { week: 'W1', avgResolutionTime: 5.1, slaAdherence: 89 },
      { week: 'W2', avgResolutionTime: 4.8, slaAdherence: 91 },
      { week: 'W3', avgResolutionTime: 4.5, slaAdherence: 93 },
      { week: 'W4', avgResolutionTime: 4.2, slaAdherence: 94.5 },
    ],
    slaHealth: {
      withinSLA: 78,
      atRisk: 15,
      breached: 7,
    },
    categoryBreakdown: [
      { category: 'Safety', count: 12, avgResolutionTime: 2.8, trend: 'down' },
      { category: 'HR', count: 8, avgResolutionTime: 6.2, trend: 'stable' },
      { category: 'IT Support', count: 15, avgResolutionTime: 3.5, trend: 'down' },
      { category: 'Operations', count: 10, avgResolutionTime: 5.1, trend: 'up' },
    ],
    teamPerformance: [
      { userId: '1', name: 'Jordan Smith', role: 'Support Specialist', ticketsResolved: 24, avgResolutionTime: 2.8, slaAdherence: 98 },
      { userId: '2', name: 'Alex Rivera', role: 'Support Specialist', ticketsResolved: 22, avgResolutionTime: 3.1, slaAdherence: 96 },
      { userId: '3', name: 'Sam Taylor', role: 'Support Lead', ticketsResolved: 20, avgResolutionTime: 3.5, slaAdherence: 94 },
      { userId: '4', name: 'Morgan Lee', role: 'Support Specialist', ticketsResolved: 19, avgResolutionTime: 4.2, slaAdherence: 92 },
      { userId: '5', name: 'Casey Jones', role: 'Support Specialist', ticketsResolved: 17, avgResolutionTime: 4.8, slaAdherence: 89 },
    ],
  };
}

export async function fetchEmployeeTickets(userId: string): Promise<Ticket[]> {
  // Replace with: await fetch(`/api/tickets/user/${userId}`)
  await new Promise(resolve => setTimeout(resolve, 300));

  return [
    {
      id: 'TKT-2451',
      title: 'Safety equipment request - new gloves needed',
      status: 'Open',
      priority: 'high',
      category: 'Safety',
      assignee: { name: 'Safety Team', initials: 'ST' },
      createdAt: new Date(Date.now() - 1000 * 60 * 60 * 2),
      dueDate: new Date(Date.now() + 1000 * 60 * 60 * 6),
    },
    {
      id: 'TKT-2448',
      title: 'Timesheet correction for Feb 12',
      status: 'In Progress',
      priority: 'medium',
      category: 'HR',
      assignee: { name: 'HR Department', initials: 'HR' },
      createdAt: new Date(Date.now() - 1000 * 60 * 60 * 24),
      dueDate: new Date(Date.now() + 1000 * 60 * 60 * 16),
    },
    {
      id: 'TKT-2442',
      title: 'Scanner not connecting to system',
      status: 'Waiting on User',
      priority: 'high',
      category: 'IT Support',
      assignee: { name: 'IT Support', initials: 'IT' },
      createdAt: new Date(Date.now() - 1000 * 60 * 60 * 48),
      dueDate: new Date(Date.now() + 1000 * 60 * 60 * 4),
    },
  ];
}

export async function fetchDepartmentTickets(departmentId: string): Promise<Ticket[]> {
  // Replace with: await fetch(`/api/tickets/department/${departmentId}`)
  await new Promise(resolve => setTimeout(resolve, 300));

  const tickets: Ticket[] = [];
  const categories = ['Safety', 'HR', 'IT Support', 'Operations'];
  const statuses: Ticket['status'][] = ['Open', 'In Progress', 'Waiting on User', 'Resolved', 'Breached'];
  const priorities: Ticket['priority'][] = ['low', 'medium', 'high', 'critical'];

  for (let i = 0; i < 15; i++) {
    const category = categories[i % categories.length];
    tickets.push({
      id: `TKT-${2500 - i}`,
      title: `${category} issue - Item ${i + 1}`,
      status: statuses[i % statuses.length],
      priority: priorities[i % priorities.length],
      category,
      assignee: {
        name: `Team Member ${i + 1}`,
        initials: `T${i + 1}`,
      },
      createdAt: new Date(Date.now() - 1000 * 60 * 60 * (i * 6)),
      dueDate: new Date(Date.now() + 1000 * 60 * 60 * ((15 - i) * 2)),
    });
  }

  return tickets;
}

export async function fetchBenchmarks(): Promise<BenchmarkData> {
  // Replace with: await fetch('/api/benchmarks')
  await new Promise(resolve => setTimeout(resolve, 300));

  return {
    metrics: [
      {
        name: 'Avg Resolution Time (hrs)',
        department: 4.2,
        companyAvg: 5.1,
        industryBenchmark: 6.8,
      },
      {
        name: 'SLA Adherence (%)',
        department: 94.5,
        companyAvg: 89.2,
        industryBenchmark: 85.0,
      },
    ],
  };
}
