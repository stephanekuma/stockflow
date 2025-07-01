<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceItem;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Store;
use App\Models\Product;
use App\Models\Unit;
use App\Models\ProductUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProformaInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_proforma_invoice()
    {
        $store = Store::factory()->create();
        $customer = Customer::factory()->create(['store_id' => $store->id]);
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();
        $productUnit = ProductUnit::factory()->create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
        ]);

        $proforma = ProformaInvoice::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'PRO-000001',
            'issued_at' => now(),
            'valid_until' => now()->addDays(30),
            'status' => ProformaInvoice::STATUS_DRAFT,
        ]);

        $item = ProformaInvoiceItem::create([
            'proforma_invoice_id' => $proforma->id,
            'product_unit_id' => $productUnit->id,
            'quantity' => 5,
            'price' => 100,
            'discount' => 10,
            'total' => 490,
        ]);

        $this->assertDatabaseHas('proforma_invoices', [
            'id' => $proforma->id,
            'invoice_number' => 'PRO-000001',
            'status' => ProformaInvoice::STATUS_DRAFT,
        ]);

        $this->assertDatabaseHas('proforma_invoice_items', [
            'proforma_invoice_id' => $proforma->id,
            'product_unit_id' => $productUnit->id,
            'quantity' => 5,
            'price' => 100,
        ]);
    }

    public function test_can_convert_proforma_to_sale()
    {
        $store = Store::factory()->create();
        $customer = Customer::factory()->create(['store_id' => $store->id]);
        $product = Product::factory()->create();
        $unit = Unit::factory()->create();
        $productUnit = ProductUnit::factory()->create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'unit_id' => $unit->id,
        ]);

        $proforma = ProformaInvoice::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'PRO-000001',
            'issued_at' => now(),
            'valid_until' => now()->addDays(30),
            'status' => ProformaInvoice::STATUS_ACCEPTED,
        ]);

        ProformaInvoiceItem::create([
            'proforma_invoice_id' => $proforma->id,
            'product_unit_id' => $productUnit->id,
            'quantity' => 5,
            'price' => 100,
            'discount' => 10,
            'total' => 490,
        ]);

        $sale = $proforma->convertToSale();

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertEquals($store->id, $sale->store_id);
        $this->assertEquals($customer->id, $sale->customer_id);
        $this->assertEquals(ProformaInvoice::STATUS_CONVERTED, $proforma->fresh()->status);
        $this->assertEquals($sale->id, $proforma->fresh()->converted_to_sale_id);

        // Vérifier que les produits ont été copiés
        $this->assertEquals(1, $sale->soldProducts->count());
        $soldProduct = $sale->soldProducts->first();
        $this->assertEquals($productUnit->id, $soldProduct->product_unit_id);
        $this->assertEquals(5, $soldProduct->quantity);
        $this->assertEquals(100, $soldProduct->price);
    }

    public function test_proforma_invoice_number_generation()
    {
        $store = Store::factory()->create();
        $customer = Customer::factory()->create(['store_id' => $store->id]);

        $proforma1 = ProformaInvoice::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'invoice_number' => ProformaInvoice::generateInvoiceNumber(),
            'issued_at' => now(),
            'status' => ProformaInvoice::STATUS_DRAFT,
        ]);

        $proforma2 = ProformaInvoice::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'invoice_number' => ProformaInvoice::generateInvoiceNumber(),
            'issued_at' => now(),
            'status' => ProformaInvoice::STATUS_DRAFT,
        ]);

        $this->assertNotEquals($proforma1->invoice_number, $proforma2->invoice_number);
        $this->assertStringStartsWith('PRO-', $proforma1->invoice_number);
        $this->assertStringStartsWith('PRO-', $proforma2->invoice_number);
    }

    public function test_can_be_converted_validation()
    {
        $store = Store::factory()->create();
        $customer = Customer::factory()->create(['store_id' => $store->id]);

        // Proforma acceptée et non expirée
        $proforma1 = ProformaInvoice::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'PRO-000001',
            'issued_at' => now(),
            'valid_until' => now()->addDays(30),
            'status' => ProformaInvoice::STATUS_ACCEPTED,
        ]);

        // Proforma expirée
        $proforma2 = ProformaInvoice::create([
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'invoice_number' => 'PRO-000002',
            'issued_at' => now()->subDays(60),
            'valid_until' => now()->subDays(30),
            'status' => ProformaInvoice::STATUS_ACCEPTED,
        ]);

        $this->assertTrue($proforma1->canBeConverted());
        $this->assertFalse($proforma2->canBeConverted());
        $this->assertTrue($proforma2->isExpired());
    }
}
