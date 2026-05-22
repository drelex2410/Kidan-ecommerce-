<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;

class CurrencyController extends Controller
{

    public function __construct()
    {
        $this->middleware(['permission:show_currencies'])->only('index');
        $this->middleware(['permission:add_currencies'])->only('create');
        $this->middleware(['permission:edit_currencies'])->only('edit');
    }

    public function changeCurrency(Request $request)
    {
        $validated = $request->validate([
            'currency_code' => [
                'required',
                'string',
                Rule::exists('currencies', 'code')->where(fn ($query) => $query->where('status', 1)),
            ],
        ]);

        $currency = Currency::where('code', $validated['currency_code'])->firstOrFail();
        $request->session()->put('currency_code', $validated['currency_code']);
        $request->session()->put('currency_exchange_rate', $currency->exchange_rate);

        $message = translate('Currency changed to ') . $currency->name;

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'code' => $currency->code,
                    'symbol' => $currency->symbol,
                    'exchange_rate' => (float) $currency->exchange_rate,
                ],
            ]);
        }

        flash($message)->success();
        return back();
    }

    public function index(Request $request)
    {
        $sort_search = null;
        $currencies = Currency::orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $sort_search = $request->search;
            $currencies = $currencies->where('name', 'like', '%' . $sort_search . '%');
        }
        $currencies = $currencies->paginate(10);

        $active_currencies = Currency::all();
        return view('backend.settings.currencies.index', compact('currencies', 'active_currencies', 'sort_search'));
    }

    public function updateYourCurrency(Request $request)
    {
        $currency = Currency::findOrFail($request->id);
        $originalCode = $currency->code;
        $currency->name = $request->name;
        $currency->symbol = $request->symbol;
        $currency->code = $request->code;
        $currency->exchange_rate = $request->exchange_rate;
        $currency->status = $currency->status;
        if ($currency->save()) {
            $this->clearCurrencyCaches($originalCode, $currency->code);
            flash(translate('Currency updated successfully'))->success();
            return redirect()->route('currency.index');
        } else {
            flash(translate('Something went wrong'))->error();
            return redirect()->route('currency.index');
        }
    }

    public function create()
    {
        return view('backend.settings.currencies.create');
    }

    public function edit(Request $request)
    {
        $currency = Currency::findOrFail($request->id);
        return view('backend.settings.currencies.edit', compact('currency'));
    }

    public function store(Request $request)
    {
        $currency = new Currency;
        $currency->name = $request->name;
        $currency->symbol = $request->symbol;
        $currency->code = $request->code;
        $currency->exchange_rate = $request->exchange_rate;
        $currency->status = '0';
        if ($currency->save()) {
            $this->clearCurrencyCaches(null, $currency->code);
            flash(translate('Currency updated successfully'))->success();
            return redirect()->route('currency.index');
        } else {
            flash(translate('Something went wrong'))->error();
            return redirect()->route('currency.index');
        }
    }

    public function update_status(Request $request)
    {
        $currency = Currency::findOrFail($request->id);
        $currency->status = $request->status;
        if ($currency->save()) {
            $this->clearCurrencyCaches($currency->code, $currency->code);
            return 1;
        }
        return 0;
    }

    private function clearCurrencyCaches(?string $originalCode = null, ?string $newCode = null): void
    {
        Cache::forget('bootstrap.currencies');

        foreach (array_filter([$originalCode, $newCode]) as $currencyCode) {
            Cache::forget("selected_currency_rate_{$currencyCode}");
            Cache::forget("selected_currency_symbol_{$currencyCode}");
        }
    }
}
