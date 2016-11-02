<?php

require_once('api/Simpla.php');

class Unitpay extends Simpla
{	
	public function checkout_form($order_id, $button_text = null)
	{
		if(empty($button_text))
			$button_text = 'Перейти к оплате';

		$order = $this->orders->get_order((int)$order_id);
		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		$payment_settings = $this->payment->get_payment_settings($payment_method->id);
		$amount = $this->money->convert($order->total_price, $payment_method->currency_id, false);
		$amount = number_format($amount, 2, '.', '');
//		$payment_currency = $this->money->get_currency(intval($payment_method->currency_id));
//		$currency_code = $payment_currency->code;
		// номер заказа
		$order_id = $order->id;

		$public_key = $payment_settings['public_key'];
		$account = $order_id;
		$sum = $amount;
		$desc = 'Оплата по заказу №' . $order_id;

		$button =	'<form name="unitpay" action="https://unitpay.ru/pay/' . $public_key . '" method="get">
            <input type="hidden" name="sum" value="' . $sum . '">
            <input type="hidden" name="account" value="' . $account . '">
            <input type="hidden" name="desc" value="' . $desc . '">
            <input type=submit class=payment_button value="' . $button_text . '">
        </form>';

		return $button;
	}

}