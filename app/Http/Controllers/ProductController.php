<?php

namespace App\Http\Controllers;

use App\Enums\RoleStatus;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\Product\ProductServiceInterface;
use App\Services\ActivityLog\ActivityLoggerInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function __construct(private readonly ProductServiceInterface $service, private readonly ActivityLoggerInterface $logger)
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || Auth::user()->role !== RoleStatus::ADMIN->value) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
            return $next($request);
        });
    }

    public function index(): View
    {
        return view('products.index');
    }

    public function data()
    {
        $query = Product::query()->with('category')->select(['id', 'category_id', 'name', 'sku', 'price', 'stock', 'created_at']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('category', fn(Product $p) => $p->category?->name)
            ->editColumn('price', fn(Product $p) => number_format((float) $p->price, 2, ',', '.'))
            ->addColumn('action', function (Product $p) {
                $editUrl = route('produk.edit', $p);
                $deleteUrl = route('produk.destroy', $p);
                $csrf = csrf_token();
                return <<<HTML
                    <div class="d-flex justify-content-end gap-1">
                        <a href="{$editUrl}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                        <form action="{$deleteUrl}" method="POST" class="d-inline" onsubmit="return confirm('Hapus produk ini? Tindakan tidak dapat dibatalkan.');">
                            <input type="hidden" name="_token" value="{$csrf}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </form>
                    </div>
                HTML;
            })
            ->rawColumns(['action'])
            ->toJson();
    }

    public function create(): View
    {
        $categories = Category::query()->orderBy('name')->pluck('name', 'id');
        return view('products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $p = $this->service->create($request->validated());
        $this->logger->log('Tambah Produk', "Menambahkan produk '{$p->name}'", ['product_id' => $p->id, 'sku' => $p->sku]);
        return redirect()->route('produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::query()->orderBy('name')->pluck('name', 'id');
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $before = $product->only(['name', 'sku', 'price', 'stock', 'category_id']);
        $this->service->update($product, $request->validated());
        $after = $product->only(['name', 'sku', 'price', 'stock', 'category_id']);
        $this->logger->log('Ubah Produk', "Mengubah produk '{$before['name']}'", ['before' => $before, 'after' => $after, 'product_id' => $product->id]);
        return redirect()->route('produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $id = $product->id;
        $sku = $product->sku;
        $this->service->delete($product);
        $this->logger->log('Hapus Produk', "Menghapus produk '{$name}'", ['product_id' => $id, 'sku' => $sku]);
        return redirect()->route('produk.index')->with('success', 'Produk berhasil dihapus.');
    }
}
