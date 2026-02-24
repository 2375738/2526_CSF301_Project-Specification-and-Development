import React, { useState } from 'react';
import { Home, MessageSquare, Bell, CheckSquare, UserCircle, Shield, ChevronLeft, ChevronRight } from 'lucide-react';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import { AnnouncementProvider, useAnnouncements } from './contexts/AnnouncementContext';
import { LoginScreen } from './components/LoginScreen';
import { EmployeeDashboard } from './components/EmployeeDashboard';
import { ManagerDashboard } from './components/ManagerDashboard';
import { MessengerInterface } from './components/MessengerInterface';
import { AnnouncementsView } from './components/AnnouncementsView';
import { MyTasksView } from './components/MyTasksView';
import { EditableProfile } from './components/EditableProfile';
import { UserProfile } from './components/UserProfile';
import { Button } from './components/ui/button';
import { Badge } from './components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from './components/ui/tooltip';
import { Toaster } from './components/ui/sonner';

function AppContent() {
  const { user, isAuthenticated } = useAuth();
  const { getUnreadCount } = useAnnouncements();
  const [activeTab, setActiveTab] = useState('dashboard');
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

  if (!isAuthenticated) {
    return <LoginScreen />;
  }

  const navItems = [
    { id: 'dashboard', icon: Home, label: 'Dashboard' },
    { id: 'announcements', icon: Bell, label: 'Announcements', badge: getUnreadCount() },
    { id: 'messenger', icon: MessageSquare, label: 'Messenger', badge: 3 },
    { id: 'tasks', icon: CheckSquare, label: 'My Tasks' },
    { id: 'profile', icon: UserCircle, label: 'Profile' },
    { id: 'governance', icon: Shield, label: 'Governance' }, // Desktop only
  ];

  const renderContent = () => {
    if (activeTab === 'messenger') return <MessengerInterface />;
    if (activeTab === 'announcements') return <AnnouncementsView />;
    if (activeTab === 'tasks') return <MyTasksView />;
    if (activeTab === 'profile') return <EditableProfile />;
    if (activeTab === 'governance') {
      return (
        <div className="p-4 md:p-8 pb-20 lg:pb-0">
          <h1 className="text-slate-900 mb-4">Governance</h1>
          <p className="text-slate-600">Governance features coming soon...</p>
        </div>
      );
    }
    if (user?.role === 'employee') return <EmployeeDashboard />;
    return <ManagerDashboard />;
  };

  return (
    <TooltipProvider>
      <div className="flex h-screen bg-slate-50">
        {/* Desktop Sidebar */}
        <aside
          className={`hidden lg:flex flex-col bg-white border-r border-slate-200 transition-all duration-300 ${
            sidebarCollapsed ? 'w-20' : 'w-64'
          }`}
        >
          {/* Logo */}
          <div className="p-4 border-b border-slate-200">
            {!sidebarCollapsed ? (
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center">
                  <Bell className="w-5 h-5 text-white" />
                </div>
                <div>
                  <h2 className="text-slate-900">Site Bulletin</h2>
                  <p className="text-xs text-slate-500">Operations Portal</p>
                </div>
              </div>
            ) : (
              <div className="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center mx-auto">
                <Bell className="w-5 h-5 text-white" />
              </div>
            )}
          </div>

          {/* Navigation */}
          <nav className="flex-1 p-3 space-y-1 overflow-y-auto">
            {navItems.map((item) => {
              const Icon = item.icon;
              const isActive = activeTab === item.id;

              return (
                <Tooltip key={item.id} delayDuration={0}>
                  <TooltipTrigger asChild>
                    <div>
                      <Button
                        variant="ghost"
                        onClick={() => setActiveTab(item.id)}
                        className={`w-full justify-start min-h-[44px] ${
                          isActive
                            ? 'bg-blue-50 text-blue-700 hover:bg-blue-50'
                            : 'text-slate-600 hover:bg-slate-50'
                        } ${sidebarCollapsed ? 'justify-center' : ''}`}
                      >
                        <Icon className="w-5 h-5 flex-shrink-0" />
                        {!sidebarCollapsed && (
                          <>
                            <span className="ml-3 flex-1 text-left">{item.label}</span>
                            {item.badge > 0 && (
                              <Badge className="bg-blue-600 text-white hover:bg-blue-600 ml-2">
                                {item.badge}
                              </Badge>
                            )}
                          </>
                        )}
                      </Button>
                    </div>
                  </TooltipTrigger>
                  {sidebarCollapsed && (
                    <TooltipContent side="right">
                      <p>{item.label}</p>
                      {item.badge > 0 && <p className="text-xs">({item.badge} unread)</p>}
                    </TooltipContent>
                  )}
                </Tooltip>
              );
            })}
          </nav>

          {/* User Profile */}
          <div className="border-t border-slate-200">
            <UserProfile collapsed={sidebarCollapsed} />
          </div>

          {/* Collapse Toggle */}
          <div className="p-3 border-t border-slate-200">
            <Button
              variant="ghost"
              onClick={() => setSidebarCollapsed(!sidebarCollapsed)}
              className="w-full min-h-[44px]"
            >
              {sidebarCollapsed ? (
                <ChevronRight className="w-5 h-5" />
              ) : (
                <>
                  <ChevronLeft className="w-5 h-5" />
                  <span className="ml-3 flex-1 text-left">Collapse</span>
                </>
              )}
            </Button>
          </div>
        </aside>

        {/* Main Content */}
        <main className="flex-1 overflow-auto">
          {renderContent()}
        </main>

        {/* Mobile Bottom Navigation */}
        <nav className="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 z-50">
          <div className="flex items-center justify-around px-2 py-2">
            {navItems.slice(0, 5).map((item) => {
              const Icon = item.icon;
              const isActive = activeTab === item.id;

              return (
                <Button
                  key={item.id}
                  variant="ghost"
                  onClick={() => setActiveTab(item.id)}
                  className={`flex flex-col items-center gap-1 min-h-[44px] px-3 ${
                    isActive ? 'text-blue-600' : 'text-slate-600'
                  }`}
                >
                  <div className="relative">
                    <Icon className="w-5 h-5" />
                    {item.badge > 0 && (
                      <Badge className="absolute -top-2 -right-2 bg-blue-600 text-white hover:bg-blue-600 h-4 min-w-[16px] p-0 flex items-center justify-center text-xs">
                        {item.badge}
                      </Badge>
                    )}
                  </div>
                  <span className="text-xs">{item.label}</span>
                </Button>
              );
            })}
          </div>
        </nav>
      </div>
      <Toaster />
    </TooltipProvider>
  );
}

export default function App() {
  return (
    <AuthProvider>
      <AnnouncementProvider>
        <AppContent />
      </AnnouncementProvider>
    </AuthProvider>
  );
}