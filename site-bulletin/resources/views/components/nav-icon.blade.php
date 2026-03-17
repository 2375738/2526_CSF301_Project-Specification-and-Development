@props(['name', 'class' => 'h-5 w-5'])

@switch($name)
    @case('dashboard')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M4.75 9.5 12 4l7.25 5.5" />
            <path d="M6.5 10.75V19h11v-8.25" />
            <path d="M10 19v-5h4v5" />
        </svg>
        @break
    @case('announcements')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 5.25a4.25 4.25 0 0 1 4.25 4.25v2.1c0 .9.22 1.78.64 2.58l.86 1.57H6.25l.86-1.57c.42-.8.64-1.68.64-2.58V9.5A4.25 4.25 0 0 1 12 5.25Z" />
            <path d="M10.25 18a1.75 1.75 0 0 0 3.5 0" />
        </svg>
        @break
    @case('messages')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M6.75 7.25h10.5A2.75 2.75 0 0 1 20 10v5a2.75 2.75 0 0 1-2.75 2.75H11l-4.25 2v-2H6.75A2.75 2.75 0 0 1 4 15v-5a2.75 2.75 0 0 1 2.75-2.75Z" />
            <path d="M8.5 11.5h7" />
            <path d="M8.5 14h4.5" />
        </svg>
        @break
    @case('knowledge')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M7.75 5.5h9A2.25 2.25 0 0 1 19 7.75v10.5A1.75 1.75 0 0 1 17.25 20h-9.5A1.75 1.75 0 0 1 6 18.25V7.25A1.75 1.75 0 0 1 7.75 5.5Z" />
            <path d="M9 9.25h7" />
            <path d="M9 12.25h7" />
            <path d="M9 15.25h4.5" />
        </svg>
        @break
    @case('tasks')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="6.25" y="5.5" width="11.5" height="14" rx="2.25" />
            <path d="M9 5.5h6" />
            <path d="m9.25 12.25 1.6 1.6 3.9-4.1" />
        </svg>
        @break
    @case('profile')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 12a3.75 3.75 0 1 0-3.75-3.75A3.75 3.75 0 0 0 12 12Z" />
            <path d="M5.5 18.5a6.5 6.5 0 0 1 13 0" />
        </svg>
        @break
    @case('governance')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12 4.5 18 7v4.5c0 4.08-2.4 6.95-6 8-3.6-1.05-6-3.92-6-8V7l6-2.5Z" />
            <path d="m9.5 12.25 1.6 1.6 3.4-3.85" />
        </svg>
        @break
@endswitch
