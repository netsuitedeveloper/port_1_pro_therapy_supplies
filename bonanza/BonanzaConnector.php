<?php
    require_once 'PHPToolkit/NetSuiteService.php';

    echo "<html><body>";    echo '<pre>';  
    $dev_name = "tH1ihRYulwyErDx";
    $cert_name = "DxQ1wu7iM9tgXzo";
    $token = 'TmRtVC00VO'; //HDEsMV0Knl TmRtVC00VO
    $currentTime = getDate(); //    
    $prevdate = new DateTime();
    $prevdate = $prevdate -> modify('-7 days');
    echo $prevdate ->format('Y-m-d'); echo '~';
    $date = date('Y-m-d');
    echo $date. " \n";

    $url = "https://api.bonanza.com/api_requests/secure_request";
    $headers = array("X-BONANZLE-API-DEV-NAME: " . $dev_name, "X-BONANZLE-API-CERT-NAME: " . $cert_name);

    $item = array(
        'orderRole' => 'seller',
        'soldTimeFrom' => $prevdate->format('Y-m-d'),
        'soldTimeTo' => $date
    );
    $args = array(
        'item' => $item,
        'requesterCredentials' => array(
            'bonanzleAuthToken' => $token
        )
    );
    echo "---Request---" . " \n";
    print_r($args);
    $post_fields = "getOrdersRequest=" .  urlencode(json_encode($args));
    echo "<pre> Request: $post_fields \n </pre>";
    $connection = curl_init($url);
    $curl_options = array(
        CURLOPT_SSL_VERIFYPEER => true,		//0
        CURLOPT_SSL_VERIFYHOST => 2,		//0
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_POST => 1,					//CURLOPT_HTTPGET => 1,
        CURLOPT_RETURNTRANSFER => 1
    );
    curl_setopt_array($connection, $curl_options);
    $json_response = curl_exec($connection);

    if (curl_errno($connection) > 0) {
        echo curl_error($connection) . "\n";
        exit(2);
    }
    curl_close($connection);
    $response = json_decode($json_response,true);


    echo "Number of getOrders : " ; echo count($response['getOrdersResponse']['orderArray']); echo " \n\n";
