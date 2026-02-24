import React, { useState } from 'react';
import { Bell } from 'lucide-react';
import { useAuth, UserRole, Department } from '../contexts/AuthContext';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Label } from './ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';

export function LoginScreen() {
  const { login } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [role, setRole] = useState<UserRole>('employee');
  const [department, setDepartment] = useState<Department>('Operations');

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    login(email, password, role, department);
  };

  const quickLogin = (preset: 'employee' | 'manager') => {
    if (preset === 'employee') {
      login('jordan.smith@company.com', 'demo', 'employee', 'Operations');
    } else {
      login('sarah.chen@company.com', 'demo', 'manager', 'Warehouse');
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-slate-50 flex items-center justify-center p-4">
      <div className="w-full max-w-md">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
            <Bell className="w-8 h-8 text-white" />
          </div>
          <h1 className="text-slate-900 mb-2">Site Bulletin</h1>
          <p className="text-slate-600">Operations Portal</p>
        </div>

        {/* Quick Demo Login */}
        <Card className="border-slate-200 mb-6">
          <CardHeader className="pb-4">
            <CardTitle className="text-slate-900">Quick Demo Login</CardTitle>
            <CardDescription>Try the portal with demo accounts</CardDescription>
          </CardHeader>
          <CardContent className="pt-4 space-y-3 border-t border-slate-200">
            <Button
              onClick={() => quickLogin('employee')}
              className="w-full min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white"
            >
              Login as Employee - Jordan Smith (Operations)
            </Button>
            <Button
              onClick={() => quickLogin('manager')}
              variant="outline"
              className="w-full min-h-[44px] border-slate-300"
            >
              Login as Manager - Sarah Chen (Warehouse)
            </Button>
          </CardContent>
        </Card>

        {/* Login Form */}
        <Card className="border-slate-200">
          <CardHeader>
            <CardTitle className="text-slate-900">Sign In</CardTitle>
            <CardDescription>Enter your credentials to access the portal</CardDescription>
          </CardHeader>
          <CardContent className="pt-6">
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="email">Email Address</Label>
                <Input
                  id="email"
                  type="email"
                  placeholder="jordan.smith@company.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="min-h-[44px]"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">Password</Label>
                <Input
                  id="password"
                  type="password"
                  placeholder="••••••••"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="min-h-[44px]"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="role">Role</Label>
                <Select value={role} onValueChange={(value) => setRole(value as UserRole)}>
                  <SelectTrigger id="role" className="min-h-[44px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="employee">Employee</SelectItem>
                    <SelectItem value="manager">Manager</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="department">Department</Label>
                <Select value={department} onValueChange={(value) => setDepartment(value as Department)}>
                  <SelectTrigger id="department" className="min-h-[44px]">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Operations">Operations</SelectItem>
                    <SelectItem value="Warehouse">Warehouse</SelectItem>
                    <SelectItem value="HR">HR</SelectItem>
                    <SelectItem value="Safety">Safety</SelectItem>
                    <SelectItem value="IT">IT</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <Button type="submit" className="w-full min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white">
                Sign In
              </Button>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
