import React, { useEffect, useState } from 'react';
import { Plus } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { fetchEmployeeTickets, fetchDepartmentTickets, Ticket, getStatusColor, getTimeRemaining } from '../services/dataService';
import { Card, CardContent } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';

export function MyTasksView() {
  const { user } = useAuth();
  const [loading, setLoading] = useState(true);
  const [tickets, setTickets] = useState<Ticket[]>([]);

  useEffect(() => {
    async function loadData() {
      if (!user) return;

      setLoading(true);
      try {
        const ticketsData =
          user.role === 'manager'
            ? await fetchDepartmentTickets(user.department)
            : await fetchEmployeeTickets(user.id);
        setTickets(ticketsData);
      } finally {
        setLoading(false);
      }
    }
    loadData();
  }, [user?.id, user?.role, user?.department]);

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

  const formatCreatedAt = (date: Date) => {
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(hours / 24);

    if (days === 1) return 'Yesterday, 4:30 PM';
    if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
    if (hours > 0) return `${hours}h ago`;
    return 'Just now';
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 p-4 md:p-8 pb-20 lg:pb-0 animate-pulse">
        <div className="max-w-5xl mx-auto">
          <div className="h-8 bg-slate-200 rounded w-1/3 mb-6"></div>
          <div className="h-64 bg-slate-200 rounded-lg"></div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 p-4 md:p-8 pb-20 lg:pb-0">
      <div className="max-w-5xl mx-auto space-y-6">
        <h1 className="text-slate-900">My Active Tickets</h1>

        <Card className="border-slate-200 bg-white/80 backdrop-blur-sm shadow-lg">
          <CardContent className="pt-6">
            {tickets.length === 0 ? (
              <div className="text-center py-8 md:py-12">
                <p className="text-slate-500 text-sm">No active tickets at the moment</p>
              </div>
            ) : (
              <div className="space-y-3">
                {tickets.map((ticket) => (
                  <div
                    key={ticket.id}
                    className="flex flex-col md:flex-row md:items-center md:justify-between gap-3 p-4 bg-white border border-slate-200 rounded-lg hover:shadow-md transition-all cursor-pointer min-h-[44px]"
                  >
                    <div className="flex items-center gap-3 flex-1">
                      <Badge className={`${getStatusColor(ticket.status)} flex-shrink-0`}>
                        {ticket.status === 'Waiting on User'
                          ? 'WAITING-ON-USER'
                          : ticket.status.toUpperCase()}
                      </Badge>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm text-slate-900 truncate">{ticket.title}</p>
                        <p className="text-xs text-slate-500">{formatCreatedAt(ticket.createdAt)}</p>
                      </div>
                    </div>
                    <div className="flex items-center justify-end gap-3">
                      <div
                        className={`w-10 h-10 bg-gradient-to-br ${getAvatarColor(ticket.assignee.initials)} rounded-full flex items-center justify-center text-white text-xs flex-shrink-0`}
                      >
                        {ticket.assignee.initials}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Floating Action Button */}
      <div className="fixed left-1/2 transform -translate-x-1/2 bottom-24 lg:bottom-8 z-50">
        <Button className="px-6 py-6 bg-blue-600 hover:bg-blue-700 text-white shadow-lg rounded-full min-h-[56px]">
          <Plus className="w-5 h-5 mr-2" />
          New Ticket
        </Button>
      </div>
    </div>
  );
}
