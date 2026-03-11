@props(['name', 'class' => 'h-5 w-5'])

@switch($name)
    @case('dashboard')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10.75 2.75a.75.75 0 00-1.5 0v.94a6.5 6.5 0 00-4.58 11.11c.72.72 1.57 1.27 2.5 1.62v.83a.75.75 0 001.5 0v-.43c.44.08.89.12 1.33.12.47 0 .94-.04 1.42-.13v.44a.75.75 0 001.5 0v-.84a6.51 6.51 0 004.5-6.22 6.5 6.5 0 00-5.25-6.38v-.96zM10 5.15a5 5 0 015 5v4.06a5 5 0 11-10 0V10.15a5 5 0 015-5z" />
        </svg>
        @break
    @case('announcements')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 2.5a2.25 2.25 0 00-2.25 2.25v.46a5.5 5.5 0 00-3.5 5.12v2.3l-.94 1.89A.75.75 0 004 15.6h4.27a1.75 1.75 0 003.46 0H16a.75.75 0 00.67-1.08l-.92-1.89v-2.3a5.5 5.5 0 00-3.5-5.12v-.46A2.25 2.25 0 0010 2.5zM9.8 16.1h.4a.25.25 0 01-.4 0z" />
        </svg>
        @break
    @case('messages')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M3.75 4A1.75 1.75 0 002 5.75v7.5C2 14.22 2.78 15 3.75 15h2.54l2.97 2.44a.75.75 0 001.24-.58V15h5.75A1.75 1.75 0 0018 13.25v-7.5A1.75 1.75 0 0016.25 4H3.75z" />
        </svg>
        @break
    @case('knowledge')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M4.75 3A1.75 1.75 0 003 4.75v10.5C3 16.22 3.78 17 4.75 17h10.5A1.75 1.75 0 0017 15.25V4.75A1.75 1.75 0 0015.25 3H4.75zm1.5 3.25a.75.75 0 01.75-.75h6a.75.75 0 010 1.5H7a.75.75 0 01-.75-.75zm0 3.5A.75.75 0 017 9h6a.75.75 0 010 1.5H7a.75.75 0 01-.75-.75zm0 3.5A.75.75 0 017 12.5h3.5a.75.75 0 010 1.5H7a.75.75 0 01-.75-.75z" />
        </svg>
        @break
    @case('tasks')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.5 3a2.5 2.5 0 00-2.5 2.5v9A2.5 2.5 0 005.5 17h9a2.5 2.5 0 002.5-2.5v-9A2.5 2.5 0 0014.5 3h-9zM13.78 7.78a.75.75 0 10-1.06-1.06L8.75 10.69 7.28 9.22a.75.75 0 10-1.06 1.06l2 2a.75.75 0 001.06 0l4.5-4.5z" clip-rule="evenodd" />
        </svg>
        @break
    @case('profile')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 2a4 4 0 100 8 4 4 0 000-8zM3 16a7 7 0 1114 0v1H3v-1z" />
        </svg>
        @break
    @case('governance')
        <svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 2.5a.75.75 0 01.3.06l5.5 2.38a.75.75 0 01.45.69v3.54c0 3.73-2.29 6.77-5.86 8.18a1.1 1.1 0 01-.78 0C6.04 15.94 3.75 12.9 3.75 9.17V5.63a.75.75 0 01.45-.69l5.5-2.38A.75.75 0 0110 2.5zm2.03 5.72a.75.75 0 10-1.06-1.06L9 9.13 8.03 8.16a.75.75 0 10-1.06 1.06l1.5 1.5a.75.75 0 001.06 0l3.5-3.5z" clip-rule="evenodd" />
        </svg>
        @break
@endswitch
