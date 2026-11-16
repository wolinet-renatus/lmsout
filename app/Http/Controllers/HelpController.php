<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Role-aware Online Help & Security Policy Guide (SRS Section 5.2.2, adapted from SCMS HelpController).
 * @author Renatus Bernardo
 */
class HelpController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index(): View
    {
        $user = Auth::user();
        $roleName = $user?->primaryRoleLabel() ?? 'User';

        $layout = match (true) {
            $user?->isAdmin() => 'layouts.admin',
            $user?->isInstructor() => 'layouts.instructor',
            default => 'layouts.student',
        };

        return view('help.index', compact('user', 'roleName', 'layout'));
    }
}
