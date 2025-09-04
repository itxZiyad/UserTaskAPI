<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices with supplier details.
     */
    public function index()
    {
        $user = Auth::user();

        // Check if stored procedures should be used
        $shouldUseStoredProcedure = (bool) env('USE_SP_INVOICES', false);
        $defaultConnection = DB::getDefaultConnection();
        $driver = config("database.connections.$defaultConnection.driver");

        if ($shouldUseStoredProcedure && $driver !== 'sqlite') {
            try {
                if (in_array($driver, ['mysql', 'mariadb'])) {
                    $rows = DB::select('CALL sp_list_invoices_with_suppliers()');
                } elseif ($driver === 'sqlsrv') {
                    $rows = DB::select('EXEC dbo.sp_list_invoices_with_suppliers');
                } elseif ($driver === 'pgsql') {
                    $rows = DB::select('SELECT * FROM sp_list_invoices_with_suppliers()');
                } else {
                    $rows = [];
                }

                return response()->json($rows);
            } catch (\Throwable $e) {
                // Fallback to Eloquent-based listing if the stored procedure is unavailable
                \Log::warning('Stored procedure failed, falling back to Eloquent: ' . $e->getMessage());
            }
        }

        // Fallback: Use Eloquent with optimized query
        $invoices = Invoice::with('supplier')
            ->select([
                'invoices.id',
                'invoices.invoice_number',
                'invoices.invoice_date',
                'invoices.due_date',
                'invoices.subtotal',
                'invoices.tax_amount',
                'invoices.total_amount',
                'invoices.status',
                'invoices.notes',
                'invoices.created_at',
                'invoices.updated_at',
                'invoices.supplier_id'
            ])
            ->join('suppliers', 'invoices.supplier_id', '=', 'suppliers.id')
            ->selectRaw('
                suppliers.id as supplier_id,
                suppliers.name as supplier_name,
                suppliers.email as supplier_email,
                suppliers.phone as supplier_phone,
                suppliers.address as supplier_address,
                suppliers.contact_person as supplier_contact_person,
                suppliers.tax_id as supplier_tax_id,
                suppliers.is_active as supplier_is_active
            ')
            ->orderBy('invoices.invoice_date', 'desc')
            ->orderBy('invoices.created_at', 'desc')
            ->get();

        return response()->json($invoices);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|unique:invoices',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'nullable|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::create($request->all());

        return response()->json($invoice->load('supplier'), 201);
    }

    /**
     * Display the specified invoice with supplier details.
     */
    public function show(Invoice $invoice)
    {
        return response()->json($invoice->load('supplier'));
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $request->validate([
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'invoice_number' => 'sometimes|string|unique:invoices,invoice_number,' . $invoice->id,
            'invoice_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after_or_equal:invoice_date',
            'subtotal' => 'sometimes|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
        ]);

        $invoice->update($request->all());

        return response()->json($invoice->load('supplier'));
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully']);
    }
}