//    echo "OrderResponse : " . " \n"; print_r($response); echo "\n";

    $orderArray = $response['getOrdersResponse']['orderArray'];

    // First, initiate login
    $service_net = new NetSuiteService();

    foreach ( $orderArray as $orderArr){
        if($orderArr[order][orderStatus]=='Completed' && $orderArr[order][checkoutStatus][status]=='Complete'){

//            echo "Filtered Orders : orderStatus = 'Completed'" . " \n";print_r($orderArr[order]);

            $buyer_email = $orderArr[order][transactionArray][transaction][buyer][email];

            echo "Buyer Email : ". " \n"; echo $buyer_email; echo "\n";

            $service_net->setSearchPreferences(false, 20);                                                                                               
            $search_customer = new CustomerSearch();

            $search_customer->basic->email->operator = "contains";                              
            $search_customer->basic->email->searchValue = (string)$buyer_email;

            $request_search_customer = new SearchRequest();
            $request_search_customer->searchRecord = $search_customer;
            $searchResponse = $service_net->search($request_search_customer);

            echo "Customer searchRecord Response filterd by Buyer Email :". " \n";           print_r($searchResponse);
            
            $search_internalId = $searchResponse->searchResult->recordList->record['0']->internalId;                

            if (!$search_internalId) {    
                $customer = new Customer();
                echo "Create New Customer". " \n";
                if ($orderArr[order][shippingAddress][name]){
                    $buyer_name = $orderArr[order][shippingAddress][name];
                }
                else {
                    $buyer_name = $orderArr[order][buyerUserName];
                }

                if ($buyer_name) 
                {
                    $customer_name = explode(" ", $buyer_name );
                    if ( count($customer_name) == 3 ){
                        $customer->firstName = $customer_name[0];
                        $customer->middleName = $customer_name[1];
                        $customer->lastName = $customer_name[2];
                    }else if ( count($customer_name) == 2 ){
                        $customer->firstName = $customer_name[0];
                        $customer->lastName = $customer_name[1];
                    }else if ( count($customer_name) > 3 ){
                        $customer->firstName = $customer_name[0];
                        $customer->middleName = $customer_name[1];
                        $customer_over_name = $customer_name;
                        unset($customer_over_name[0]);
                        unset($customer_over_name[1]);
                        $customer->lastName = implode(" ", $customer_over_name); 
                    }else{
                        $customer->firstName = $buyer_name;
                    }                                 

                }                                    
                $customer->isPerson = "company";     
                $customer->companyName =                    
                $customer->email = $buyer_email;

                $shippingAddress = $orderArr[order][shippingAddress];
                $customer->addressbookList->addressbook['addressee'] = $shippingAddress[name];                               
                $customer->addressbookList->addressbook['addr1'] = $shippingAddress[street1];              
                $customer->addressbookList->addressbook['addr2'] = $shippingAddress[street2];              
                $customer->addressbookList->addressbook['city'] = $shippingAddress[cityName];
                $customer->addressbookList->addressbook['country'] = $shippingAddress[country];
                $customer->addressbookList->addressbook['state'] = $shippingAddress[stateOrProvince];
                $customer->addressbookList->addressbook['zip'] = $shippingAddress[postalCode];
                $customer->addressbookList->addressbook['defaultShipping'] = TRUE;
                $customer->addressbookList->replaceAll = 1;     

                $request_customer_add = new AddRequest();                                                       
                $request_customer_add->record = $customer;
                print_r($customer);

                $addResponse_customer_add = $service_net->add($request_customer_add);

                if (!$addResponse_customer_add->writeResponse->status->isSuccess) {
                    echo "ADD CUSTOMER ERROR". " \n";
                    print_r($addResponse_customer_add);
                } else {
                    $customer_internal_id = $addResponse_customer_add->writeResponse->baseRef->internalId;
                    echo "ADD CUSTOMER SUCCESS". " \n";
                    echo "New Customer ID : "; print_r($customer_internal_id);
                } 
            } else {                                                 
                $customer = new Customer(); 
                echo "Update Existing Customer". " \n";
                $customer->internalId = $search_internalId;  
 
                if ($orderArr[order][shippingAddress][name]){
                    $buyer_name = $orderArr[order][shippingAddress][name];
                }
                else {
                    $buyer_name = $orderArr[order][buyerUserName];
                }

                if ($buyer_name) 
                {
                    $customer_name = explode(" ", $buyer_name );
                    if ( count($customer_name) == 3 ){
                        $customer->firstName = $customer_name[0];
                        $customer->middleName = $customer_name[1];
                        $customer->lastName = $customer_name[2];
                    }else if ( count($customer_name) == 2 ){
                        $customer->firstName = $customer_name[0];
                        $customer->lastName = $customer_name[1];
                    }else if ( count($customer_name) > 3 ){
                        $customer->firstName = $customer_name[0];
                        $customer->middleName = $customer_name[1];
                        $customer_over_name = $customer_name;
                        unset($customer_over_name[0]);
                        unset($customer_over_name[1]);
                        $customer->lastName = implode(" ", $customer_over_name); 
                    }else{
                        $customer->firstName = $buyer_name;
                    }                                 

                }                                    
                $customer->isPerson = "company";     
                $customer->companyName =                    
                $customer->email = $buyer_email;
/*
                $shippingAddress = $orderArr[order][shippingAddress];
                $customer->addressbookList->addressbook['addressee'] = $shippingAddress[name];                               
                $customer->addressbookList->addressbook['addr1'] = $shippingAddress[street1];              
                $customer->addressbookList->addressbook['addr2'] = $shippingAddress[street2];              
                $customer->addressbookList->addressbook['city'] = $shippingAddress[cityName];
                $customer->addressbookList->addressbook['country'] = $shippingAddress[country];
                $customer->addressbookList->addressbook['state'] = $shippingAddress[stateOrProvince];
                $customer->addressbookList->addressbook['zip'] = $shippingAddress[postalCode];
                $customer->addressbookList->addressbook['defaultShipping'] = TRUE;
                $customer->addressbookList->replaceAll = 1;     
*/
                print_r($customer);

                $request_customer_update = new UpdateRequest();
                $request_customer_update->record = $customer;

                $addResponse_customer_update = $service_net->update($request_customer_update);
                if (!$addResponse_customer_update->writeResponse->status->isSuccess) {
                    echo "Update ERROR". " \n";                   print_r($addResponse_customer_update);
                } else {
                    $customer_internal_id = $addResponse_customer_update->writeResponse->baseRef->internalId;
                    echo "Update SUCCESS". " \n"; 
                    echo "Customer Internal ID :";  print_r($customer_internal_id);
                }                                                                                                          
            }                                                                       
            $service_net->setSearchPreferences(false, 20);                                                                                               
            $search_sales_order = new TransactionSearch();              

            $search_customer->basic->entity->internalId->operator = "equalTo";                              
            $search_customer->basic->entity->internalId->searchValue = $customer_internal_id;

            $search_customer->basic->itemList->item[0]->item->internalId->operator = "equalTo";
            $search_customer->basic->itemList->item[0]->item->internalId->searchValue = $orderArr[order][itemArray][0][item][sku];

            $extenalorderField = new SearchStringCustomField();
            $extenalorderField->searchValue = 'Bonanza';
            $extenalorderField->internalId = 226;
            $extenalorderField->scriptId = 'custbodystorefront';
            $extenalorderField->operator = "is";    

            $extenalOrderUrlField = new SearchStringCustomField();
            $extenalOrderUrlField->searchValue = (string)$orderArr[order][orderID];
            $extenalOrderUrlField->internalId = 227;
            $extenalorderField->scriptId = 'custbodystorefrontorder';
            $extenalOrderUrlField->operator = "is";

            $search_sales_order->basic->customFieldList->customField = array($extenalorderField, $extenalOrderUrlField);

            $request_search_so = new SearchRequest();
            $request_search_so->searchRecord = $search_sales_order; 
            $searchSOResponse = $service_net->search($request_search_so);        
            print_r($searchSOResponse);                    
            $searchSO_internalId = $searchSOResponse->searchResult->recordList->record['0']->internalId;    

            if($searchSO_internalId){
                echo "Update Sales Order\n";
                echo $customer_internal_id."\n".$searchSO_internalId."\n";

                $so = new SalesOrder();
                $so -> internalId = $searchSO_internalId;

                $request_update_so = new UpdateRequest();
                $request_update_so->record = $so;
                echo "Sales Order :". "\n"; print_r($so);
                $updateResponse_update_so = $service_net->update($request_update_so);
                if (!$updateResponse_update_so->writeResponse->status->isSuccess) {
                    echo "UPDATE ERROR". " \n";
                    print_r($updateResponse_update_so);              
                    exit();
                } else {
                    echo "UPDATE SUCCESS"."\n"."Sales Order ID : " . $updateResponse_update_so->writeResponse->baseRef->internalId . " \n";  
                }
            } else 
            {
                echo "Create Sales Order\n";    
                $so = new SalesOrder();
                $so->entity = new RecordRef();
                if ( $customer_internal_id ){
                    $so->entity->internalId =$customer_internal_id;  //
                }                                            

                $so->tranDate = new DateTime(); 
                $so->tranDate = $orderArr[order][paidTime];

                $soos = new SalesOrderOrderStatus();
                $so->orderStatus = '_pendingFulfillment';

                $so->memo = 'PayPal # ';                          

                $so->shipMethod = new RecordRef();
                $so->shipMethod->internalId = 50233;
                $so->shipMethod->name = isset($orderArr[order][shippingDetails][shippingService]) ? $orderArr[order][shippingDetails][shippingService] : 'Standard Shipping';   
                $so->paymentMethod->internalId = 7; 
                $so -> toBeEmailed = FALSE;

                $so->itemList = new SalesOrderItemList();  

                foreach($orderArr[order][itemArray] as $item_array )
                {
                    if ( isset($item_array[item][sku]) && !empty($item_array[item][sku]) ){
                        $soi = new SalesOrderItem();
                        $soi->item = new RecordRef();
                        if ( $item_array[item][sku]) {   
                            $soi->item->internalId = $item_array[item][sku];                                    
                        }                            
                        $soi->quantity = $item_array[item][quantity];    
                        $soi->price = -1;

                        $soi->amount = $item_array[item][quantity]*$item_array[item][price];

                        $so->itemList->item[] = $soi;      
                        echo "Sales Order Item :". "\n"; print_r($soi);
                    }else{
                        $failed_order_number++;
                        echo "<br>---------------------UNKNOWN ITEM FOUND. PLEASE CHECK IT.-------------------</br>";
                        echo "<br>Bonanza Order ID : " . $orderArr[order][orderID];                                    
                        $so->memo = "test order  *****" . $failed_order_number . "ITEM FAILED***** PLEASE CHECK";
                        $to      = 'pts.eowen@gmail.com';
                        $subject = 'FAILED ORDER Order ID: # ' .$orderArr[order][orderID];
                        $message = 'Found a order has problem, so fail to import.<br> please fix it. <br> Bonanza order id:' . $orderArr[order][orderID];
                        $headers = 'From: pts.eowen@gmail.com' . "\r\n" .
                        'Reply-To: pts.eowen@gmail.com' . "\r\n" .
                        'X-Mailer: PHP/' . phpversion();

                        mail($to, $subject, $message, $headers);                                                
                    }                                                      
                }
                $astringField1 = new StringCustomFieldRef();
                $astringField1->value = 'Bonanza';
                $astringField1->scriptId = 'custbodystorefront';
                $astringField1->internalId = 226;
                $astringField2 = new LongCustomFieldRef();
                $astringField2->value = $orderArr[order][orderID];
                $astringField2->scriptId = 'custbodystorefrontorder';                                    
                $astringField2->internalId = 227;                                    

                $so->customFieldList->customField = array($astringField1,$astringField2); //,$astringField3,$astringField4,$astringField5);                                     

                $request_add_so = new AddRequest();
                $request_add_so->record = $so;
                echo "Sales Order :". "\n"; print_r($so);

				$addResponse_add_so = $service_net->add($request_add_so);

                if (!$addResponse_add_so->writeResponse->status->isSuccess) {
                    echo "ADD ERROR". " \n";
                    print_r($addResponse_add_so);              
                    exit();
                } else {
                    echo "ADD SUCCESS"."\n"."New Sales Order ID : " . $addResponse_add_so->writeResponse->baseRef->internalId . " \n";
                    print_r($addResponse_add_so);              
                }                
            }

        }
        echo "---SUCCESS---\n";
    }

    echo '</pre>';

    echo "<body/><html/>";    
?>