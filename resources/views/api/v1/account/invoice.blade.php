<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ optional($combinedOrder)->code ?? $order->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #222; margin: 32px; }
        h1, h2, h3, p { margin: 0 0 12px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f6f6f6; }
        .muted { color: #666; }
        .totals { margin-top: 16px; text-align: right; }
    </style>
</head>
<body>
    <h1>Invoice {{ optional($combinedOrder)->code ?? $order->id }}</h1>
    <p class="muted">Generated on {{ now()->format('Y-m-d H:i') }}</p>

    <h3>Order Summary</h3>
    <p>Order ID: {{ $order->id }}</p>
    <p>Payment status: {{ $order->payment_status }}</p>
    <p>Delivery status: {{ $order->delivery_status }}</p>

    <table>
        <thead>
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Tax</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order->orderDetails as $detail)
            <tr>
                <td>{{ optional($detail->product)->name ?? 'Product removed' }}</td>
                <td>{{ $detail->quantity }}</td>
                <td>{{ number_format((float) $detail->price, 2) }}</td>
                <td>{{ number_format((float) $detail->tax, 2) }}</td>
                <td>{{ number_format((float) $detail->total, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="totals">
        <p>Shipping: {{ number_format((float) $order->shipping_cost, 2) }}</p>
        <p>Coupon discount: {{ number_format((float) $order->coupon_discount, 2) }}</p>
        <p><strong>Grand total: {{ number_format((float) $order->grand_total, 2) }}</strong></p>
    </div>
</body>
</html>
