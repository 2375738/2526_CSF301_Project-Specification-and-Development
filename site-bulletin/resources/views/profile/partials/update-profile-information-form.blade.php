<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Personal Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your contact details and personal preferences.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name -->
            <div class="col-span-1">
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <!-- Pronouns -->
            <div class="col-span-1">
                <x-input-label for="pronouns" :value="__('Pronouns')" />
                <x-text-input id="pronouns" name="pronouns" type="text" class="mt-1 block w-full" :value="old('pronouns', $user->pronouns)" placeholder="e.g. He/Him" />
                <x-input-error class="mt-2" :messages="$errors->get('pronouns')" />
            </div>

            <!-- Email -->
            <div class="col-span-1">
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div>
                        <p class="text-sm mt-2 text-gray-800">
                            {{ __('Your email address is unverified.') }}

                            <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('Click here to re-send the verification email.') }}
                            </button>
                        </p>

                        @if (session('status') === 'verification-link-sent')
                            <p class="mt-2 font-medium text-sm text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Phone -->
            <div class="col-span-1">
                <x-input-label for="phone" :value="__('Phone Number')" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <!-- Job Title -->
            <div class="col-span-1">
                <x-input-label for="job_title" :value="__('Job Title')" />
                <x-text-input id="job_title" name="job_title" type="text" class="mt-1 block w-full" :value="old('job_title', $user->job_title)" />
                <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
            </div>

            <!-- Employee ID -->
            <div class="col-span-1">
                <x-input-label for="employee_id" :value="__('Employee ID')" />
                <x-text-input id="employee_id" name="employee_id" type="text" class="mt-1 block w-full" :value="old('employee_id', $user->employee_id)" />
                <x-input-error class="mt-2" :messages="$errors->get('employee_id')" />
            </div>

            <!-- Location -->
            <div class="col-span-2">
                <x-input-label for="location" :value="__('Location / Site')" />
                <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" :value="old('location', $user->location)" />
                <x-input-error class="mt-2" :messages="$errors->get('location')" />
            </div>

            <!-- Emergency Contact -->
            <div class="col-span-2">
                <x-input-label for="emergency_contact" :value="__('Emergency Contact Info')" />
                <textarea id="emergency_contact" name="emergency_contact" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="3">{{ old('emergency_contact', $user->emergency_contact) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('emergency_contact')" />
            </div>

            <!-- Notification Preferences -->
            <div class="col-span-2 space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-sm font-semibold text-slate-900">{{ __('Notification Preferences') }}</p>

                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-800">{{ __('Email Notifications') }}</p>
                        <p class="text-xs text-slate-500">{{ __('Receive operational updates in your notification feed (and email channels when configured).') }}</p>
                    </div>
                    <div>
                        <input type="hidden" name="email_notifications_enabled" value="0">
                        <input
                            id="email_notifications_enabled"
                            name="email_notifications_enabled"
                            type="checkbox"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            @checked((bool) old('email_notifications_enabled', $user->email_notifications_enabled ?? true))
                        >
                    </div>
                </div>
                <x-input-error class="mt-1" :messages="$errors->get('email_notifications_enabled')" />

                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-800">{{ __('Slack Notifications') }}</p>
                        <p class="text-xs text-slate-500">{{ __('Enable Slack delivery for urgent updates (when configured).') }}</p>
                    </div>
                    <div>
                        <input type="hidden" name="slack_notifications_enabled" value="0">
                        <input
                            id="slack_notifications_enabled"
                            name="slack_notifications_enabled"
                            type="checkbox"
                            value="1"
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            @checked((bool) old('slack_notifications_enabled', $user->slack_notifications_enabled ?? false))
                        >
                    </div>
                </div>
                <x-input-error class="mt-1" :messages="$errors->get('slack_notifications_enabled')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Changes') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
