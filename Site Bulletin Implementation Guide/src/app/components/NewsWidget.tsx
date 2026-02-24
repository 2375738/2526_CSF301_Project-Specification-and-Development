import React from 'react';
import { Bell, AlertCircle, ChevronRight } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { useAnnouncements } from '../contexts/AnnouncementContext';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';

export function NewsWidget() {
  const { user } = useAuth();
  const { announcements } = useAnnouncements();

  const relevantAnnouncements = announcements
    .filter(
      (a) =>
        a.department === 'All Departments' || a.department === user?.department
    )
    .slice(0, 5);

  const getPriorityColor = (priority: string) => {
    const colors = {
      urgent: 'bg-red-100 text-red-700 hover:bg-red-100',
      high: 'bg-orange-100 text-orange-700 hover:bg-orange-100',
      medium: 'bg-blue-100 text-blue-700 hover:bg-blue-100',
      low: 'bg-slate-100 text-slate-700 hover:bg-slate-100',
    };
    return colors[priority as keyof typeof colors] || colors.low;
  };

  const formatTimestamp = (date: Date) => {
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(hours / 24);

    if (days > 0) return `${days}d ago`;
    if (hours > 0) return `${hours}h ago`;
    return 'Just now';
  };

  return (
    <Card className="border-slate-200">
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="text-slate-900">Latest News & Updates</CardTitle>
          <Button variant="ghost" className="text-sm text-blue-600 hover:text-blue-700">
            View All
            <ChevronRight className="w-4 h-4 ml-1" />
          </Button>
        </div>
      </CardHeader>
      <CardContent className="pt-6">
        <div className="space-y-3">
          {relevantAnnouncements.map((announcement) => (
            <div
              key={announcement.id}
              className={`p-4 rounded-lg border transition-all cursor-pointer hover:shadow-md ${
                announcement.read
                  ? 'bg-white border-slate-200'
                  : 'bg-blue-50 border-blue-200'
              }`}
            >
              <div className="flex gap-3">
                <div className="flex-shrink-0">
                  {announcement.priority === 'high' ||
                  announcement.priority === 'urgent' ? (
                    <div className="w-10 h-10 bg-gradient-to-br from-red-400 to-red-600 rounded-full flex items-center justify-center">
                      <AlertCircle className="w-5 h-5 text-white" />
                    </div>
                  ) : (
                    <div className="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center">
                      <Bell className="w-5 h-5 text-white" />
                    </div>
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <h4 className="text-slate-900 line-clamp-1 mb-1">
                    {announcement.title}
                  </h4>
                  <p className="text-sm text-slate-600 line-clamp-2 mb-2">
                    {announcement.content}
                  </p>
                  <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span>{announcement.from}</span>
                    <span>•</span>
                    <span>{formatTimestamp(announcement.timestamp)}</span>
                    <Badge className={getPriorityColor(announcement.priority)}>
                      {announcement.priority.toUpperCase()}
                    </Badge>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
