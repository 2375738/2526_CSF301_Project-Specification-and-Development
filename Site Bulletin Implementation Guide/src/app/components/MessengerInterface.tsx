import React, { useState } from 'react';
import { Megaphone, User, Search, MoreVertical, Send, ArrowLeft } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from './ui/card';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Badge } from './ui/badge';
import { ScrollArea } from './ui/scroll-area';

interface Conversation {
  id: string;
  type: 'announcement' | 'direct';
  name: string;
  lastMessage: string;
  timestamp: string;
  unread: number;
  messages: Message[];
}

interface Message {
  id: string;
  type: 'announcement' | 'sent' | 'received';
  content: string;
  sender?: string;
  timestamp: string;
}

const mockConversations: Conversation[] = [
  {
    id: 'broadcast',
    type: 'announcement',
    name: 'Team Announcements',
    lastMessage: 'New safety protocols effective immediately...',
    timestamp: '10m',
    unread: 2,
    messages: [
      {
        id: '1',
        type: 'announcement',
        content: 'Welcome to the team announcements channel. Important updates will be posted here.',
        timestamp: '2d ago',
      },
      {
        id: '2',
        type: 'announcement',
        content: 'New safety protocols effective immediately. All team members must complete the updated safety training module by end of week.',
        timestamp: '10m ago',
      },
    ],
  },
  {
    id: 'sarah',
    type: 'direct',
    name: 'Sarah Chen',
    lastMessage: 'Great job on yesterday\'s output!',
    timestamp: '2h',
    unread: 1,
    messages: [
      {
        id: '1',
        type: 'received',
        content: 'Hey! How\'s everything going today?',
        sender: 'Sarah Chen',
        timestamp: '3h ago',
      },
      {
        id: '2',
        type: 'sent',
        content: 'Going well! Just finished processing the morning batch.',
        timestamp: '2h 30m ago',
      },
      {
        id: '3',
        type: 'received',
        content: 'Great job on yesterday\'s output!',
        sender: 'Sarah Chen',
        timestamp: '2h ago',
      },
    ],
  },
  {
    id: 'alex',
    type: 'direct',
    name: 'Alex Rivera',
    lastMessage: 'Can you help me with the new scanner?',
    timestamp: '1d',
    unread: 0,
    messages: [
      {
        id: '1',
        type: 'received',
        content: 'Can you help me with the new scanner?',
        sender: 'Alex Rivera',
        timestamp: '1d ago',
      },
      {
        id: '2',
        type: 'sent',
        content: 'Sure! I\'ll come by your station in 10 minutes.',
        timestamp: '1d ago',
      },
    ],
  },
];

