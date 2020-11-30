<?php

require_once('api/Simpla.php');

class Unitpay extends Simpla
{	
	public function checkout_form($order_id, $button_text = null)
	{
		if(empty($button_text))
			$button_text = 'Перейти к оплате';

		$order = $this->orders->get_order((int)$order_id);

		$products = $this->orders->get_purchases(array("order_id" => $order->id));
		
		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		$payment_settings = $this->payment->get_payment_settings($payment_method->id);
		$amount = $this->money->convert($order->total_price, $payment_method->currency_id, false);
		
		$payment_currency = $this->money->get_currency(intval($payment_method->currency_id));
		$currency = $payment_currency->code;
		$cents = $payment_currency->cents;
		
		$amount = number_format($amount, $cents, '.', '');
		
		// номер заказа
		$order_id = $order->id;

		$domain = $payment_settings['domain'];
		$public_key = $payment_settings['public_key'];
		$secret_key = $payment_settings['secret_key'];
		$account = $order_id;
		$sum = $amount;
		$desc = 'Оплата по заказу №' . $order_id;
		
		
		$signature = hash('sha256', join('{up}', array(
			$order_id,
			$currency,
			$desc,
			$sum ,
			$secret_key
		)));

		$cashItems = $this->cashItems($order, $products, $currency, $cents);
		
		$button =	'<form name="unitpay" action="https://' . $domain . '/pay/' . $public_key . '" method="get">
            <input type="hidden" name="sum" value="' . $sum . '">
            <input type="hidden" name="account" value="' . $account . '">
			<input type="hidden" name="currency" value="' . $currency . '">
            <input type="hidden" name="desc" value="' . $desc . '">
			<input type="hidden" name="signature" value="' . $signature . '">
			<input type="hidden" name="customerEmail" value="' . $order->email . '">
			<input type="hidden" name="customerPhone" value="' . preg_replace('/\D/', '', $order->phone)  . '">
			<input type="hidden" name="cashItems" value="' . $cashItems . '">
            <input type=submit class=payment_button value="' . $button_text . '">
        </form>';

		return $button;
	}
	
	private function cashItems($order, $products, $currency, $cents) {
		$items = array_map(function ($item) use($currency, $cents) {
			return array(
				'name' => $item->product_name,
				'count' => $item->amount,
				'price' => number_format($item->price, $cents, '.', ''),
				'currency' => $currency,
				'type' => 'commodity',
			);
		}, $products);
		
		if(!$order->separate_delivery && $order->delivery_price > 0) {
			$items[] = array(
				'name' => "Услуги доставки",
				'count' => 1,
				'price' => number_format($order->delivery_price, $cents, '.', ''),
				'currency' => $currency,
				'type' => 'service',
			);
		}
	
		
        return base64_encode(json_encode($items));
	}

}