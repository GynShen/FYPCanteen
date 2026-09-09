<?php

use App\Models\CartItem;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT19 adds updates and removes an eligible meal cart selection', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 09:00:00', 'Asia/Kuala_Lumpur'));
    $customer = F::user('customer');
    $school = F::school(['ordering_deadline_day' => 'saturday']);
    $student = F::student($school);
    F::child($customer, $student);
    $menu = F::menu($school, [], ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);

    foreach (range(24, 28) as $day) {
        $this->actingAs($customer)->post(route('customer.cart.store'), [
            'student_id' => $student->id,
            'menu_item_id' => $menu->id,
            'collection_date' => "2026-08-{$day}",
            'quantity' => 2,
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    $this->actingAs($customer)->get(route('customer.menu'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('cartItems', 5)
            ->where('cartItems.0.collection_date', '2026-08-24')
            ->where('cartItems.1.collection_date', '2026-08-25')
            ->where('cartItems.2.collection_date', '2026-08-26')
            ->where('cartItems.3.collection_date', '2026-08-27')
            ->where('cartItems.4.collection_date', '2026-08-28'));

    $cartItem = CartItem::orderBy('scheduled_time')->firstOrFail();
    $this->actingAs($customer)->patch(route('customer.cart.update', $cartItem), ['quantity' => 3])
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 3]);

    $this->actingAs($customer)->delete(route('customer.cart.destroy', $cartItem))->assertRedirect();
    $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
});
