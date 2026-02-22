<?php
require 'vendor/autoload.php';
require_once 'config/db.php';

use Razorpay\Api\Api;

$stmt = $pdo->query("SELECT razorpay_key, razorpay_secret FROM settings WHERE id=1");
$settings = $stmt->fetch();

$api = new Api($settings['razorpay_key'], $settings['razorpay_secret']);

$data = json_decode(file_get_contents("php://input"), true);

try {

    $attributes = [
        'razorpay_order_id' => $data['razorpay_order_id'],
        'razorpay_payment_id' => $data['razorpay_payment_id'],
        'razorpay_signature' => $data['razorpay_signature']
    ];

    $api->utility->verifyPaymentSignature($attributes);

    echo json_encode(['status'=>'success']);

} catch(Exception $e){
    echo json_encode(['status'=>'failed']);
}