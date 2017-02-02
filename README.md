Salted Payment

Supported payment methods
- POLi Payment
- Paystation Payment

Usage:
First thing: make sure the class that you use as "order" extends SaltedOrder class

== One off payment ==
$order = {THE_ORDER_OBJECT};
return $order->Pay({STRING_OF_PAYMENT_METHOD}); // you will be redirected to the payment page

== Pay and also setup a future payment ==

$order = {THE_ORDER_OBJECT};
$order->RecursiveFrequency = {INT_EVERY_X_DAYS_IT_PAYS};
return $order->Pay({STRING_OF_PAYMENT_METHOD}, true); // you will be redirected to the payment page

ps: you will need to write your own dev task and setup cron jobs
