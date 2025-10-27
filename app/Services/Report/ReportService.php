<?php

namespace App\Services\Report;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService implements ReportServiceInterface
{
    public function summary(array $filters): array
    {
        $trx = $this->baseTransactionQuery($filters);
        $sum = (clone $trx)->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(t.total),0) as total')->first();

        $items = DB::table('transaction_details as d')
            ->join('transactions as t', 't.id', '=', 'd.transaction_id')
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '<=', $v))
            ->when(!empty($filters['status']), fn($q) => $q->where('t.status', $filters['status']))
            ->when(!empty($filters['method']), fn($q) => $q->where('t.payment_method', $filters['method']))
            ->selectRaw('COALESCE(SUM(d.quantity),0) as items_qty')
            ->first();

        $totalSales = (float) ($sum->total ?? 0);
        $totalTrx = (int) ($sum->trx_count ?? 0);
        $avg = $totalTrx > 0 ? ($totalSales / $totalTrx) : 0.0;

        return [
            'total_sales' => $totalSales,
            'total_transactions' => $totalTrx,
            'average_order_value' => $avg,
            'total_items_sold' => (int) ($items->items_qty ?? 0),
        ];
    }

    public function dailySalesQuery(array $filters): Builder
    {
        $dateExpr = DB::raw('DATE(t.created_at)');

        $trx = $this->baseTransactionQuery($filters)
            ->selectRaw('DATE(t.created_at) as grp_date, COUNT(*) as trx_count, COALESCE(SUM(t.total),0) as total')
            ->groupBy($dateExpr);

        $items = DB::table('transaction_details as d')
            ->join('transactions as t', 't.id', '=', 'd.transaction_id')
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '<=', $v))
            ->when(!empty($filters['status']), fn($q) => $q->where('t.status', $filters['status']))
            ->when(!empty($filters['method']), fn($q) => $q->where('t.payment_method', $filters['method']))
            ->selectRaw('DATE(t.created_at) as grp_date, COALESCE(SUM(d.quantity),0) as items_qty')
            ->groupBy($dateExpr);

        return DB::query()
            ->fromSub($trx, 'x')
            ->leftJoinSub($items, 'i', 'i.grp_date', '=', 'x.grp_date')
            ->selectRaw('x.grp_date as date, x.trx_count, COALESCE(i.items_qty,0) as items_qty, x.total')
            ->orderBy('x.grp_date');
    }

    public function monthlySalesQuery(array $filters): Builder
    {
        $driver = DB::connection()->getDriverName();
        $expr = $driver === 'sqlite'
            ? "strftime('%Y-%m', t.created_at)"
            : "DATE_FORMAT(t.created_at, '%Y-%m')";

        $trx = $this->baseTransactionQuery($filters)
            ->selectRaw("$expr as grp_date, COUNT(*) as trx_count, COALESCE(SUM(t.total),0) as total")
            ->groupBy(DB::raw($expr));

        $items = DB::table('transaction_details as d')
            ->join('transactions as t', 't.id', '=', 'd.transaction_id')
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '<=', $v))
            ->when(!empty($filters['status']), fn($q) => $q->where('t.status', $filters['status']))
            ->when(!empty($filters['method']), fn($q) => $q->where('t.payment_method', $filters['method']))
            ->selectRaw("$expr as grp_date, COALESCE(SUM(d.quantity),0) as items_qty")
            ->groupBy(DB::raw($expr));

        return DB::query()
            ->fromSub($trx, 'x')
            ->leftJoinSub($items, 'i', 'i.grp_date', '=', 'x.grp_date')
            ->selectRaw('x.grp_date as date, x.trx_count, COALESCE(i.items_qty,0) as items_qty, x.total')
            ->orderBy('x.grp_date');
    }

    public function topProducts(array $filters, int $limit = 5): Collection
    {
        return DB::table('transaction_details as d')
            ->join('transactions as t', 't.id', '=', 'd.transaction_id')
            ->leftJoin('products as p', 'p.id', '=', 'd.product_id')
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '<=', $v))
            ->when(!empty($filters['status']), fn($q) => $q->where('t.status', $filters['status']))
            ->when(!empty($filters['method']), fn($q) => $q->where('t.payment_method', $filters['method']))
            ->groupBy('d.product_id', 'p.name')
            ->orderByDesc(DB::raw('SUM(d.quantity)'))
            ->limit($limit)
            ->selectRaw('d.product_id, COALESCE(p.name, CONCAT("#", d.product_id)) as name, SUM(d.quantity) as qty, SUM(d.total) as total')
            ->get();
    }

    public function slowProducts(array $filters, int $limit = 5): Collection
    {
        $sold = DB::table('transaction_details as d')
            ->join('transactions as t', 't.id', '=', 'd.transaction_id')
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '<=', $v))
            ->when(!empty($filters['status']), fn($q) => $q->where('t.status', $filters['status']))
            ->when(!empty($filters['method']), fn($q) => $q->where('t.payment_method', $filters['method']))
            ->groupBy('d.product_id')
            ->selectRaw('d.product_id, SUM(d.quantity) as qty, SUM(d.total) as total');

        return DB::table('products as p')
            ->leftJoinSub($sold, 's', 's.product_id', '=', 'p.id')
            ->orderByRaw('COALESCE(s.qty, 0) ASC, p.stock DESC, p.name ASC')
            ->limit($limit)
            ->selectRaw('p.id as product_id, p.name, COALESCE(s.qty, 0) as qty, COALESCE(s.total, 0) as total, p.stock')
            ->get();
    }

    public function productSales(array $filters): Collection
    {
        return DB::table('transaction_details as d')
            ->join('transactions as t', 't.id', '=', 'd.transaction_id')
            ->leftJoin('products as p', 'p.id', '=', 'd.product_id')
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '<=', $v))
            ->when(!empty($filters['status']), fn($q) => $q->where('t.status', $filters['status']))
            ->when(!empty($filters['method']), fn($q) => $q->where('t.payment_method', $filters['method']))
            ->groupBy('d.product_id', 'p.name', 'p.sku')
            ->orderByDesc(DB::raw('SUM(d.quantity)'))
            ->selectRaw('d.product_id, COALESCE(p.name, CONCAT("#", d.product_id)) as name, p.sku, SUM(d.quantity) as qty, SUM(d.total) as total')
            ->get();
    }

    private function baseTransactionQuery(array $filters)
    {
        return DB::table('transactions as t')
            ->when($filters['from'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn($q, $v) => $q->whereDate('t.created_at', '<=', $v))
            ->when(!empty($filters['status']), fn($q) => $q->where('t.status', $filters['status']))
            ->when(!empty($filters['method']), fn($q) => $q->where('t.payment_method', $filters['method']));
    }
}
