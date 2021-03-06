<?php

namespace App\Http\Controllers;

use App\Enum\OrderMode;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\SystemConfig;
use App\Models\Variation;
use App\Util\Cart;
use App\Util\CartItem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ulid\Ulid;

class CartController extends Controller
{
    public function cartPage(): Factory|View|Application
    {
        return view('listing.cart.index');
    }

    public function checkOutPage(): View|Factory|Application|RedirectResponse
    {
        $cart = Cart::useSession();
        $payments = PaymentMethod::all();

        if ($cart->count() == 0) return response()->redirectTo('/cart');

        return view('listing.check-out.index', compact('cart', 'payments'));
    }

    public function checkOut(): View|Factory|Application|RedirectResponse
    {
        $cart = Cart::useSession();

        if ($cart->count() == 0) return response()->redirectTo('/cart');

        $name = request('name') ?? '';
        $phone = request('phone');
        $address = request('address') ?? '';
        $payment_method = request('payment_method');
        $receipt_image = request('receipt_image');

        if ($cart->orderMode == OrderMode::$SELF_PICKUP) {
            $customer = new Customer([
                'phone' => $phone,
            ]);
        } else if ($cart->orderMode == OrderMode::$DELIVERY) {
            $customer = new Customer([
                'name' => $name,
                'phone' => $phone,
                'address' => $address,
            ]);
        } else {
            return response()->redirectTo('/cart');
        }

        $imagePath = ImageController::save($receipt_image);

        if ($cart->orderMode == OrderMode::$DELIVERY) {
            if (SystemConfig::freeShippingIsActivated()) {
                if ($cart->subtotal() >= SystemConfig::getFreeShippingThreshold()) {
                    $note = SystemConfig::getFreeShippingDesc();
                }
            }
        }

        $order = new Order([
            'mode' => $cart->orderMode,
            'shipping_fee' => $cart->shippingFee(),
            'payment_method' => $payment_method,
            'receipt_image' => $imagePath,
            'note' => $note ?? '',
        ]);

        $orderId = (string) Ulid::generate();
        $order->setAttribute('id', $orderId);

        $order->save();
        $order->customer()->save($customer);

        $cartItems = Cart::useSession()->cartItems;

        foreach ($cartItems as $cartItem) {
            $variation = $cartItem->variation;

            $orderItem = new OrderItem([
                'name' => $variation->item->name . ' ' . $variation->name,
                'name_en' => $variation->item->name_en . ' ' . $variation->name_en,
                'barcode' => $variation->barcode,
                'price' => $variation->getPrice(),
                'discount_rate' => $variation->getDiscountRate(),
                'quantity' => $cartItem->quantity,
            ]);

            $order->orderItems()->save($orderItem);

            Variation::query()
                ->find($variation->barcode)
                ->first()
                ->update(['stock' => $variation->stock - $cartItem->quantity]);
        }

        $cart->reset();
        $cart->saveSession();

        return view('listing.check-out.order-successful', compact('cartItems', 'orderId'));
    }

    public function count(): JsonResponse
    {
        $cart = Cart::useSession();
        return response()->json(['count' => $cart->count()]);
    }

    public function add(): JsonResponse
    {
        $addList = request('addList');

        $cart = Cart::useSession();
        $cartItems = [];

        foreach ($addList as $item) {
            $variation = Variation::query()
                ->find($item['barcode']);

            if ($variation == null) return response()->json(['isAdded' => false], 400);

            $cartItems[] = new CartItem($variation, $item['quantity']);
        }

        foreach ($cartItems as $cartItem) {
            $cart->add($cartItem);
            $cart->saveSession();
        }

        return response()->json([], 204);
    }

    public function remove(): JsonResponse
    {
        $barcode = request('barcode');

        $cart = Cart::useSession();
        $cart->remove($barcode);
        $cart->saveSession();

        return response()->json([], 204);
    }

    public function reset(): JsonResponse
    {
        $cart = Cart::useSession();
        $cart->reset();
        $cart->saveSession();

        return response()->json([], 204);
    }

    public function setQuantity(): JsonResponse
    {
        $barcode = request('barcode');
        $quantity = request('quantity');

        $cart = Cart::useSession();

        if ($cart->adjust($barcode, $quantity)) {
            $cart->saveSession();
            return response()->json([], 204);
        } else {
            return response()->json([], 500);
        }
    }

    public function setOrderMode(): JsonResponse
    {
        $orderMode = request('orderMode');

        $cart = Cart::useSession();

        $cart->orderMode = $orderMode;
        $cart->saveSession();

        return response()->json([], 204);
    }
}
