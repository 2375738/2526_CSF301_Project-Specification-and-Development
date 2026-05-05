@props(['name', 'class' => 'h-5 w-5'])

@switch($name)
    @case('dashboard')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 10.2 12 4l8 6.2v8.05A1.75 1.75 0 0 1 18.25 20H5.75A1.75 1.75 0 0 1 4 18.25V10.2Z" fill="currentColor" opacity=".16" />
            <path d="M4.25 10.25 12 4.25l7.75 6" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M6.5 10.75v7.5c0 .69.56 1.25 1.25 1.25h8.5c.69 0 1.25-.56 1.25-1.25v-7.5" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" />
            <path d="M10 19.5v-5h4v5" stroke="currentColor" stroke-width="2.25" stroke-linejoin="round" />
        </svg>
        @break
    @case('my-work')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <rect x="4.75" y="5" width="14.5" height="15" rx="2.4" fill="currentColor" opacity=".14" />
            <path d="M8.25 5.25h7.5M7.75 19.25h8.5A2.25 2.25 0 0 0 18.5 17V7A2.25 2.25 0 0 0 16.25 4.75h-8.5A2.25 2.25 0 0 0 5.5 7v10a2.25 2.25 0 0 0 2.25 2.25Z" stroke="currentColor" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M8.4 14.25 10.5 12l2.1 1.85 3.25-4.1" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        @break
    @case('messages')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M5 8.5A3.5 3.5 0 0 1 8.5 5h7A3.5 3.5 0 0 1 19 8.5v4.75a3.5 3.5 0 0 1-3.5 3.5h-3.1L7.25 20v-3.25A3.25 3.25 0 0 1 5 13.65V8.5Z" fill="currentColor" opacity=".15" />
            <path d="M6.5 6h11A2.5 2.5 0 0 1 20 8.5v5A2.5 2.5 0 0 1 17.5 16H12l-5 3v-3h-.5A2.5 2.5 0 0 1 4 13.5v-5A2.5 2.5 0 0 1 6.5 6Z" stroke="currentColor" stroke-width="2.15" stroke-linejoin="round" />
            <path d="M8 10h8M8 13h5.5" stroke="currentColor" stroke-width="2.15" stroke-linecap="round" />
        </svg>
        @break
    @case('knowledge')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 5.75h8.25A3.75 3.75 0 0 1 18 9.5v9H8.25A2.25 2.25 0 0 1 6 16.25V5.75Z" fill="currentColor" opacity=".14" />
            <path d="M7.25 5h7.25A3.5 3.5 0 0 1 18 8.5v10H8a2.5 2.5 0 0 1-2.5-2.5V6.75A1.75 1.75 0 0 1 7.25 5Z" stroke="currentColor" stroke-width="2.15" stroke-linejoin="round" />
            <path d="M9 9.25h5.5M9 12.25h5.5M8 18.5a2.5 2.5 0 0 1 0-5h10" stroke="currentColor" stroke-width="2.15" stroke-linecap="round" />
        </svg>
        @break
    @case('tasks')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M6 5.75h12v12.5A1.75 1.75 0 0 1 16.25 20H7.75A1.75 1.75 0 0 1 6 18.25V5.75Z" fill="currentColor" opacity=".14" />
            <path d="M7.25 4.75h9.5A2.25 2.25 0 0 1 19 7v10a2.25 2.25 0 0 1-2.25 2.25h-9.5A2.25 2.25 0 0 1 5 17V7a2.25 2.25 0 0 1 2.25-2.25Z" stroke="currentColor" stroke-width="2.15" stroke-linejoin="round" />
            <path d="M8.5 12.1 10.8 14.4l4.9-5.05" stroke="currentColor" stroke-width="2.35" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        @break
    @case('analytics')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M5 18.5h14V20H5v-1.5Z" fill="currentColor" opacity=".18" />
            <path d="M7.25 16.75v-5.5M12 16.75v-9.5M16.75 16.75v-7" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" />
            <path d="M5.25 18.75h13.5M6.25 8.75l3.25-2 3.5 2.5 4.75-4.25" stroke="currentColor" stroke-width="2.15" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        @break
    @case('profile')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="8.5" r="4" fill="currentColor" opacity=".16" />
            <path d="M12 12.25a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2.15" />
            <path d="M5.25 19.25a6.75 6.75 0 0 1 13.5 0" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" />
        </svg>
        @break
    @case('governance')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 4.5 18.25 7v4.5c0 4-2.35 6.55-6.25 7.9-3.9-1.35-6.25-3.9-6.25-7.9V7L12 4.5Z" fill="currentColor" opacity=".15" />
            <path d="M12 4.25 18.5 7v4.75c0 4.1-2.6 6.7-6.5 8-3.9-1.3-6.5-3.9-6.5-8V7L12 4.25Z" stroke="currentColor" stroke-width="2.15" stroke-linejoin="round" />
            <path d="m9.25 12.25 1.75 1.8 3.9-4.25" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        @break
    @default
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
@endswitch
