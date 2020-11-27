<?php

chdir ('../../');
require_once('api/Simpla.php');
$simpla = new Simpla();

//header('Content-type:application/json;  charset=utf-8');
$method = '';
$params = array();
$result = array();
if ((isset($_GET['params'])) && (isset($_GET['method'])) && (isset($_GET['params']['signature']))){
    $params = $_GET['params'];
    $method = $_GET['method'];
    $signature = $params['signature'];
    if (empty($signature) ){
        $status_sign = false;
    }else{

        $order_id = $params['account'];
        $order = $simpla->orders->get_order(intval($order_id ));
        if(empty($order)){
            $result = array('error' =>
                array('message' => 'заказа не существует')
            );
        }else{

            $payment_method = $simpla->payment->get_payment_method(intval($order->payment_method_id));
            if(empty($payment_method)) {
                $result = array('error' =>
                    array('message' => 'платежного метода не существует')
                );
            }else{
                $settings = unserialize($payment_method->settings);
                $secret_key = $settings['secret_key'];
                $status_sign = verifySignature($params, $method, $secret_key);
            }

        }

    }
}else{
    $status_sign = false;
}
$status_sign = true;
if ($status_sign){
    switch ($method) {
        case 'check':
            $result = check( $params );
            break;
        case 'pay':
            $result = payment( $params );
            break;
        case 'error':
            $result = error( $params );
            break;
        default:
            $result = array('error' =>
                array('message' => 'неверный метод')
            );
            break;
    }
}else{
    $result = array('error' =>
        array('message' => 'неверная сигнатура')
    );
}
echo json_encode($result);
die();
function verifySignature($params, $method, $secret)
{
    return $params['signature'] == getSignature($method, $params, $secret);
}
function getSignature($method, array $params, $secretKey)
{
    ksort($params);
    unset($params['sign']);
    unset($params['signature']);
    array_push($params, $secretKey);
    array_unshift($params, $method);
    return hash('sha256', join('{up}', $params));
}
function check( $params )
{
    $order_id = $params['account'];
    $simpla = new Simpla();
    $order = $simpla->orders->get_order(intval( $order_id ));
    if(empty($order)) {
        $result = array('error' =>
            array('message' => 'заказа не существует')
        );
    }else{
        $payment_method = $simpla->payment->get_payment_method(intval($order->payment_method_id));
        if(empty($payment_method)){
            $result = array('error' =>
                array('message' => 'Неизвестный метод оплаты')
            );
        }else{

            $sum=$simpla->money->convert($order->total_price, $payment_method->currency_id, false) ;
            $sum=number_format($sum, 2, '.', '');


            if ((float)$sum != (float)$params['orderSum']) {
                $result = array('error' =>
                    array('message' => 'не совпадает сумма заказа')
                );
            }else{

                $payment_currency = $simpla->money->get_currency(intval($payment_method->currency_id));
        		$currency_code = $payment_currency->code;
                if ($currency_code == 'RUR'){
                    $currency_code = 'RUB';
                }

                if ($currency_code != $params['orderCurrency']) {
                    $result = array('error' =>
                        array('message' => 'не совпадает валюта заказа')
                    );
                }
                else{
                    $result = array('result' =>
                        array('message' => 'Запрос успешно обработан')
                    );
                }
            }

        }
    }

    return $result;
}

function error( $params )
{
	$order_id = $params['account'];
    $simpla = new Simpla();
    $order = $simpla->orders->get_order(intval( $order_id ));
    if(empty($order)) {
        $result = array('error' =>
            array('message' => 'заказа не существует')
        );
    }else{
        $payment_method = $simpla->payment->get_payment_method(intval($order->payment_method_id));
        if(empty($payment_method)){
            $result = array('error' =>
                array('message' => 'Неизвестный метод оплаты')
            );
        }else{

            $sum=$simpla->money->convert($order->total_price, $payment_method->currency_id, false) ;
            $sum=number_format($sum, 2, '.', '');


            if ((float)$sum != (float)$params['orderSum']) {
                $result = array('error' =>
                    array('message' => 'не совпадает сумма заказа')
                );
            }else{

                $payment_currency = $simpla->money->get_currency(intval($payment_method->currency_id));
                $currency_code = $payment_currency->code;
                if ($currency_code == 'RUR'){
                    $currency_code = 'RUB';
                }

                if ($currency_code != $params['orderCurrency']) {
                    $result = array('error' =>
                        array('message' => 'не совпадает валюта заказа')
                    );
                }
                else{


                    if($order->paid){
                        $result = array('error' =>
                            array('message' => 'Этот заказ уже оплачен')
                        );
                    }else{
                        // Установим статус не оплачен
                        $simpla->orders->update_order(intval($order->id), array('paid'=>0));

                        $result = array('error' =>
                            array('message' => $params['errorMessage'])
                        );
                    }

                }
            }

        }
    }

    return $result;
}

function payment( $params )
{
    $order_id = $params['account'];
    $simpla = new Simpla();
    $order = $simpla->orders->get_order(intval( $order_id ));
    if(empty($order)) {
        $result = array('error' =>
            array('message' => 'заказа не существует')
        );
    }else{
        $payment_method = $simpla->payment->get_payment_method(intval($order->payment_method_id));
        if(empty($payment_method)){
            $result = array('error' =>
                array('message' => 'Неизвестный метод оплаты')
            );
        }else{

            $sum=$simpla->money->convert($order->total_price, $payment_method->currency_id, false) ;
            $sum=number_format($sum, 2, '.', '');


            if ((float)$sum != (float)$params['orderSum']) {
                $result = array('error' =>
                    array('message' => 'не совпадает сумма заказа')
                );
            }else{

                $payment_currency = $simpla->money->get_currency(intval($payment_method->currency_id));
                $currency_code = $payment_currency->code;
                if ($currency_code == 'RUR'){
                    $currency_code = 'RUB';
                }

                if ($currency_code != $params['orderCurrency']) {
                    $result = array('error' =>
                        array('message' => 'не совпадает валюта заказа')
                    );
                }
                else{


                    if($order->paid){
                        $result = array('error' =>
                            array('message' => 'Этот заказ уже оплачен')
                        );
                    }else{
                        // Установим статус оплачен
                        $simpla->orders->update_order(intval($order->id), array('paid'=>1));

                        // Спишем товары
                        $simpla->orders->close(intval($order->id));
						
						try {
							$simpla->notify->email_order_user(intval($order->id));
							$simpla->notify->email_order_admin(intval($order->id));
						} catch (Exception $e) {
						
						}

                        $result = array('result' =>
                            array('message' => 'Запрос успешно обработан')
                        );
                    }

                }
            }

        }
    }

    return $result;
}

