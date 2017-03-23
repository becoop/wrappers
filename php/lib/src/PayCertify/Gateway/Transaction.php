<?php

namespace PayCertify\Gateway;

use PayCertify\Gateway\Base\Resource;

class Transaction extends Resource {

  const API_ENDPOINT = '/ws/encgateway2.asmx/ProcessCreditCard';

  const ATTRIBUTES = [
    'transaction_id', 'type', 'amount', 'currency', 'card_number', 'expiration_month', 'expiration_year',
    'name_on_card', 'cvv', 'billing_address', 'billing_city', 'billing_state', 'billing_country',
    'billing_zip', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_country',  'shipping_zip',
    'email', 'phone', 'ip', 'order_description', 'customer_id'
  ];

  public function __construct($attributes) {
    parent::__construct($attributes);
  }

  public function getID() {
    return $this->transaction_id;
  }

  public function getExpirationMonth() {
    return $this->expiration_month;
  }

  public function getExpirationYear() {
    return $this->expiration_year;
  }

  public function getType() {
    return $this->type;
  }

  /**
   * @return $this
   */
  public function save() {
    parent::save();
    $this->transaction_id = (string) $this->getResponse()->PNRef;
    return $this;
  }

  /**
   * @return bool
   */
  public function isSuccess() {
    return parent::isSuccess() && $this->getResponse()->response->result == '0';
  }

  /**
   * @return array
   */
  public function attributesToGatewayFormat() {
    return array_merge(
      parent::attributesToGatewayFormat(),
      AttributeMapping::expirationDate($this),
      AttributeMapping::type($this)
    );
  }
}
