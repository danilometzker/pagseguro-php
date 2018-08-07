<?php

/**
 * @author Danilo Metzker
 * @version 1.0
 * @copyright 2018 Todos os direitos reservados
 *
 * Pagseguro transparent checkout class
 *
 * Summary
 *  0. Miscelaneus
 *  1. Session
 *  2. Cart
 *  3. Shipping
 *  4. Sender
 *  5. Credit Card
 *  6. Online Debit
 *  7. Boleto
 *  8. Notifications
 */

class PagSeguro{

	private $email;
	private $token;
	private $items;
	private $notification_url;
	public 	$mode;
	private $shipping;
	private $sender;

	public $stc_url;
	public $ws_url;

	public function __construct($email, $token, $notification_url, $mode=true){
		$this->email = $email;
		$this->token = $token;

		if($mode == false){
			$this->stc_url = "stc.sandbox.pagseguro.uol.com.br";
			$this->ws_url = "ws.sandbox.pagseguro.uol.com.br";
		}else{
			$this->stc_url = "stc.pagseguro.uol.com.br";
			$this->ws_url = "ws.pagseguro.uol.com.br";
		}
	}

	/*
  
    0. Miscelaneus
  
    */
	public function moneyFormat($number){
		$number = str_replace(",", "", $number);
		$number = number_format($number, 2,".","");

		return (string)$number;
	}

	/*
  
	1.  Session
  
	*/
	public function getSession(){

		$url_session = "https://$this->ws_url/v2/sessions/?email=$this->email&token=$this->token";
		$ch = curl_init();

		curl_setopt($ch,CURLOPT_URL, $url_session);

		$headers = array(
			'Content-Type: application/xml; charset=ISO-8859-1'
		);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$result = curl_exec($ch);
		
		$data = simplexml_load_string($result);
		return $data->id;

	}

	/*
  
	2. Cart
    
	*/
	public function addItem($id, $description, $quantity, $amount){

		$this->items[] = array(
			'id' => $id,
			'description' => $description,
			'quantity' => $quantity,
			'amount' => $amount
		);

		return true;
	}

	public function itemsPricing(){
		$price = 0;
		foreach ($this->items as $item){
			$price += $item['amount'];
		}
		return $this->moneyFormat($price);
	}

	/*
  
	3. Shipping
  
	*/
	public function setShipping(
		$address_street, 		// Shipping address street, e.g: Av. Visconde de Porto  Alegre
		$address_number, 		// Shipping address number, e.g: 1678
		$address_complement, 	// Shipping address complement, e.g: Casa 3
		$address_district, 		// Shipping address district, e.g: Praça 14 de Janeiro
		$address_postalcode, 	// Shipping address postalcode, e.g: 69020130 (69020-130)
		$address_city, 			// Shipping address city, e.g: Manaus
		$address_state, 		// Shipping address state, e.g: Amazonas
		$address_country, 		// Shipping address country, e.g: Brasil
		$type, 					// Shipping address type, e.g: 1 = PAC, 2 = SEDEX, 3 = None
		$cost 					// Shipping cost, e.g: 31.50
		){

		$this->shipping['address_street'] 		= addslashes($address_street);
		$this->shipping['address_number'] 		= addslashes($address_number);
		$this->shipping['address_complement'] 	= addslashes($address_complement);
		$this->shipping['address_district'] 	= addslashes($address_district);
		$this->shipping['address_postalcode'] 	= addslashes($address_postalcode);
		$this->shipping['address_city'] 		= addslashes($address_city);
		$this->shipping['address_state'] 		= addslashes($address_state);
		$this->shipping['address_country'] 		= addslashes($address_country);
		$this->shipping['type'] 				= addslashes($type);
		$this->shipping['cost'] 				= addslashes($cost);

		return true;
	}

