<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Disable transactional migration to allow routine creation on MySQL
    public $withinTransaction = false;
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultConnection = DB::getDefaultConnection();
        $driver = config("database.connections.$defaultConnection.driver");

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::unprepared('DROP PROCEDURE IF EXISTS sp_list_invoices_with_suppliers');
            DB::unprepared('CREATE PROCEDURE sp_list_invoices_with_suppliers() SELECT 
                        i.id,
                        i.invoice_number,
                        i.invoice_date,
                        i.due_date,
                        i.subtotal,
                        i.tax_amount,
                        i.total_amount,
                        i.status,
                        i.notes,
                        i.created_at,
                        i.updated_at,
                        s.id as supplier_id,
                        s.name as supplier_name,
                        s.email as supplier_email,
                        s.phone as supplier_phone,
                        s.address as supplier_address,
                        s.contact_person as supplier_contact_person,
                        s.tax_id as supplier_tax_id,
                        s.is_active as supplier_is_active
                    FROM invoices i
                    INNER JOIN suppliers s ON i.supplier_id = s.id
                    ORDER BY i.invoice_date DESC, i.created_at DESC');
        } elseif ($driver === 'pgsql') {
            DB::unprepared('
                CREATE OR REPLACE FUNCTION sp_list_invoices_with_suppliers()
                RETURNS TABLE (
                    id bigint,
                    invoice_number varchar,
                    invoice_date date,
                    due_date date,
                    subtotal decimal,
                    tax_amount decimal,
                    total_amount decimal,
                    status varchar,
                    notes text,
                    created_at timestamp,
                    updated_at timestamp,
                    supplier_id bigint,
                    supplier_name varchar,
                    supplier_email varchar,
                    supplier_phone varchar,
                    supplier_address text,
                    supplier_contact_person varchar,
                    supplier_tax_id varchar,
                    supplier_is_active boolean
                ) AS $$
                BEGIN
                    RETURN QUERY
                    SELECT 
                        i.id,
                        i.invoice_number,
                        i.invoice_date,
                        i.due_date,
                        i.subtotal,
                        i.tax_amount,
                        i.total_amount,
                        i.status,
                        i.notes,
                        i.created_at,
                        i.updated_at,
                        s.id as supplier_id,
                        s.name as supplier_name,
                        s.email as supplier_email,
                        s.phone as supplier_phone,
                        s.address as supplier_address,
                        s.contact_person as supplier_contact_person,
                        s.tax_id as supplier_tax_id,
                        s.is_active as supplier_is_active
                    FROM invoices i
                    INNER JOIN suppliers s ON i.supplier_id = s.id
                    ORDER BY i.invoice_date DESC, i.created_at DESC;
                END;
                $$ LANGUAGE plpgsql;
            ');
        } elseif ($driver === 'sqlsrv') {
            DB::unprepared('IF OBJECT_ID(N"sp_list_invoices_with_suppliers", N"P") IS NOT NULL DROP PROCEDURE sp_list_invoices_with_suppliers');
            DB::unprepared('CREATE PROCEDURE sp_list_invoices_with_suppliers AS SELECT 
                        i.id,
                        i.invoice_number,
                        i.invoice_date,
                        i.due_date,
                        i.subtotal,
                        i.tax_amount,
                        i.total_amount,
                        i.status,
                        i.notes,
                        i.created_at,
                        i.updated_at,
                        s.id as supplier_id,
                        s.name as supplier_name,
                        s.email as supplier_email,
                        s.phone as supplier_phone,
                        s.address as supplier_address,
                        s.contact_person as supplier_contact_person,
                        s.tax_id as supplier_tax_id,
                        s.is_active as supplier_is_active
                    FROM invoices i
                    INNER JOIN suppliers s ON i.supplier_id = s.id
                    ORDER BY i.invoice_date DESC, i.created_at DESC');
        }
        // SQLite doesn't support stored procedures, so we'll use Eloquent queries as fallback
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $defaultConnection = DB::getDefaultConnection();
        $driver = config("database.connections.$defaultConnection.driver");

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::unprepared('DROP PROCEDURE IF EXISTS sp_list_invoices_with_suppliers');
        } elseif ($driver === 'pgsql') {
            DB::unprepared('DROP FUNCTION IF EXISTS sp_list_invoices_with_suppliers()');
        } elseif ($driver === 'sqlsrv') {
            DB::unprepared('DROP PROCEDURE IF EXISTS sp_list_invoices_with_suppliers');
        }
    }
};
