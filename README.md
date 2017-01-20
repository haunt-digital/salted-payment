Salted Payment

Supported payment methods
- POLi Payment
- Paystation Payment

Proactive and Passive modes
- proactive: create payment object first in the DB, and then wait for the remote payment gateway's postback to update its status. If you are using this method, please make sure you write your own task to remove expired payment objects.
- passive: create no payment object until remote payment gateway posts back.

Usage:
- POLi
Passive:
< inside a controller class >
$order = <<the order object that the customer is paying for>>;
$poli = Poli::process($amount_due, $order->FullRef, 'Order');
if (empty($poli['Success'])) {
    return $this->httpError(500, $poli['ErrorMessage']);
}
if (empty($poli['NavigateURL'])) {
    return $this->httpError(500, 'Unknown payment gateway error.');
}
return $this->controller->redirect($poli['NavigateURL']);