	/*
  
	4. Sender
  
	*/
	public function setSender(
		$name, 				// Sender name, e.g: Danilo Metzker
		$cpf, 				// Sender cpf, e.g: 03784722245 (037.847.222-45)
		$email, 			// Sender email, e.g: danilo@metzker.com.br
		$phone_areacode, 	// Sender phone areacode, e.g: 92
		$phone_number, 		// Sender phone number, e.g: 991278199 (99127-8199)
		$hash 				// Sender hash, e.g: e62595ee98b585153dac87ce1ab69c3c
		){

		$this->sender['name'] 			= addslashes($name);
		$this->sender['cpf'] 			= addslashes($cpf);
		$this->sender['email'] 			= addslashes($email);
		$this->sender['phone_areacode'] = addslashes($phone_areacode);
		$this->sender['phone_number'] 	= addslashes($phone_number);
		$this->sender['hash'] 			= addslashes($hash);
	}

	/*
  
  5. Credit Card
  
	*/
	public function CreditCardPayment(		
		$token,					// installment token, e.g: e62595ee98b585153dac87ce1ab69c3c
		$installment_quantity, 	// installment quantity, e.g: 2
		$installment_value, 	// Installment value, e.g: 349.5 (total 699 / 2)
		$no_interest,			// No interest installment, e.g: 2
		$credit_card_name,		// Credit card name, e.g JOHN DOE
		$credit_card_cpf, 		// Credit card cpf, e.g: 01333848374
		$credit_card_birthdate,	// Credit card birthdate, e.g: 02/01/1995
		$credit_card_areacode,	// Credit card areacode, e.g: 92
		$credit_card_phone,		// Credit card phone, e.g: 992648379
		$payment_reference 		// Payment reference, e.g: REF0001
		){

		// $installment_value = $this->moneyFormat(($total_value / $installment_quantity));


		$items_array = array();
		for($i=0; $i<count($this->items); $i++){
			$item_index = $i+1;

			$items_array["itemId$item_index"] 			= $this->items[$i]['id'];
			$items_array["itemDescription$item_index"] 	= $this->items[$i]['description'];
			$items_array["itemAmount$item_index"] 		= $this->items[$i]['amount'];
			$items_array["itemQuantity$item_index"]		= $this->items[$i]['quantity'];
		}

		$payment_data = array(
			"paymentMode" 					    => "default",
			"paymentMethod" 				    => "CreditCard",
			"receiverEmail" 				    => $this->email,
			"currency" 						      => "BRL",
			"extraAmount" 					    => $this->moneyFormat(0),
			"notificationURL" 				  => $this->notification_url,
			"reference" 					      => $payment_reference,
			"senderName" 					      => $this->sender['name'],
			"senderCPF" 					      => $this->sender['cpf'],
			"senderAreaCode" 				    => $this->sender['phone_areacode'],
			"senderPhone" 					    => $this->sender['phone_number'],
			"senderEmail" 					    => $this->sender['email'],
			"senderHash" 					      => $this->sender['hash'],
			"shippingAddressStreet" 		=> $this->shipping['address_street'],
			"shippingAddressNumber" 		=> $this->shipping['address_number'],
			"shippingAddressComplement" => $this->shipping['address_complement'],
			"shippingAddressDistrict" 	=> $this->shipping['address_district'],
			"shippingAddressPostalCode" => $this->shipping['address_postalcode'],
			"shippingAddressCity" 			=> $this->shipping['address_city'],
			"shippingAddressState" 			=> $this->shipping['address_state'],
			"shippingAddressCountry" 		=> $this->shipping['address_country'],
			"shippingType" 					    => $this->shipping['type'],
			"shippingCost" 					    => $this->shipping['cost'],
			"creditCardToken" 				  => $token,
			"installmentQuantity" 			=> $installment_quantity,
			"installmentValue" 				  => $installment_value,
			"noInterestInstallmentQuantity" => $no_interest,
			"creditCardHolderName" 			=> $credit_card_name,
			"creditCardHolderCPF" 			=> $credit_card_cpf,
			"creditCardHolderBirthDate" => $credit_card_birthdate,
			"creditCardHolderAreaCode" 	=> $credit_card_areacode,
			"creditCardHolderPhone" 		=> $credit_card_phone,
			"billingAddressStreet" 			=> $this->shipping['address_street'],
			"billingAddressNumber" 			=> $this->shipping['address_number'],
			"billingAddressComplement" 	=> $this->shipping['address_complement'],
			"billingAddressDistrict" 		=> $this->shipping['address_district'],
			"billingAddressPostalCode" 	=> $this->shipping['address_postalcode'],
			"billingAddressCity" 			  => $this->shipping['address_city'],
			"billingAddressState" 			=> $this->shipping['address_state'],
			"billingAddressCountry" 		=> $this->shipping['address_country']
		);

		$url_array = array_merge($items_array, $payment_data);

		$url_payment = "https://$this->ws_url/v2/transactions/";
		$url_array = "email=" . $this->email . "&" . token=$this->token . "&" . http_build_query($url_array);
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url_payment);

