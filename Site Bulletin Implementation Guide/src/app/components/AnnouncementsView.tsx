import React, { useState, useMemo } from 'react';
import { Bell, AlertCircle, Megaphone, Search, SortAsc, Filter, CheckCircle2, Plus } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { useAnnouncements } from '../contexts/AnnouncementContext';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Input } from './ui/input';
import { Button } from './ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { CreateAnnouncementDialog } from './CreateAnnouncementDialog';

export function AnnouncementsView() {
  const { user } = useAuth();
  const { announcements, markAsRead, markAllAsRead, getUnreadCount } = useAnnouncements();
  const [searchQuery, setSearchQuery] = useState('');
  const [sortBy, setSortBy] = useState<'newest' | 'oldest' | 'priority' | 'unread'>('newest');
  const [filterBy, setFilterBy] = useState<'all' | 'unread' | 'high-priority' | 'my-department'>('all');
  const [showCreateDialog, setShowCreateDialog] = useState(false);

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

  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase();
  };

  const filteredAndSortedAnnouncements = useMemo(() => {
    let filtered = announcements;

    // Text search
    if (searchQuery) {
      filtered = filtered.filter(
        (a) =>
          a.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
          a.content.toLowerCase().includes(searchQuery.toLowerCase()) ||
          a.from.toLowerCase().includes(searchQuery.toLowerCase())
      );
    }

    // Apply filter
    if (filterBy === 'unread') {
      filtered = filtered.filter((a) => !a.read);
    } else if (filterBy === 'high-priority') {
      filtered = filtered.filter((a) => a.priority === 'high' || a.priority === 'urgent');
    } else if (filterBy === 'my-department') {
      filtered = filtered.filter(
        (a) => a.department === user?.department || a.department === 'All Departments'
      );
    }

    // Apply sort
    const sorted = [...filtered].sort((a, b) => {
      if (sortBy === 'newest') {
        return b.timestamp.getTime() - a.timestamp.getTime();
      } else if (sortBy === 'oldest') {
        return a.timestamp.getTime() - b.timestamp.getTime();
      } else if (sortBy === 'priority') {
        const priorityOrder = { urgent: 0, high: 1, medium: 2, low: 3 };
        return priorityOrder[a.priority] - priorityOrder[b.priority];
      } else if (sortBy === 'unread') {
        return a.read === b.read ? 0 : a.read ? 1 : -1;
      }
      return 0;
    });

    return sorted;
  }, [announcements, searchQuery, sortBy, filterBy, user?.department]);

  const stats = {
    total: announcements.length,
    unread: getUnreadCount(),
    highPriority: announcements.filter((a) => a.priority === 'high' || a.priority === 'urgent')
      .length,
    myDepartment: announcements.filter(
      (a) => a.department === user?.department || a.department === 'All Departments'
    ).length,
  };

  return (
    <div className="p-4 md:p-8 pb-20 lg:pb-0 space-y-6 md:space-y-8">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
          <h1 className="text-slate-900 mb-2">Announcements</h1>
          <p className="text-slate-600">Stay updated with the latest news and updates</p>
        </div>
        {user?.role === 'manager' && (
          <Button
            onClick={() => setShowCreateDialog(true)}
            className="bg-blue-600 hover:bg-blue-700 text-white min-h-[44px]"
          >
            <Plus className="w-4 h-4 mr-2" />
            New Announcement
          </Button>
        )}
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
        <Card className="border-slate-200">
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-slate-500 mb-1">Total</p>
                <h2 className="text-slate-900">{stats.total}</h2>
              </div>
              <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                <Bell className="w-5 h-5 text-blue-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-slate-200">
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-slate-500 mb-1">Unread</p>
                <h2 className="text-slate-900">{stats.unread}</h2>
              </div>
              <div className="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center flex-shrink-0">
                <AlertCircle className="w-5 h-5 text-orange-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-slate-200">
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-slate-500 mb-1">High Priority</p>
                <h2 className="text-slate-900">{stats.highPriority}</h2>
              </div>
              <div className="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                <Megaphone className="w-5 h-5 text-red-600" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="border-slate-200">
          <CardContent className="pt-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-slate-500 mb-1">My Department</p>
                <h2 className="text-slate-900">{stats.myDepartment}</h2>
              </div>
              <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                <CheckCircle2 className="w-5 h-5 text-green-600" />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filters and Search */}
      <Card className="border-slate-200">
        <CardContent className="pt-6">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div className="md:col-span-2 relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400" />
              <Input
                placeholder="Search announcements..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10 min-h-[44px]"
              />
            </div>

            <Select value={sortBy} onValueChange={(value: any) => setSortBy(value)}>
              <SelectTrigger className="min-h-[44px]">
                <div className="flex items-center gap-2">
                  <SortAsc className="w-4 h-4" />
                  <SelectValue />
                </div>
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="newest">Newest First</SelectItem>
                <SelectItem value="oldest">Oldest First</SelectItem>
                <SelectItem value="priority">Priority</SelectItem>
                <SelectItem value="unread">Unread First</SelectItem>
              </SelectContent>
            </Select>

            <Select value={filterBy} onValueChange={(value: any) => setFilterBy(value)}>
              <SelectTrigger className="min-h-[44px]">
                <div className="flex items-center gap-2">
                  <Filter className="w-4 h-4" />
                  <SelectValue />
                </div>
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Announcements</SelectItem>
                <SelectItem value="unread">Unread Only</SelectItem>
                <SelectItem value="high-priority">High Priority</SelectItem>
                <SelectItem value="my-department">My Department</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {getUnreadCount() > 0 && (
            <div className="pt-4 border-t border-slate-200">
              <Button
                onClick={markAllAsRead}
                variant="outline"
                className="w-full min-h-[44px]"
              >
                Mark All as Read
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Announcements List */}
      <div className="space-y-4">
        {filteredAndSortedAnnouncements.length === 0 ? (
          <Card className="border-slate-200">
            <CardContent className="pt-6">
              <div className="text-center py-8 md:py-12">
                <Bell className="w-12 h-12 text-slate-300 mx-auto mb-3 md:mb-4" />
                <p className="text-slate-500 text-sm">No announcements found</p>
              </div>
            </CardContent>
          </Card>
        ) : (
          filteredAndSortedAnnouncements.map((announcement) => (
            <Card
              key={announcement.id}
              className={`border cursor-pointer hover:shadow-md transition-all ${
                announcement.read
                  ? 'bg-white border-slate-200'
                  : 'bg-blue-50 border-blue-200'
              }`}
              onClick={() => markAsRead(announcement.id)}
            >
              <CardContent className="pt-6">
                <div className="flex gap-4">
                  <div className="flex-shrink-0">
                    <div
                      className={`w-12 h-12 bg-gradient-to-br ${
                        announcement.priority === 'high' || announcement.priority === 'urgent'
                          ? 'from-red-400 to-red-600'
                          : 'from-purple-400 to-purple-600'
                      } rounded-full flex items-center justify-center`}
                    >
                      <Megaphone className="w-6 h-6 text-white" />
                    </div>
                  </div>
                  <div className="flex-1 min-w-0">
                    <h3 className="text-slate-900 mb-2">{announcement.title}</h3>
                    <p className="text-slate-600 mb-4">{announcement.content}</p>
                    <div className="flex flex-wrap items-center gap-3">
                      <div className="flex items-center gap-2">
                        <div className="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white text-xs">
                          {getInitials(announcement.from)}
                        </div>
                        <span className="text-sm text-slate-700">{announcement.from}</span>
                      </div>
                      <Badge className="bg-slate-100 text-slate-700 hover:bg-slate-100">
                        {announcement.department}
                      </Badge>
                      <Badge className={getPriorityColor(announcement.priority)}>
                        {announcement.priority.toUpperCase()}
                      </Badge>
                      <span className="text-sm text-slate-500">
                        {formatTimestamp(announcement.timestamp)}
                      </span>
                    </div>
                  </div>
                  {!announcement.read && (
                    <div className="flex-shrink-0">
                      <div className="w-3 h-3 bg-blue-600 rounded-full"></div>
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          ))
        )}
      </div>

      {/* Create Announcement Dialog */}
      {user?.role === 'manager' && (
        <CreateAnnouncementDialog
          open={showCreateDialog}
          onOpenChange={setShowCreateDialog}
        />
      )}
    </div>
  );
}
