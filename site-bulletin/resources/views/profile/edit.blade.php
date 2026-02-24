@extends('layouts.app')

@extends('layouts.app')

@extends('layouts.app')

@section('content')
<div x-data="{ activeTab: 'job' }" class="space-y-6">
    
    <!-- Profile Header -->
    <div class="bg-white border border-slate-200 rounded-lg p-6 flex items-start justify-between shadow-sm">
        <div class="flex items-start gap-6">
            <!-- Icon Avatar -->
            <div class="h-20 w-20 bg-orange-100 rounded-md flex items-center justify-center text-orange-500 border border-orange-200">
                <svg class="h-12 w-12" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    {{ $user->name }} 
                    @if($user->pronouns)
                        <span class="text-slate-500 font-normal text-base">({{ $user->pronouns }})</span>
                    @endif
                </h1>
                <div class="text-sm text-slate-600 mt-2 space-y-1">
                    <p class="font-medium">{{ $user->job_title ?? 'Associate' }}</p>
                    <p>{{ $user->location ?? 'Site' }}</p>
                    <a href="#" class="text-blue-600 hover:underline text-xs font-medium">View Phone Tool</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button @click="activeTab = 'job'" 
                    :class="{ 'border-blue-600 text-slate-900': activeTab === 'job', 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300': activeTab !== 'job' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-colors duration-150">
                Job details and history
            </button>
            <button @click="activeTab = 'personal'" 
                    :class="{ 'border-blue-600 text-slate-900': activeTab === 'personal', 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300': activeTab !== 'personal' }"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-colors duration-150">
                Manage personal information
            </button>
        </nav>
    </div>

    <!-- Job Details Tab -->
    <div x-show="activeTab === 'job'" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Job Details & History -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Job Details -->
            <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-4">Job details</h2>
                <div class="space-y-4">
                    <div>
                        <p class="text-slate-500 text-sm">Login</p>
                        <p class="font-medium text-slate-900">{{ $user->email }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-sm">Employee ID</p>
                        <p class="font-medium text-slate-900">{{ $user->employee_id ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-sm">Job Title</p>
                        <p class="font-medium text-slate-900">{{ $user->job_title ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-sm">Department</p>
                        <p class="font-medium text-slate-900">{{ $user->primaryDepartment->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 text-sm">Manager</p>
                        <div class="flex items-center gap-2 mt-1">
                            @if($manager)
                                <a href="#" class="text-blue-600 hover:underline font-medium">{{ $manager->name }}</a>
                                <span class="text-slate-400 text-xs">({{ $manager->email }})</span>
                            @else
                                <p class="text-slate-400 italic">No manager assigned</p>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-slate-500 text-sm">Site / Location</p>
                        <p class="font-medium text-slate-900">{{ $user->location ?? '-' }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <button class="text-blue-600 text-sm hover:underline font-medium flex items-center gap-1">
                        Show more 
                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Job History -->
            <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-6">Job history</h2>
                <div class="relative border-l-2 border-slate-200 ml-3 space-y-10 pb-2">
                    @foreach($jobHistory as $job)
                        <div class="relative pl-8">
                            <!-- Dot -->
                            <div class="absolute -left-[9px] top-1 h-4 w-4 rounded-full border-2 border-white {{ $job['status'] === 'current' ? 'bg-blue-600' : 'bg-slate-300' }}"></div>
                            
                            <!-- Content -->
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 mb-2">
                                    Manager change
                                </span>
                                <p class="text-xs text-slate-500 mb-1 font-medium">{{ $job['start_date'] }} <span class="font-normal text-slate-400">({{ $job['duration'] }})</span></p>
                                <h3 class="text-sm font-bold text-slate-900">{{ $job['role'] }}</h3>
                                <p class="text-sm text-slate-600 mt-1">Department: {{ $job['department'] }}</p>
                                <p class="text-sm text-slate-600">
                                    Manager: <a href="mailto:{{ $job['manager_email'] }}" class="text-blue-600 hover:underline">{{ $job['manager'] }}</a>
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Column: Resources -->
        <div class="space-y-6">
            <div class="bg-white shadow-sm rounded-lg border border-slate-200 p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-4">Resources</h2>
                <ul class="space-y-4 text-sm">
                    <li><a href="#" class="text-blue-600 hover:underline flex justify-between items-center group">
                        <span>Events timeline</span>
                        <svg class="h-4 w-4 text-slate-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a></li>
                    <li><a href="#" class="text-blue-600 hover:underline flex justify-between items-center group">
                        <span>Benefits</span>
                        <svg class="h-4 w-4 text-slate-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a></li>
                    <li><a href="#" class="text-blue-600 hover:underline flex justify-between items-center group">
                        <span>Personal bank accounts</span>
                        <svg class="h-4 w-4 text-slate-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a></li>
                    <li><a href="#" class="text-blue-600 hover:underline flex justify-between items-center group">
                        <span>Payroll information</span>
                        <svg class="h-4 w-4 text-slate-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a></li>
                    <li><a href="#" class="text-blue-600 hover:underline flex justify-between items-center group">
                        <span>Compensation statements</span>
                        <svg class="h-4 w-4 text-slate-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a></li>
                    <li><a href="#" class="text-blue-600 hover:underline flex justify-between items-center group">
                        <span>Employee discount</span>
                        <svg class="h-4 w-4 text-slate-400 group-hover:text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                    </a></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Personal Info Tab -->
    <div x-show="activeTab === 'personal'" class="grid gap-6 lg:grid-cols-2" style="display: none;">
        <div class="p-6 bg-white shadow-sm rounded-lg border border-slate-200">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="space-y-6">
            <div class="p-6 bg-white shadow-sm rounded-lg border border-slate-200">
                @include('profile.partials.update-password-form')
            </div>
            <div class="p-6 bg-white shadow-sm rounded-lg border border-slate-200">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection
