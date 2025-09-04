<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = Supplier::all();
        
        if ($suppliers->isEmpty()) {
            $this->command->warn('No suppliers found. Please run SupplierSeeder first.');
            return;
        }

        $invoices = [
            [
                'supplier_id' => $suppliers->where('name', 'Tech Solutions Inc.')->first()->id,
                'invoice_number' => 'INV-2024-001',
                'invoice_date' => '2024-01-15',
                'due_date' => '2024-02-15',
                'subtotal' => 5000.00,
                'tax_amount' => 500.00,
                'total_amount' => 5500.00,
                'status' => 'paid',
                'notes' => 'Software licensing and support services',
            ],
            [
                'supplier_id' => $suppliers->where('name', 'Office Supplies Co.')->first()->id,
                'invoice_number' => 'INV-2024-002',
                'invoice_date' => '2024-01-20',
                'due_date' => '2024-02-20',
                'subtotal' => 1200.00,
                'tax_amount' => 120.00,
                'total_amount' => 1320.00,
                'status' => 'sent',
                'notes' => 'Office equipment and supplies',
            ],
            [
                'supplier_id' => $suppliers->where('name', 'Marketing Partners LLC')->first()->id,
                'invoice_number' => 'INV-2024-003',
                'invoice_date' => '2024-02-01',
                'due_date' => '2024-03-01',
                'subtotal' => 8000.00,
                'tax_amount' => 800.00,
                'total_amount' => 8800.00,
                'status' => 'draft',
                'notes' => 'Digital marketing campaign services',
            ],
            [
                'supplier_id' => $suppliers->where('name', 'Consulting Services Ltd.')->first()->id,
                'invoice_number' => 'INV-2024-004',
                'invoice_date' => '2024-02-10',
                'due_date' => '2024-03-10',
                'subtotal' => 3500.00,
                'tax_amount' => 350.00,
                'total_amount' => 3850.00,
                'status' => 'overdue',
                'notes' => 'Business process consulting',
            ],
            [
                'supplier_id' => $suppliers->where('name', 'Tech Solutions Inc.')->first()->id,
                'invoice_number' => 'INV-2024-005',
                'invoice_date' => '2024-02-15',
                'due_date' => '2024-03-15',
                'subtotal' => 2500.00,
                'tax_amount' => 250.00,
                'total_amount' => 2750.00,
                'status' => 'sent',
                'notes' => 'Hardware maintenance contract',
            ],
            [
                'supplier_id' => $suppliers->where('name', 'Office Supplies Co.')->first()->id,
                'invoice_number' => 'INV-2024-006',
                'invoice_date' => '2024-02-20',
                'due_date' => '2024-03-20',
                'subtotal' => 800.00,
                'tax_amount' => 80.00,
                'total_amount' => 880.00,
                'status' => 'paid',
                'notes' => 'Monthly office supplies delivery',
            ],
        ];

        foreach ($invoices as $invoice) {
            Invoice::create($invoice);
        }
    }
}
