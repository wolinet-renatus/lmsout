<?php

namespace App\Http\Controllers\Admin\Campus;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

/**
 * Notification template controller — manages campus & short-course operations within the admin portal.
 *
 * @author Renatus Bernardo
 */
class NotificationTemplateController extends Controller
{
    /**
     * Display a listing of the resource records.
     */
    public function index()
    {
        $templates = NotificationTemplate::orderBy('name')->get();

        return view('admin.campus.templates.index', compact('templates'));
    }

    /**
     * Validate and update the specified resource record.
     */
    public function update(Request $request, NotificationTemplate $template)
    {
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $template->update($data);

        ToastMagic::success('Template "' . $template->name . '" saved.');

        return back();
    }
}
