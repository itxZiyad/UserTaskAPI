<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Tech Solutions Inc.',
                'email' => 'contact@techsolutions.com',
                'phone' => '+1-555-0123',
                'address' => '123 Technology Drive, Silicon Valley, CA 94000',
                'contact_person' => 'John Smith',
                'tax_id' => 'TS-123456789',
                'is_active' => true,
            ],
            [
                'name' => 'Office Supplies Co.',
                'email' => 'orders@officesupplies.com',
                'phone' => '+1-555-0456',
                'address' => '456 Business Ave, New York, NY 10001',
                'contact_person' => 'Sarah Johnson',
                'tax_id' => 'OS-987654321',
                'is_active' => true,
            ],
            [
                'name' => 'Marketing Partners LLC',
                'email' => 'info@marketingpartners.com',
                'phone' => '+1-555-0789',
                'address' => '789 Creative Blvd, Los Angeles, CA 90210',
                'contact_person' => 'Mike Davis',
                'tax_id' => 'MP-456789123',
                'is_active' => true,
            ],
            [
                'name' => 'Consulting Services Ltd.',
                'email' => 'admin@consultingservices.com',
                'phone' => '+1-555-0321',
                'address' => '321 Professional Plaza, Chicago, IL 60601',
                'contact_person' => 'Emily Brown',
                'tax_id' => 'CS-789123456',
                'is_active' => true,
            ],
            [
                'name' => 'Inactive Supplier Corp.',
                'email' => 'old@inactivesupplier.com',
                'phone' => '+1-555-0999',
                'address' => '999 Old Street, Detroit, MI 48201',
                'contact_person' => 'Robert Wilson',
                'tax_id' => 'IS-111222333',
                'is_active' => false,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
