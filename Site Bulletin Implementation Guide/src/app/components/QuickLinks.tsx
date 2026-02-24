import React from 'react';
import { ChevronRight } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';

interface LinkItem {
  title: string;
  url: string;
  hot?: boolean;
}

interface LinkCategory {
  title: string;
  items: LinkItem[];
}

const employeeLinks: LinkCategory[] = [
  {
    title: 'Hot Topics',
    items: [
      { title: 'New Safety Protocols', url: '#', hot: true },
      { title: 'Q1 Performance Reviews', url: '#', hot: true },
      { title: 'Benefits Enrollment 2026', url: '#', hot: true },
      { title: 'Training Schedule', url: '#' },
    ],
  },
  {
    title: 'My Site',
    items: [
      { title: 'Car Park Map', url: '#' },
      { title: 'Canteen Menu', url: '#' },
      { title: 'Building Directory', url: '#' },
      { title: 'Emergency Contacts', url: '#' },
    ],
  },
  {
    title: 'Diversity, Equity & Inclusion',
    items: [
      { title: 'DEI Resources', url: '#' },
      { title: 'Employee Resource Groups', url: '#' },
      { title: 'Accessibility Support', url: '#' },
    ],
  },
  {
    title: 'Site Tools',
    items: [
      { title: 'Timesheet Portal', url: '#' },
      { title: 'Pay Stub Access', url: '#' },
      { title: 'PTO Request', url: '#' },
      { title: 'Equipment Checkout', url: '#' },
    ],
  },
];

const managerLinks: LinkCategory[] = [
  {
    title: 'Hot Topics',
    items: [
      { title: 'Team Analytics Dashboard', url: '#', hot: true },
      { title: 'Performance Review Guide', url: '#', hot: true },
      { title: 'Budget Planning Q2', url: '#', hot: true },
      { title: 'Safety Compliance Report', url: '#' },
    ],
  },
  {
    title: 'Department Management',
    items: [
      { title: 'Team Scheduling', url: '#' },
      { title: 'Approval Queue', url: '#' },
      { title: 'Workforce Planning', url: '#' },
      { title: 'Training Assignments', url: '#' },
    ],
  },
  {
    title: 'Reporting & Analytics',
    items: [
      { title: 'KPI Dashboard', url: '#' },
      { title: 'Productivity Reports', url: '#' },
      { title: 'Quality Metrics', url: '#' },
      { title: 'Custom Reports', url: '#' },
    ],
  },
  {
    title: 'Resources',
    items: [
      { title: 'Manager Handbook', url: '#' },
      { title: 'HR Policies', url: '#' },
      { title: 'Coaching Templates', url: '#' },
      { title: 'Best Practices Library', url: '#' },
    ],
  },
];

export function QuickLinks() {
  const { user } = useAuth();
  const links = user?.role === 'manager' ? managerLinks : employeeLinks;

  return (
    <Card className="border-slate-200">
      <CardHeader>
        <CardTitle className="text-slate-900">Quick Links</CardTitle>
      </CardHeader>
      <CardContent className="pt-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {links.map((category) => (
            <div key={category.title} className="space-y-3">
              <h3 className="text-slate-900">{category.title}</h3>
              <div className="space-y-1">
                {category.items.map((link) => (
                  <a
                    key={link.title}
                    href={link.url}
                    className="flex items-center justify-between p-2 rounded-lg hover:bg-slate-50 transition-colors group min-h-[44px]"
                  >
                    <div className="flex items-center gap-2 flex-1">
                      <span className="text-sm text-slate-700">{link.title}</span>
                      {link.hot && (
                        <Badge className="bg-red-100 text-red-700 hover:bg-red-100 text-xs px-2">
                          HOT
                        </Badge>
                      )}
                    </div>
                    <ChevronRight className="w-4 h-4 text-slate-400 group-hover:text-slate-600 flex-shrink-0" />
                  </a>
                ))}
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}