export function MessengerInterface() {
  const [viewMode, setViewMode] = useState<'preview' | 'thread'>('preview');
  const [selectedConversation, setSelectedConversation] = useState<Conversation>(mockConversations[0]);
  const [messageInput, setMessageInput] = useState('');

  const handleSelectConversation = (conversation: Conversation) => {
    setSelectedConversation(conversation);
    setViewMode('thread');
  };

  const handleSendMessage = (e: React.FormEvent) => {
    e.preventDefault();
    if (!messageInput.trim()) return;

    // In a real app, this would send the message
    console.log('Sending message:', messageInput);
    setMessageInput('');
  };

  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase();
  };

  if (viewMode === 'preview') {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-green-50 p-4 md:p-8 pb-20 lg:pb-0">
        <div className="max-w-5xl mx-auto space-y-6">
          <div>
            <h1 className="text-slate-900 mb-2">The Messenger Interface</h1>
            <p className="text-slate-600 mb-4">
              Modern, human, and glanceable communication within the portal.
            </p>
            <h2 className="text-slate-900">Glanceable Preview: Recent Chats</h2>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {/* Broadcast Announcement */}
            <Card
              onClick={() => handleSelectConversation(mockConversations[0])}
              className="bg-gradient-to-br from-blue-400 to-blue-600 border-0 cursor-pointer hover:shadow-lg transition-all"
            >
              <CardContent className="pt-6">
                <div className="flex flex-col items-center text-center space-y-4">
                  <div className="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                    <Megaphone className="w-8 h-8 text-white" />
                  </div>
                  <div className="space-y-2">
                    <h3 className="text-white">Team Announcements</h3>
                    <p className="text-sm text-blue-50 line-clamp-2">
                      {mockConversations[0].lastMessage}
                    </p>
                  </div>
                  <div className="flex items-center gap-2 text-xs text-blue-50">
                    <span>Team Comm</span>
                    <span>•</span>
                    <span>{mockConversations[0].timestamp} ago</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Direct Messages */}
            {mockConversations.slice(1).map((conversation) => (
              <Card
                key={conversation.id}
                onClick={() => handleSelectConversation(conversation)}
                className="border-slate-200 cursor-pointer hover:shadow-lg transition-all"
              >
                <CardContent className="pt-6">
                  <div className="flex flex-col items-center text-center space-y-4">
                    <div className="w-16 h-16 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white text-xl">
                      {getInitials(conversation.name)}
                    </div>
                    <div className="space-y-2">
                      <h3 className="text-slate-900">{conversation.name}</h3>
                      <p className="text-sm text-slate-600 line-clamp-2">
                        {conversation.lastMessage}
                      </p>
                    </div>
                    <div className="flex items-center gap-2 text-xs text-slate-500">
                      <span>Direct Message</span>
                      <span>•</span>
                      <span>{conversation.timestamp} ago</span>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>

          <Button
            onClick={() => setViewMode('thread')}
            variant="outline"
            className="w-full min-h-[44px]"
          >
            View All Conversations
          </Button>
        </div>
      </div>
    );
  }

  // Thread View
  return (
    <div className="flex h-[calc(100vh-80px)] lg:h-screen bg-white">
      {/* Conversations List */}
      <div className="hidden lg:block w-80 border-r border-slate-200 flex-shrink-0">
        <div className="p-4 border-b border-slate-200">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-slate-900">Messages</h2>
            <Button
              onClick={() => setViewMode('preview')}
              variant="ghost"
              size="sm"
            >
              Preview
            </Button>
          </div>
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-slate-400" />
            <Input placeholder="Search..." className="pl-10 min-h-[44px]" />
          </div>
        </div>
        <ScrollArea className="h-[calc(100%-120px)]">
          <div className="p-2">
            {mockConversations.map((conversation) => (
              <div
                key={conversation.id}
                onClick={() => setSelectedConversation(conversation)}
                className={`flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors mb-1 min-h-[44px] ${
                  selectedConversation.id === conversation.id
                    ? 'bg-blue-50'
                    : 'hover:bg-slate-50'
                }`}
              >
                {conversation.type === 'announcement' ? (
                  <div className="w-12 h-12 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                    <Megaphone className="w-6 h-6 text-white" />
                  </div>
                ) : (
                  <div className="w-12 h-12 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white flex-shrink-0">
                    {getInitials(conversation.name)}
                  </div>
                )}
                <div className="flex-1 min-w-0">
                  <div className="flex items-center justify-between mb-1">
                    <p className="text-sm text-slate-900 truncate">{conversation.name}</p>
                    <span className="text-xs text-slate-500">{conversation.timestamp}</span>
                  </div>
                  <p className="text-xs text-slate-600 truncate">{conversation.lastMessage}</p>
                </div>
                {conversation.unread > 0 && (
                  <Badge className="bg-blue-600 text-white hover:bg-blue-600">
                    {conversation.unread}
                  </Badge>
                )}
              </div>
            ))}
          </div>
        </ScrollArea>
      </div>

      {/* Chat Thread */}
      <div className="flex-1 flex flex-col">
        {/* Chat Header */}
        <div className="p-4 border-b border-slate-200 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Button
              onClick={() => setViewMode('preview')}
              variant="ghost"
              size="sm"
              className="lg:hidden"
            >
              <ArrowLeft className="w-5 h-5" />
            </Button>
            {selectedConversation.type === 'announcement' ? (
              <div className="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center">
                <Megaphone className="w-5 h-5 text-white" />
              </div>
            ) : (
              <div className="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white">
                {getInitials(selectedConversation.name)}
              </div>
            )}
            <div>
              <p className="text-sm text-slate-900">{selectedConversation.name}</p>
              <p className="text-xs text-slate-500">
                {selectedConversation.type === 'announcement' ? 'Broadcast Channel' : 'Online'}
              </p>
            </div>
          </div>
          <Button variant="ghost" size="sm">
            <MoreVertical className="w-5 h-5" />
          </Button>
        </div>

        {/* Messages */}
        <ScrollArea className="flex-1 p-4">
          <div className="space-y-4 max-w-3xl mx-auto">
            {selectedConversation.messages.map((message) => (
              <div key={message.id}>
                {message.type === 'announcement' ? (
                  <div className="p-4 bg-purple-50 border border-purple-200 rounded-lg">
                    <div className="flex items-center gap-2 mb-2">
                      <Megaphone className="w-4 h-4 text-purple-600" />
                      <Badge className="bg-purple-100 text-purple-700 hover:bg-purple-100">
                        Announcement
                      </Badge>
                    </div>
                    <p className="text-sm text-slate-900">{message.content}</p>
                    <p className="text-xs text-slate-500 mt-2">{message.timestamp}</p>
                  </div>
                ) : message.type === 'sent' ? (
                  <div className="flex justify-end">
                    <div className="max-w-md">
                      <div className="bg-blue-600 text-white rounded-lg px-4 py-2">
                        <p className="text-sm">{message.content}</p>
                      </div>
                      <p className="text-xs text-slate-500 mt-1 text-right">{message.timestamp}</p>
                    </div>
                  </div>
                ) : (
                  <div className="flex gap-3">
                    <div className="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white text-xs flex-shrink-0">
                      {getInitials(message.sender || '')}
                    </div>
                    <div className="max-w-md">
                      <div className="bg-slate-100 rounded-lg px-4 py-2">
                        <p className="text-sm text-slate-900">{message.content}</p>
                      </div>
                      <p className="text-xs text-slate-500 mt-1">{message.timestamp}</p>
                    </div>
                  </div>
                )}
              </div>
            ))}
          </div>
        </ScrollArea>

        {/* Message Input */}
        <div className="p-4 border-t border-slate-200">
          <form onSubmit={handleSendMessage} className="flex gap-3 items-center max-w-3xl mx-auto">
            {selectedConversation.type === 'announcement' ? (
              <div className="flex-1 p-3 bg-slate-50 rounded-full border border-slate-200">
                <p className="text-sm text-slate-500">
                  This is a broadcast channel. Only admins can post.
                </p>
              </div>
            ) : (
              <>
                <Input
                  placeholder="Type a message..."
                  value={messageInput}
                  onChange={(e) => setMessageInput(e.target.value)}
                  className="flex-1 rounded-full min-h-[44px]"
                />
                <Button
                  type="submit"
                  className="bg-blue-600 hover:bg-blue-700 text-white rounded-full w-12 h-12 p-0 flex-shrink-0"
                >
                  <Send className="w-5 h-5" />
                </Button>
              </>
            )}
          </form>
        </div>
      </div>
    </div>
  );
}
