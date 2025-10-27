<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\TransactionStatus;
use App\Models\Transaction;
use App\Services\Settings\SettingsServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class TransactionController extends Controller
{
    public function __construct(private readonly SettingsServiceInterface $settings) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $method = $request->query('method');
        $cashier = $request->query('cashier');
        $from = $request->query('from');
        $to = $request->query('to');

        $statuses = array_values(array_filter(TransactionStatus::cases(), fn($s) => $s->value !== 'suspended'));

        return view('transactions.index', [
            'q' => $q,
            'status' => $status,
            'method' => $method,
            'cashier' => $cashier,
            'from' => $from,
            'to' => $to,
            'currency' => $this->settings->currency(),
            'statuses' => $statuses,
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function data(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $status = $request->input('status');
        $method = $request->input('method');
        $cashier = $request->input('cashier');
        $from = $request->input('from');
        $to = $request->input('to');

        $query = Transaction::query()
            ->with(['user'])
            ->where('status', '!=', TransactionStatus::SUSPENDED->value)
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($qq) use ($q) {
                    $qq->where('invoice_number', 'like', "%{$q}%")
                        ->orWhere('note', 'like', "%{$q}%");
                });
            })
            ->when($status && in_array($status, array_column(TransactionStatus::cases(), 'value'), true), function ($w) use ($status) {
                $w->where('status', $status);
            })
            ->when($method && in_array($method, array_column(PaymentMethod::cases(), 'value'), true), function ($w) use ($method) {
                $w->where('payment_method', $method);
            })
            ->when($cashier && ctype_digit((string) $cashier), function ($w) use ($cashier) {
                $w->where('user_id', (int) $cashier);
            })
            ->when($from, function ($w) use ($from) {
                $w->whereDate('created_at', '>=', $from);
            })
            ->when($to, function ($w) use ($to) {
                $w->whereDate('created_at', '<=', $to);
            })
            ->orderByDesc('created_at')
            ->select(['id', 'user_id', 'invoice_number', 'payment_method', 'status', 'total', 'created_at']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('invoice', function (Transaction $t) {
                $url = route('transaksi.show', $t);
                return '<a href="' . e($url) . '">' . e($t->invoice_number) . '</a>';
            })
            ->addColumn('date', function (Transaction $t) {
                return $t->created_at?->format('d/m/Y H:i');
            })
            ->addColumn('cashier', function (Transaction $t) {
                return e($t->user->name ?? '-');
            })
            ->addColumn('method', function (Transaction $t) {
                $m = is_string($t->payment_method) ? $t->payment_method : ($t->payment_method?->value ?? '');
                return strtoupper($m);
            })
            ->addColumn('status_badge', function (Transaction $t) {
                $s = is_string($t->status) ? $t->status : ($t->status?->value ?? '');
                $class = $s === 'paid' ? 'bg-success' : ($s === 'pending' ? 'bg-warning text-dark' : 'bg-secondary');
                return '<span class="badge ' . $class . '">' . strtoupper($s) . '</span>';
            })
            ->editColumn('total', function (Transaction $t) {
                return 'Rp ' . number_format((float) $t->total, 0, ',', '.');
            })
            ->addColumn('action', function (Transaction $t) {
                $showUrl = route('transaksi.show', $t);
                $receiptUrl = route('transaksi.struk', $t);
                return '<div class="d-flex justify-content-end gap-1">'
                    . '<a class="btn btn-sm btn-outline-primary" href="' . e($showUrl) . '"><i class="bi bi-eye"></i></a>'
                    . '<a class="btn btn-sm btn-outline-secondary" href="' . e($receiptUrl) . '" target="_blank" rel="noopener noreferrer"><i class="bi bi-receipt-cutoff"></i></a>'
                    . '</div>';
            })
            ->rawColumns(['invoice', 'status_badge', 'action'])
            ->toJson();
    }

    public function show(Transaction $transaction): View
    {
        $transaction->loadMissing(['details.product', 'user', 'latestPayment']);
        return view('transactions.show', [
            'trx' => $transaction,
            'currency' => $this->settings->currency(),
        ]);
    }

    public function receipt(Transaction $transaction): View
    {
        $transaction->loadMissing(['details.product', 'user']);

        return view('transactions.receipt', [
            'transaction'    => $transaction,
            'store_name'     => $this->settings->storeName(),
            'store_address'  => $this->settings->storeAddress(),
            'store_phone'    => $this->settings->storePhone(),
            'store_logo'     => $this->settings->storeLogoPath(),
            'currency'       => $this->settings->currency(),
            'discount_percent' => $this->settings->discountPercent(),
            'tax_percent'      => $this->settings->taxPercent(),
        ]);
    }
}
