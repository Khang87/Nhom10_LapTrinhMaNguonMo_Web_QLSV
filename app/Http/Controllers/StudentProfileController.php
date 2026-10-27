<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    /**
     * Display student profile in 2 columns (Academic & Personal).
     */
    public function show(): View
    {
        $user = Auth::user();
        $student = $user->student;

        return view('student.profile', compact('user', 'student'));
    }

    /**
     * Update contact info (Phone, Email, Contact Address).
     */
    public function updateContact(Request $request): RedirectResponse
    {
        $student = Auth::user()->student;
        if (!$student) {
            return back()->with('error', 'Không tìm thấy hồ sơ sinh viên.');
        }

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:15'],
            'email' => ['required', 'email', 'max:100'],
            'contact_address' => ['required', 'string', 'max:255'],
        ]);

        $student->update($validated);
        Auth::user()->update(['email' => $validated['email']]);

        return back()->with('success', 'Cập nhật thông tin liên hệ thành công!');
    }
}
