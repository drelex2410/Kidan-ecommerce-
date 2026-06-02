<?php

namespace App\Http\Controllers\Api\V1\Content;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactSubmissionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'inquiry_type' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
            'source_page' => ['nullable', Rule::in(['contact-us'])],
        ]);

        ContactSubmission::create([
            ...$validated,
            'source_page' => $validated['source_page'] ?? 'contact-us',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'status' => ContactSubmission::STATUS_NEW,
        ]);

        return response()->json([
            'success' => true,
            'message' => translate('Your message has been received. Our team will get back to you soon.'),
        ]);
    }
}
