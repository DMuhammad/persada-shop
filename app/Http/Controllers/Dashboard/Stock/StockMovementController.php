<?php

namespace App\Http\Controllers\Dashboard\Stock;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\StockMovementRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\Stock\StockMovementRepository;
use App\Services\Stock\StockService;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly StockMovementRepository $movements,
    ) {}

    /**
     * Shows the stock movement history with product, type and date filters.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['product_id', 'type', 'start_date', 'end_date']);

        return view('pages.dashboard.stock.index', [
            'movements' => $this->movements->paginateFiltered($filters),
            'filters' => $filters,
            'products' => Product::with('variants')->orderBy('name')->get(['id', 'name']),
            'types' => StockMovementType::cases(),
            'manualTypes' => StockMovementType::manualCases(),
        ]);
    }

    /**
     * Records a stock movement entered by an admin. A correction carries the
     * counted balance, every other type carries an amount.
     */
    public function store(StockMovementRequest $request)
    {
        $variant = ProductVariant::findOrFail($request->validated('product_variant_id'));
        $type = $request->movementType();
        $quantity = (int) $request->validated('quantity');
        $note = $request->validated('note');

        try {
            $movement = $type->isAdjustment()
                ? $this->stockService->adjust($variant, $quantity, $note)
                : $this->stockService->record($variant, $type, $quantity, $note);
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($movement === null) {
            return back()->with('error', 'Hasil hitung sama dengan saldo tercatat, tidak ada yang dikoreksi.');
        }

        return back()->with('success', 'Mutasi stok berhasil dicatat.');
    }
}
