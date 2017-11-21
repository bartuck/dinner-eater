<?php
header("Access-Control-Allow-origin: *");
header("Cache-Control: no-cache");

$postdata = file_get_contents("php://input");
$json = json_decode($postdata, true);
$data = $json['data'];

$dbHost = 'localhost';
$dbName = 'menu';
$dbUser= 'menu';
$dbPassword= 'menu';

$db = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8", "{$dbUser}", "{$dbPassword}");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

performAction($data, $db);

function performAction($data, $db) {
    switch ($data['action']) {
        case 'addOrder':
            addOrder($data, $db);
            break;
        case 'removeOrder':
            removeOrder($data, $db);
            break;
        case 'updatePayment':
            updatePayment($data, $db);
            break;
    }
}

function updatePayment($data, $db) {
    $query = $db->prepare("UPDATE payment SET status = :status WHERE payment.id = :id");
    $query->execute(array(
        'id' => $data['id'],
        'status' => $data['status']
    ));
}

function addOrder($data, $db) {
    $query = $db->prepare("INSERT INTO orders (date_time, order_from, user_id) VALUES (:date_time, :order_from, :user_id)");
    $query->execute(array(
        'date_time' => $data['date_time'],
        'order_from' => $data['order_from'],
        'user_id' => $data['user_id']
    ));
    $order = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 1")->fetchAll(PDO::FETCH_NAMED);
    $orderId = $order[0]['id'];
    $paymentsLen = count($data['payments']);
    if ($paymentsLen) {
        addPayments($data['payments'], $paymentsLen, $db, $orderId);
    }
}

function addPayments($payments, $paymentsLen, $db, $orderId) {
    for ($i = 0; $i < $paymentsLen; $i++) {
        addPayment($payments[$i], $db, $orderId);
    }
}

function addPayment($data, $db, $orderId) {
    $query = $db->prepare("INSERT INTO payment (user_id, what, cost, status, order_id) VALUES (:user_id, :what, :cost, :status, :order_id)");
    $query->execute(array(
        'user_id' => $data['user_id'],
        'what' => $data['what'],
        'cost' => $data['cost'],
        'status' => $data['status'],
        'order_id' => $orderId
    ));
}

function getPaymentsByOrderId($db, $id) {
    return $db->query("SELECT * FROM payment WHERE order_id=$id")->fetchAll(PDO::FETCH_NAMED);
}

function removeOrder($data, $db) {
    $id = $data['id'];
    removePayments($db, $id);
    $query = $db->prepare("DELETE FROM orders WHERE orders.id = :id");
    $query->execute(array(
        'id' => $id
    ));
}

function removePayments($db, $orderId) {
    $payments = getPaymentsByOrderId($db, $orderId);
    $paymentsLen = count($payments);
    for ($i = 0; $i < $paymentsLen; $i++) {
        removePayment($payments[$i]['id'], $db);
    }
}

function removePayment($paymentId, $db) {
    $query = $db->prepare("DELETE FROM payment WHERE payment.id = :id");
    $query->execute(array(
        'id' => $paymentId
    ));
}

function getAllOrders($db) {
    $orders = $db->query("SELECT * FROM orders ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_NAMED);
    foreach ($orders as &$order) {
        $order['payments'] = getPaymentsByOrderId($db, $order['id']);
    }
    return $orders;
}

function getAllUsers($db) {
    return $db->query("SELECT * FROM user")->fetchAll(PDO::FETCH_NAMED);
}

$response = array(
    'orders' => getAllOrders($db),
    'users' => getAllUsers($db)
);
echo json_encode($response);
