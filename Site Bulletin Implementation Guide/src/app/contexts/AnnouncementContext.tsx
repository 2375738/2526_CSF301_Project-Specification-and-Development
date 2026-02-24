import React, { createContext, useContext, useState, ReactNode } from 'react';
import { Department } from './AuthContext';

export interface Announcement {
  id: string;
  title: string;
  content: string;
  from: string;
  fromRole: 'manager' | 'admin' | 'hr';
  department: Department | 'All Departments';
  priority: 'low' | 'medium' | 'high' | 'urgent';
  timestamp: Date;
  read: boolean;
}

interface AnnouncementContextType {
  announcements: Announcement[];
  addAnnouncement: (announcement: Omit<Announcement, 'id' | 'timestamp' | 'read'>) => void;
  markAsRead: (id: string) => void;
  markAllAsRead: () => void;
  getUnreadCount: () => number;
}

const AnnouncementContext = createContext<AnnouncementContextType | undefined>(undefined);

const initialAnnouncements: Announcement[] = [
  {
    id: 'ann-1',
    title: 'New Safety Protocols Effective Immediately',
    content: 'All team members must complete the updated safety training module by end of week. This includes new procedures for warehouse equipment operation and emergency protocols.',
    from: 'Sarah Chen',
    fromRole: 'manager',
    department: 'All Departments',
    priority: 'urgent',
    timestamp: new Date(Date.now() - 1000 * 60 * 30), // 30 minutes ago
    read: false,
  },
  {
    id: 'ann-2',
    title: 'Q1 Performance Review Schedule',
    content: 'Performance reviews will be conducted March 15-20. Please ensure your self-assessment forms are submitted by March 10th.',
    from: 'HR Team',
    fromRole: 'hr',
    department: 'All Departments',
    priority: 'high',
    timestamp: new Date(Date.now() - 1000 * 60 * 60 * 2), // 2 hours ago
    read: false,
  },
  {
    id: 'ann-3',
    title: 'System Maintenance This Weekend',
    content: 'Our ticketing system will be offline Saturday 2AM-6AM for scheduled maintenance. Plan accordingly.',
    from: 'IT Department',
    fromRole: 'admin',
    department: 'All Departments',
    priority: 'medium',
    timestamp: new Date(Date.now() - 1000 * 60 * 60 * 5), // 5 hours ago
    read: true,
  },
  {
    id: 'ann-4',
    title: 'Warehouse Team Meeting - Thursday 3PM',
    content: 'All warehouse staff please attend the weekly sync in Conference Room B. We\'ll discuss new inventory management procedures.',
    from: 'Sarah Chen',
    fromRole: 'manager',
    department: 'Warehouse',
    priority: 'medium',
    timestamp: new Date(Date.now() - 1000 * 60 * 60 * 24), // 1 day ago
    read: true,
  },
  {
    id: 'ann-5',
    title: 'Company Picnic - Save the Date!',
    content: 'Join us for our annual company picnic on April 15th at Riverside Park. Food, games, and prizes for the whole family!',
    from: 'HR Team',
    fromRole: 'hr',
    department: 'All Departments',
    priority: 'low',
    timestamp: new Date(Date.now() - 1000 * 60 * 60 * 48), // 2 days ago
    read: true,
  },
];

export function AnnouncementProvider({ children }: { children: ReactNode }) {
  const [announcements, setAnnouncements] = useState<Announcement[]>(initialAnnouncements);

  const addAnnouncement = (announcement: Omit<Announcement, 'id' | 'timestamp' | 'read'>) => {
    const newAnnouncement: Announcement = {
      ...announcement,
      id: `ann-${Date.now()}`,
      timestamp: new Date(),
      read: false,
    };
    setAnnouncements(prev => [newAnnouncement, ...prev]);
  };

  const markAsRead = (id: string) => {
    setAnnouncements(prev =>
      prev.map(ann => (ann.id === id ? { ...ann, read: true } : ann))
    );
  };

  const markAllAsRead = () => {
    setAnnouncements(prev => prev.map(ann => ({ ...ann, read: true })));
  };

  const getUnreadCount = () => {
    return announcements.filter(ann => !ann.read).length;
  };

  return (
    <AnnouncementContext.Provider
      value={{ announcements, addAnnouncement, markAsRead, markAllAsRead, getUnreadCount }}
    >
      {children}
    </AnnouncementContext.Provider>
  );
}

export function useAnnouncements() {
  const context = useContext(AnnouncementContext);
  if (!context) {
    throw new Error('useAnnouncements must be used within an AnnouncementProvider');
  }
  return context;
}
