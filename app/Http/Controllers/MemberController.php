<?php

namespace App\Http\Controllers;

use App\Models\Membership;

class MemberController extends Controller
{
    public function index()
    {
        $memberships = Membership::with(['organization.plan', 'role'])
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->get();

        return view('member.index', ['memberships' => $memberships]);
    }
}
