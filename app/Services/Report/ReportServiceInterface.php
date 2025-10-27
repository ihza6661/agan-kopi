<?php

namespace App\Services\Report;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

interface ReportServiceInterface
{
    public function summary(array $filters): array;

    public function dailySalesQuery(array $filters): Builder;

    public function topProducts(array $filters, int $limit = 5): Collection;

    public function monthlySalesQuery(array $filters): Builder;

    public function slowProducts(array $filters, int $limit = 5): Collection;

    public function productSales(array $filters): Collection;
}
