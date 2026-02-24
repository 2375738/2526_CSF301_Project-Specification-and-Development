<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $jobHistory = [
            [
                'role' => 'FC Associate I, L1',
                'department' => 'Fulfillment Center - VAR',
                'manager' => 'Tim Houlston Clark',
                'manager_email' => 'timthoul@example.com',
                'start_date' => '11 Oct 2023',
                'duration' => '1 month and 14 days',
                'status' => 'current',
            ],
            [
                'role' => 'FC Associate I, L1',
                'department' => 'Fulfillment Center - VAR',
                'manager' => 'Marian Luca',
                'manager_email' => 'lucamari@example.com',
                'start_date' => '26 May 2025',
                'duration' => '4 months and 15 days',
                'status' => 'past',
            ],
            [
                'role' => 'FC Associate I, L1',
                'department' => 'Fulfillment Center - VAR',
                'manager' => 'Jonathan Davies',
                'manager_email' => 'jonathad@example.com',
                'start_date' => '28 Apr 2025',
                'duration' => '28 days',
                'status' => 'past',
            ],
        ];

        // In ManagerRelationship: manager_id is the SUBORDINATE, reports_to_id is the BOSS.
        // So we want the relationship where 'manager_id' is the current user.
        $manager = $request->user()->managerRelationships()->with('reportsTo')->first()?->reportsTo;

        return view('profile.edit', [
            'user' => $request->user(),
            'manager' => $manager,
            'jobHistory' => $jobHistory,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