		$headers = array(
			'Content-Type: application/x-www-form-urlencoded; charset=ISO-8859-1'
		);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $url_array);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$result = curl_exec($ch);

		return $result;
	}

	/*
  
	6. Online Debit
  
	*/
	public function OnlineDebitPayment(		
		$bank,
		$payment_reference 		// Payment reference, e.g: REF0001
		){

		switch($bank){
			case 1:
				$bank_name = "bradesco";
				break;
			case 2:
				$bank_name = "itau";
				break;
			case 3:
				$bank_name = "bancodobrasil";
				break;
			case 4:
				$bank_name = "banrisul";
				break;
			case 5:
				$bank_name = "HSBC";
				break;

			default:
				$bank_name = "bradesco";
				break;
		}

		$items_array = array();
		for($i=0; $i<count($this->items); $i++){
			$item_index = $i+1;

			$items_array["itemId$item_index"] 			    = $this->items[$i]['id'];
			$items_array["itemDescription$item_index"] 	= $this->items[$i]['description'];
			$items_array["itemAmount$item_index"] 		  = $this->items[$i]['amount'];
			$items_array["itemQuantity$item_index"]		  = $this->items[$i]['quantity'];
		}

		$payment_data = array(
			"paymentMode" 					      => "default",
			"paymentMethod" 				      => "online_debit",
			"bankName"						        => $bank_name,
			"receiverEmail" 				      => $this->email,
			"currency" 						        => "BRL",
			"extraAmount" 					      => $this->moneyFormat(0),
			"notificationURL" 				    => $this->notification_url,
			"reference" 					        => $payment_reference,
			"senderName" 					        => $this->sender['name'],
			"senderCPF" 					        => $this->sender['cpf'],
			"senderAreaCode" 				      => $this->sender['phone_areacode'],
			"senderPhone" 					      => $this->sender['phone_number'],
			"senderEmail" 					      => $this->sender['email'],
			"senderHash" 					        => $this->sender['hash'],
			"shippingAddressStreet" 		  => $this->shipping['address_street'],
			"shippingAddressNumber" 		  => $this->shipping['address_number'],
			"shippingAddressComplement" 	=> $this->shipping['address_complement'],
			"shippingAddressDistrict" 		=> $this->shipping['address_district'],
			"shippingAddressPostalCode" 	=> $this->shipping['address_postalcode'],
			"shippingAddressCity" 			  => $this->shipping['address_city'],
			"shippingAddressState" 			  => $this->shipping['address_state'],
			"shippingAddressCountry" 		  => $this->shipping['address_country'],
			"shippingType" 					      => $this->shipping['type'],
			"shippingCost" 					      => $this->shipping['cost'],
			"billingAddressStreet" 			  => $this->shipping['address_street'],
			"billingAddressNumber" 			  => $this->shipping['address_number'],
			"billingAddressComplement" 		=> $this->shipping['address_complement'],
			"billingAddressDistrict" 		  => $this->shipping['address_district'],
			"billingAddressPostalCode" 		=> $this->shipping['address_postalcode'],
			"billingAddressCity" 			    => $this->shipping['address_city'],
			"billingAddressState" 			  => $this->shipping['address_state'],
			"billingAddressCountry" 		  => $this->shipping['address_country']
		);

		$url_array = array_merge($items_array, $payment_data);

		$url_payment = "https://$this->ws_url/v2/transactions/";
		$url_array = "email=" . $this->email . "&" . token=$this->token . "&" . http_build_query($url_array);
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url_payment);

		$headers = array(
			'Content-Type: application/x-www-form-urlencoded; charset=ISO-8859-1'
		);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $url_array);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$result = curl_exec($ch);

		return $result;
	}

	/*
  
  7. Boleto
  
	*/
	public function BoletoPayment(
		$payment_reference 		// Payment reference, e.g: REF0001
		){

		$items_array = array();
		for($i=0; $i<count($this->items); $i++){
			$item_index = $i+1;

			$items_array["itemId$item_index"] 			    = $this->items[$i]['id'];
			$items_array["itemDescription$item_index"] 	= $this->items[$i]['description'];
			$items_array["itemAmount$item_index"] 		  = $this->items[$i]['amount'];
			$items_array["itemQuantity$item_index"]		  = $this->items[$i]['quantity'];
		}

		$payment_data = array(
			"paymentMode" 					=> "default",
			"paymentMethod" 				=> "boleto",
			"receiverEmail" 				=> $this->email,
			"currency" 						  => "BRL",
			"extraAmount" 					=> $this->moneyFormat(0),
			"notificationURL" 			=> $this->notification_url,
			"reference" 					  => $payment_reference,
			"senderName" 					  => $this->sender['name'],
			"senderCPF" 					  => $this->sender['cpf'],
			"senderAreaCode" 				=> $this->sender['phone_areacode'],
			"senderPhone" 					=> $this->sender['phone_number'],
			"senderEmail" 					=> $this->sender['email'],
			"senderHash" 					      => $this->sender['hash'],
			"shippingAddressStreet" 		=> $this->shipping['address_street'],
			"shippingAddressNumber" 		=> $this->shipping['address_number'],
			"shippingAddressComplement" => $this->shipping['address_complement'],
			"shippingAddressDistrict" 	=> $this->shipping['address_district'],
			"shippingAddressPostalCode" => $this->shipping['address_postalcode'],
			"shippingAddressCity" 			=> $this->shipping['address_city'],
			"shippingAddressState" 			=> $this->shipping['address_state'],
			"shippingAddressCountry" 		=> $this->shipping['address_country'],
			"shippingType" 					     => $this->shipping['type'],
			"shippingCost" 					    => $this->shipping['cost'],
			"billingAddressStreet" 			=> $this->shipping['address_street'],
			"billingAddressNumber" 			=> $this->shipping['address_number'],
			"billingAddressComplement" 	=> $this->shipping['address_complement'],
			"billingAddressDistrict" 		=> $this->shipping['address_district'],
			"billingAddressPostalCode" 	=> $this->shipping['address_postalcode'],
			"billingAddressCity" 			  => $this->shipping['address_city'],
			"billingAddressState" 			=> $this->shipping['address_state'],
			"billingAddressCountry" 		=> $this->shipping['address_country']
		);

		$url_array = array_merge($items_array, $payment_data);

		$url_payment = "https://$this->ws_url/v2/transactions/";
		$url_array = "email=" . $this->email . "&" . token=$this->token . "&" . http_build_query($url_array);
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url_payment);

		$headers = array(
			'Content-Type: application/x-www-form-urlencoded; charset=ISO-8859-1'
		);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $url_array);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$result = curl_exec($ch);

		return $result;
	}

	/*
  
	8. Notifications
  
	*/
	public function getNotification($notification_code){
		$url = "https://$this->ws_url/v3/transactions/notifications/$notification_code?email=$this->email&token=$this->token";

	    $ch = curl_init($url);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $result = curl_exec($ch);
	    curl_close($ch);

	    if($result == 'Unauthorized'){
	        return false;
	    }else{
	    	return $result;
	    }
	}

}
?>
