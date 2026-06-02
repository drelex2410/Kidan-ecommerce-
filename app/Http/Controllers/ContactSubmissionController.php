<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactSubmissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_contact_submissions'])->only(['index', 'show']);
        $this->middleware(['permission:show_contact_submissions'])->only(['update', 'destroy']);
    }

    public function index(Request $request)
    {
        $status = $request->get('status');
        $search = trim((string) $request->get('search'));

        $submissions = ContactSubmission::query()
            ->when($status && array_key_exists($status, ContactSubmission::STATUSES), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('inquiry_type', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20);

        return view('backend.marketing.contact_submissions.index', compact('submissions', 'status', 'search'));
    }

    public function show(ContactSubmission $contactSubmission)
    {
        if ($contactSubmission->status === ContactSubmission::STATUS_NEW) {
            $contactSubmission->update(['status' => ContactSubmission::STATUS_READ]);
        }

        return view('backend.marketing.contact_submissions.show', [
            'submission' => $contactSubmission,
        ]);
    }

    public function update(Request $request, ContactSubmission $contactSubmission)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ContactSubmission::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $contactSubmission->update($validated);

        flash(translate('Contact submission updated successfully'))->success();

        return back();
    }

    public function destroy(ContactSubmission $contactSubmission)
    {
        $contactSubmission->delete();

        flash(translate('Contact submission deleted successfully'))->success();

        return redirect()->route('contact-submissions.index');
    }
}
