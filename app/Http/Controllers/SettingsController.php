<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $gatheringTypes = Setting::get('gathering_types', []);
        $membershipGroups = Setting::get('membership_groups', []);

        return view('settings', compact('gatheringTypes', 'membershipGroups'));
    }

    public function updateGatheringTypes(Request $request)
    {
        $validated = $request->validate([
            'types' => 'required|array',
            'types.*' => 'required|string|max:100',
        ]);

        Setting::set('gathering_types', $validated['types'], 'array');

        return response()->json([
            'status' => 'success',
            'message' => 'Gathering types updated successfully',
            'types' => $validated['types']
        ]);
    }

    public function updateMembershipGroups(Request $request)
    {
        $validated = $request->validate([
            'groups' => 'required|array',
            'groups.*' => 'required|string|max:100',
        ]);

        Setting::set('membership_groups', $validated['groups'], 'array');

        return response()->json([
            'status' => 'success',
            'message' => 'Membership groups updated successfully',
            'groups' => $validated['groups']
        ]);
    }
}
