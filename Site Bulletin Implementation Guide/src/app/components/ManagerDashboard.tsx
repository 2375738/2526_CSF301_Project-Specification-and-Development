import React, { useEffect, useState } from 'react';
import { Users, Package, TrendingUp, Target, AlertCircle, Shield, Wrench, Briefcase, ArrowUp, ArrowDown, Minus } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import {
  fetchDepartmentOperationalMetrics,
  fetchDepartmentKPIs,
  fetchBenchmarks,
  DepartmentOperationalMetrics,
  DepartmentKPIs,
  BenchmarkData,
} from '../services/dataService';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { LineChart, Line, BarChart, Bar, PieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, ReferenceLine } from 'recharts';
import { QuickLinks } from './QuickLinks';
import { NewsWidget } from './NewsWidget';

export function ManagerDashboard() {
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [operationalMetrics, setOperationalMetrics] = useState<DepartmentOperationalMetrics | null>(null);
  const [kpis, setKpis] = useState<DepartmentKPIs | null>(null);
  const [benchmarks, setBenchmarks] = useState<BenchmarkData | null>(null);

  useEffect(() => {
    async function loadData() {
      if (!user) return;

      setLoading(true);
      try {
        const [opsData, kpiData, benchmarkData] = await Promise.all([
          fetchDepartmentOperationalMetrics(user.department),
          fetchDepartmentKPIs(user.department),
          fetchBenchmarks(),
        ]);
        setOperationalMetrics(opsData);
        setKpis(kpiData);
        setBenchmarks(benchmarkData);
      } finally {
        setLoading(false);
      }
    }
    loadData();
  }, [user?.department]);

  if (loading || !operationalMetrics || !kpis || !benchmarks) {
    return (
      <div className="p-4 md:p-8 pb-20 lg:pb-0 animate-pulse">
        <div className="h-8 bg-slate-200 rounded w-1/3 mb-6"></div>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-6">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="h-32 bg-slate-200 rounded-lg"></div>
          ))}
        </div>
      </div>
    );
  }

  const getCategoryIcon = (category: string) => {
    const icons = {
      Safety: Shield,
      HR: Users,
      'IT Support': Wrench,
      Operations: Briefcase,
    };
    return icons[category as keyof typeof icons] || Briefcase;
  };

  const getCategoryColor = (category: string) => {
    const colors = {
      Safety: { text: 'text-blue-600', bg: 'bg-blue-100' },
      HR: { text: 'text-pink-600', bg: 'bg-pink-100' },
      'IT Support': { text: 'text-purple-600', bg: 'bg-purple-100' },
      Operations: { text: 'text-emerald-600', bg: 'bg-emerald-100' },
    };
    return colors[category as keyof typeof colors] || colors.Operations;
  };

  const TrendIcon = ({ trend }: { trend: 'up' | 'down' | 'stable' }) => {
    if (trend === 'up') return <ArrowUp className="w-4 h-4 text-green-600" />;
    if (trend === 'down') return <ArrowDown className="w-4 h-4 text-red-600" />;
    return <Minus className="w-4 h-4 text-slate-400" />;
  };

  const SLA_COLORS = {
    withinSLA: '#10b981',
    atRisk: '#f59e0b',
    breached: '#ef4444',
  };

  const slaData = [
    { name: 'Within SLA', value: kpis.slaHealth.withinSLA, color: SLA_COLORS.withinSLA },
    { name: 'At Risk', value: kpis.slaHealth.atRisk, color: SLA_COLORS.atRisk },
    { name: 'Breached', value: kpis.slaHealth.breached, color: SLA_COLORS.breached },
  ];

  return (
    <div className="p-4 md:p-8 pb-20 lg:pb-0 space-y-6 md:space-y-8">
      {/* Header */}
      <div>
        <h1 className="text-slate-900 mb-2">Department Overview</h1>
        <p className="text-slate-600">Operations health and team performance metrics</p>
      </div>

      {/* Key Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        <Card className="border-slate-200">
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-slate-500 mb-1">Team Size</p>
                <h2 className="text-slate-900">{kpis.teamSize}</h2>
                <p className="text-sm text-slate-600">Active members</p>
              </div>
              <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                <Users className="w-6 h-6 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-slate-200">
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-slate-500 mb-1">Units Processed</p>
                <h2 className="text-slate-900">
                  {operationalMetrics.productivity.totalUnitsProcessed.toLocaleString()}
                </h2>
                <div className="flex items-center gap-1 text-sm text-green-600">
                  <TrendIcon trend={operationalMetrics.productivity.trend} />
                  <span>{operationalMetrics.productivity.percentOfTarget}% of target</span>
                </div>
              </div>
              <div className="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                <Package className="w-6 h-6 text-purple-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-slate-200">
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-slate-500 mb-1">Avg Productivity</p>
                <h2 className="text-slate-900">
                  {operationalMetrics.productivity.averageRate} /hr
                </h2>
                <div className="flex items-center gap-1 text-sm">
                  <TrendIcon trend={operationalMetrics.productivity.trend} />
                  <span className="text-slate-600">Team average</span>
                </div>
              </div>
              <div className="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                <TrendingUp className="w-6 h-6 text-green-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-slate-200">
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-slate-500 mb-1">Quality Score</p>
                <h2 className="text-slate-900">
                  {operationalMetrics.quality.averageScore}%
                </h2>
                <p className="text-sm text-slate-600">
                  {operationalMetrics.quality.defectCount} total defects
                </p>
              </div>
              <div className="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                <Target className="w-6 h-6 text-emerald-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card className="border-slate-200 lg:col-span-2">
          <CardHeader>
            <CardTitle className="text-slate-900">Department Operational Performance</CardTitle>
          </CardHeader>
          <CardContent className="pt-6">
            <ResponsiveContainer width="100%" height={280}>
              <LineChart data={operationalMetrics.historicalTrend}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
                <XAxis dataKey="day" stroke="#64748b" style={{ fontSize: '12px' }} />
                <YAxis yAxisId="left" stroke="#64748b" style={{ fontSize: '12px' }} />
                <YAxis yAxisId="right" orientation="right" stroke="#64748b" style={{ fontSize: '12px' }} />
                <Tooltip
                  contentStyle={{
                    backgroundColor: 'white',
                    border: '1px solid #e2e8f0',
                    borderRadius: '8px',
                    boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                  }}
                />
                <Legend />
                <ReferenceLine yAxisId="left" y={42} stroke="#f59e0b" strokeDasharray="3 3" label="Target" />
                <Line yAxisId="left" type="monotone" dataKey="productivity" stroke="#3b82f6" strokeWidth={2} name="Productivity (units/hr)" />
                <Line yAxisId="right" type="monotone" dataKey="quality" stroke="#10b981" strokeWidth={2} name="Quality (%)" />
              </LineChart>
            </ResponsiveContainer>
            <div className="mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
              <p className="text-sm text-green-900">
                <strong>Team Performance:</strong> Department is exceeding targets at {operationalMetrics.productivity.percentOfTarget}% with strong quality scores averaging {operationalMetrics.quality.averageScore}%.
              </p>
            </div>
          </CardContent>
        </Card>

        <Card className="border-slate-200">
          <CardHeader>
            <CardTitle className="text-slate-900">SLA Health</CardTitle>
          </CardHeader>
          <CardContent className="pt-6">
            <ResponsiveContainer width="100%" height={280}>
              <PieChart>
                <Pie
                  data={slaData}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={100}
                  paddingAngle={2}
                  dataKey="value"
                >
                  {slaData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
            <div className="mt-4 space-y-2">
              {slaData.map((item) => (
                <div key={item.name} className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <div className="w-3 h-3 rounded-full" style={{ backgroundColor: item.color }}></div>
                    <span className="text-sm text-slate-700">{item.name}</span>
                  </div>
                  <span className="text-sm text-slate-900">{item.value}%</span>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Benchmark Comparison */}
      <Card className="border-slate-200">
        <CardHeader>
          <CardTitle className="text-slate-900">Benchmark Comparison</CardTitle>
        </CardHeader>
        <CardContent className="pt-6">
          <ResponsiveContainer width="100%" height={300}>
            <BarChart data={benchmarks.metrics}>
              <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" />
              <XAxis dataKey="name" stroke="#64748b" style={{ fontSize: '12px' }} />
              <YAxis stroke="#64748b" style={{ fontSize: '12px' }} />
              <Tooltip
                contentStyle={{
                  backgroundColor: 'white',
                  border: '1px solid #e2e8f0',
                  borderRadius: '8px',
                  boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)',
                }}
              />
              <Legend />
              <Bar dataKey="department" fill="#3b82f6" name="Your Department" />
              <Bar dataKey="companyAvg" fill="#8b5cf6" name="Company Average" />
              <Bar dataKey="industryBenchmark" fill="#64748b" name="Industry Benchmark" />
            </BarChart>
          </ResponsiveContainer>
          <div className="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="p-4 bg-blue-50 rounded-lg border border-blue-200">
              <p className="text-sm text-blue-900">
                <strong>Resolution Time:</strong> Your team is performing 18% better than company average and 38% better than industry benchmark.
              </p>
            </div>
            <div className="p-4 bg-green-50 rounded-lg border border-green-200">
              <p className="text-sm text-green-900">
                <strong>SLA Performance:</strong> Exceeding company standards with 94.5% adherence, well above the 85% industry benchmark.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Bottom Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Ticket Categories */}
        <Card className="border-slate-200">
          <CardHeader>
            <CardTitle className="text-slate-900">Ticket Categories</CardTitle>
          </CardHeader>
          <CardContent className="pt-6">
            <div className="space-y-4">
              {kpis.categoryBreakdown.map((category) => {
                const Icon = getCategoryIcon(category.category);
                const colors = getCategoryColor(category.category);
                const total = kpis.categoryBreakdown.reduce((sum, c) => sum + c.count, 0);
                const percentage = (category.count / total) * 100;

                return (
                  <div key={category.category} className="space-y-2">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className={`w-10 h-10 ${colors.bg} rounded-full flex items-center justify-center flex-shrink-0`}>
                          <Icon className={`w-5 h-5 ${colors.text}`} />
                        </div>
                        <div>
                          <p className="text-sm text-slate-900">{category.category}</p>
                          <p className="text-xs text-slate-500">
                            Avg {category.avgResolutionTime}h resolution
                          </p>
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="text-sm text-slate-900">{category.count}</span>
                        <TrendIcon trend={category.trend} />
                      </div>
                    </div>
                    <div className="w-full bg-slate-100 rounded-full h-2">
                      <div
                        className={`${colors.bg} h-2 rounded-full`}
                        style={{ width: `${percentage}%` }}
                      ></div>
                    </div>
                  </div>
                );
              })}
            </div>
          </CardContent>
        </Card>

        {/* Team Performance */}
        <Card className="border-slate-200">
          <CardHeader>
            <CardTitle className="text-slate-900">Team Operational Performance</CardTitle>
          </CardHeader>
          <CardContent className="pt-6">
            <div className="space-y-3">
              {operationalMetrics.teamMembers
                .sort((a, b) => b.productivity - a.productivity)
                .map((member, index) => {
                  const initials = member.name
                    .split(' ')
                    .map((n) => n[0])
                    .join('')
                    .toUpperCase();
                  const colors = [
                    'from-blue-400 to-blue-600',
                    'from-purple-400 to-purple-600',
                    'from-green-400 to-green-600',
                    'from-orange-400 to-orange-600',
                    'from-pink-400 to-pink-600',
                  ];
                  const colorIndex = index % colors.length;

                  return (
                    <div
                      key={member.userId}
                      className="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200"
                    >
                      <div className={`w-8 h-8 ${index === 0 ? 'bg-gradient-to-br from-amber-400 to-amber-600' : 'bg-slate-300'} rounded-full flex items-center justify-center text-white text-xs flex-shrink-0`}>
                        {index + 1}
                      </div>
                      <div className={`w-10 h-10 bg-gradient-to-br ${colors[colorIndex]} rounded-full flex items-center justify-center text-white flex-shrink-0`}>
                        {initials}
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm text-slate-900">{member.name}</p>
                        <p className="text-xs text-slate-500">{member.role}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm text-slate-900">{member.productivity} /hr</p>
                        <p className="text-xs text-slate-500">{member.quality}% quality</p>
                      </div>
                      <TrendIcon trend={member.trend} />
                    </div>
                  );
                })}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* News Widget */}
      <NewsWidget />

      {/* Quick Links */}
      <QuickLinks />
    </div>
  );
}
