import React, { useEffect, useState } from 'react';
import { Package, TrendingUp, Target, Plus, ArrowUp } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import {
  fetchOperationalMetrics,
  fetchEmployeeMetrics,
  fetchEmployeeTickets,
  OperationalMetrics,
  EmployeeMetrics,
  Ticket,
  getTimeRemaining,
  getStatusColor,
} from '../services/dataService';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { AreaChart, Area, LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, ReferenceLine } from 'recharts';
import { QuickLinks } from './QuickLinks';
import { NewsWidget } from './NewsWidget';

export function EmployeeDashboard() {
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [operationalMetrics, setOperationalMetrics] = useState<OperationalMetrics | null>(null);
  const [ticketMetrics, setTicketMetrics] = useState<EmployeeMetrics | null>(null);
  const [tickets, setTickets] = useState<Ticket[]>([]);

  useEffect(() => {
    async function loadData() {
      if (!user) return;

      setLoading(true);
      try {
        const [opsData, metricsData, ticketsData] = await Promise.all([
          fetchOperationalMetrics(user.id),
          fetchEmployeeMetrics(user.id),
          fetchEmployeeTickets(user.id),
        ]);
        setOperationalMetrics(opsData);
        setTicketMetrics(metricsData);
        setTickets(ticketsData);
      } finally {
        setLoading(false);
      }
    }
    loadData();
  }, [user?.id]);

  if (loading || !operationalMetrics || !ticketMetrics) {
    return (
      <div className="p-4 md:p-8 pb-20 lg:pb-0 animate-pulse">
        <div className="h-8 bg-slate-200 rounded w-1/3 mb-6"></div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-6">
          {[...Array(3)].map((_, i) => (
            <div key={i} className="h-32 bg-slate-200 rounded-lg"></div>
          ))}
        </div>
      </div>
    );
  }

  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase();
  };

  const getAvatarColor = (initials: string) => {
    const colors = [
      'from-blue-400 to-blue-600',
      'from-orange-400 to-orange-600',
      'from-green-400 to-green-600',
      'from-purple-400 to-purple-600',
      'from-pink-400 to-pink-600',
    ];
    const index = initials.charCodeAt(0) % colors.length;
    return colors[index];
  };

  return (
    <div className="p-4 md:p-8 pb-20 lg:pb-0 space-y-6 md:space-y-8">
      {/* Header */}
      <div>
        <h1 className="text-slate-900 mb-2">Welcome back, {user?.name}</h1>
        <p className="text-slate-600">Here's your performance snapshot for this week</p>
      </div>

      {/* Stats Overview */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
        <Card className="border-slate-200">
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-slate-500 mb-1">Units Processed</p>
                <h2 className="text-slate-900 mb-1">
                  {operationalMetrics.productivity.unitsProcessed.toLocaleString()}
                </h2>
                <div className="flex items-center gap-1 text-sm text-green-600">
                  <ArrowUp className="w-4 h-4" />
                  <span>{operationalMetrics.productivity.percentOfTarget}% of target</span>
                </div>
              </div>
              <div className="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                <Package className="w-6 h-6 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-slate-200">
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-slate-500 mb-1">Productivity Rate</p>
                <h2 className="text-slate-900 mb-1">
                  {operationalMetrics.productivity.rate} /hr
                </h2>
                <p className="text-sm text-slate-600">
                  Target: {(operationalMetrics.productivity.targetUnits / 60).toFixed(1)} /hr
                </p>
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
                <h2 className="text-slate-900 mb-1">
                  {operationalMetrics.quality.score}%
                </h2>
                <p className="text-sm text-slate-600">
                  {operationalMetrics.quality.defectCount} defects
                </p>
              </div>
              <div className="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center flex-shrink-0">
                <Target className="w-6 h-6 text-emerald-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Performance Trend Chart */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card className="border-slate-200 lg:col-span-2">
          <CardHeader>
            <CardTitle className="text-slate-900">Performance Trend</CardTitle>
          </CardHeader>
          <CardContent className="pt-6">
            <ResponsiveContainer width="100%" height={280}>
              <AreaChart data={operationalMetrics.historicalTrend}>
                <defs>
                  <linearGradient id="colorProductivity" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%" stopColor="#3b82f6" stopOpacity={0.3} />
                    <stop offset="95%" stopColor="#3b82f6" stopOpacity={0} />
                  </linearGradient>
                </defs>
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
                <ReferenceLine yAxisId="left" y={42} stroke="#f59e0b" strokeDasharray="3 3" label="Target" />
                <Area
                  yAxisId="left"
                  type="monotone"
                  dataKey="productivity"
                  stroke="#3b82f6"
                  fill="url(#colorProductivity)"
                  strokeWidth={2}
                />
                <Line yAxisId="right" type="monotone" dataKey="quality" stroke="#10b981" strokeWidth={2} />
              </AreaChart>
            </ResponsiveContainer>
            <div className="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
              <p className="text-sm text-blue-900">
                <strong>Great work!</strong> Your productivity is {operationalMetrics.productivity.percentOfTarget}% of target and quality remains high at {operationalMetrics.quality.score}%.
              </p>
            </div>
          </CardContent>
        </Card>

        {/* Recent Messages Preview */}
        <Card className="border-slate-200">
          <CardHeader>
            <CardTitle className="text-slate-900">Recent Messages</CardTitle>
          </CardHeader>
          <CardContent className="pt-6">
            <div className="space-y-3 mb-4">
              {[
                { from: 'Sarah Chen', message: 'Great job on yesterday\'s output!', unread: true, time: '10m' },
                { from: 'HR Team', message: 'Benefits enrollment deadline...', unread: true, time: '2h' },
                { from: 'Safety Dept', message: 'New equipment training...', unread: false, time: '1d' },
              ].map((msg, i) => (
                <div
                  key={i}
                  className={`p-3 rounded-lg cursor-pointer hover:shadow-md transition-all ${
                    msg.unread ? 'bg-blue-50 border border-blue-200' : 'bg-slate-50 border border-slate-200'
                  }`}
                >
                  <div className="flex gap-3">
                    <div className={`w-10 h-10 bg-gradient-to-br ${getAvatarColor(getInitials(msg.from))} rounded-full flex items-center justify-center text-white flex-shrink-0`}>
                      {getInitials(msg.from)}
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm text-slate-900">{msg.from}</p>
                      <p className="text-xs text-slate-600 truncate">{msg.message}</p>
                      <p className="text-xs text-slate-500 mt-1">{msg.time} ago</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
            <Button variant="outline" className="w-full">
              View All Messages
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* My Active Tickets */}
      <Card className="border-slate-200">
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="text-slate-900">My Active Tickets</CardTitle>
            <Button className="bg-blue-600 hover:bg-blue-700 text-white">
              <Plus className="w-4 h-4 mr-2" />
              New Ticket
            </Button>
          </div>
        </CardHeader>
        <CardContent className="pt-6">
          <div className="space-y-3">
            {tickets.map((ticket) => (
              <div
                key={ticket.id}
                className="flex flex-col md:flex-row md:items-center md:justify-between gap-3 p-4 bg-white border border-slate-200 rounded-lg hover:shadow-md transition-all cursor-pointer min-h-[44px]"
              >
                <div className="flex items-center gap-3 flex-1">
                  <Badge className={`${getStatusColor(ticket.status)} flex-shrink-0`}>
                    {ticket.status.toUpperCase().replace(/-/g, ' ')}
                  </Badge>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm text-slate-900 truncate">{ticket.title}</p>
                    <p className="text-xs text-slate-500">{ticket.id}</p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <span className="text-xs text-slate-500">{getTimeRemaining(ticket.dueDate)}</span>
                  <div className={`w-10 h-10 bg-gradient-to-br ${getAvatarColor(ticket.assignee.initials)} rounded-full flex items-center justify-center text-white text-xs flex-shrink-0`}>
                    {ticket.assignee.initials}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* News Widget */}
      <NewsWidget />

      {/* Quick Links */}
      <QuickLinks />
    </div>
  );
}
