<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\UserBannedNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_customers'])->only('index');
        $this->middleware(['permission:show_customers'])->only(['create', 'store']);
        $this->middleware(['permission:view_customers'])->only('show');
        $this->middleware(['permission:delete_customers'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_search = null;
        $customers = User::where('user_type', 'customer')->withCount('orders')->orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $sort_search = $request->search;
            $customers = $customers->where('name', 'like', '%' . $sort_search . '%')->orWhere('email', 'like', '%' . $sort_search . '%');
        }
        $customers = $customers->paginate(15);
        return view('backend.customers.index', compact('customers', 'sort_search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('backend.customers.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'required_without:email', 'unique:users,phone'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
            ],
        ], [
            'email.required_without' => translate('Email or phone is required.'),
            'phone.required_without' => translate('Phone or email is required.'),
            'password.confirmed' => translate('Password confirmation does not match.'),
            'password.min' => translate('Password must be at least 8 characters.'),
            'password.regex' => translate('Password must contain uppercase, lowercase and a number.'),
        ]);

        $phone = isset($validated['phone']) ? preg_replace('/\s+/', '', (string) $validated['phone']) : null;

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $phone ?: null,
            'password' => Hash::make($validated['password']),
            'user_type' => 'customer',
            'email_verified_at' => !empty($validated['email']) ? now() : null,
            'phone_verified_at' => !empty($phone) ? now() : null,
            'banned' => false,
        ]);

        flash(translate('Customer created successfully'))->success();

        return redirect()->route('customers.show', $user->id);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('backend.customers.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);

        $user->orders()->delete();
        $user->reviews()->delete();
        $user->carts()->delete();
        $user->wallets()->delete();
        $user->addresses()->delete();
        $user->reviews()->delete();

        // delete chats, conversation and related data 
        try {
            $user->chat_thread->chats()->delete();
            $user->chat_thread()->delete();

            $user->conversations->messages()->delete();
            $user->conversations()->delete();
        } catch (\Throwable $th) {
            //throw $th;
        }


        $user->delete();

        flash(translate('Customer deleted successfully'))->error();
        return back();
    }

    public function ban($id)
    {
        $user = User::find(decrypt($id));
        if ($user->banned == 1) {
            $user->banned = 0;
            flash(translate('Customer Unbanned Successfully'))->success();
        } else {
            $user->banned = 1;
            flash(translate('Customer Banned Successfully'))->success();
        }
        $user->save();
        try {
            $user->notify(new UserBannedNotification($user));
        } catch (\Exception $e) {
        }
        return back();
    }

    public function bulk_customer_delete(Request $request)
    {
        
        if ($request->id) {
            foreach ($request->id as $customer_id) {
                $this->destroy($customer_id);
            }
        }

        return 1;
    }
}
