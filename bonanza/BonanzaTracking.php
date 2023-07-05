<?php

    include_once ('PHPToolkit/NetSuiteService.php');

    $service_search_so = new NetSuiteService();
    $service_search_so->setSearchPreferences(false, 80);

    echo '<pre>';     
    $date_ago = date('Y-m-d\TH:i:s', time()-3600*2);
    $date_cur = date('Y-m-d\TH:i:s', time());
    print_r($date_ago); echo '~';
    print_r($date_cur);
    $search_sales_order = new TransactionSearch();                                         
    $search_sales_order->basic->shipDate->operator = 'after';
    $search_sales_order->basic->shipDate->searchValue = $date_ago;
    $extenalorderField = new SearchStringCustomField();
    $extenalorderField->searchValue = 'Bonanza';
    $extenalorderField->internalId = 226;
    $extenalorderField->operator = "hasKeywords";    
    $extenalOrderUrlField = new SearchStringCustomField();

    $search_sales_order->basic->customFieldList->customField = array($extenalorderField); //, $extenalOrderUrlField);

    $request = new SearchRequest();
    $request->searchRecord = $search_sales_order; 
    $searchResponse = $service_search_so->search($request);
    print_r($searchResponse);
    $searchId = $searchResponse->searchResult->searchId;
    $total_pages = $searchResponse->searchResult->totalPages;
    Global $transaction_id;
    Global $tracking_number;
    Global $shipping_info;
    $tracking_number = array();
    $transaction_id = array();
    $shipping_info = array(); 

    $soos = new SalesOrderOrderStatus();    

    for ($num = 0; $num < count($searchResponse->searchResult->recordList->record); $num++) {
        echo $num;

        if(($searchResponse->searchResult->recordList->record[$num]->status == "Pending Billing") 
            || ($searchResponse->searchResult->recordList->record[$num]->status == "Fully Billed")
            || ($searchResponse->searchResult->recordList->record[$num]->status == "Billed")){
            echo "<br>Status : ".$searchResponse->searchResult->recordList->record[$num]->status."<br>";               
            $tracking_number[] = $searchResponse->searchResult->recordList->record[$num]->linkedTrackingNumbers;//custbody8
            $customfields = $searchResponse->searchResult->recordList->record[$num]->customFieldList->customField;
            foreach ($customfields as $customfield){
                if($customfield -> scriptId == "custbodystorefrontorder"){
                    $tran_id = $customfield-> value;
                    $transaction_id[]= $tran_id;
                }
                if($customfield -> scriptId == "custbody8"){
                    $track_num = $customfield->value;
                    if( $track_num != '') $tracking_number[] = $track_num;
                }
            }
            $shipping_info[] = TRUE;
            echo '<br>NetSuite SalesOrder List With Tracking Number<br>Tracking Number: ' . $searchResponse->searchResult->recordList->record[$num]->linkedTrackingNumbers . '<br>Bonanza ORDER ID: ' . $tran_id . "\n";

            //            }
        } else if(($searchResponse->searchResult->recordList->record[$num]->status == "Cancelled") 
        || ($searchResponse->searchResult->recordList->record[$num]->status == "Closed")){
            echo "<br>Status : ".$searchResponse->searchResult->recordList->record[$num]->status."<br>";
            $tracking_number[] = $searchResponse->searchResult->recordList->record[$num]->linkedTrackingNumbers;//custbody8
            $customfields = $searchResponse->searchResult->recordList->record[$num]->customFieldList->customField;
            foreach ($customfields as $customfield){
                if($customfield -> scriptId == "custbodystorefrontorder"){
                    $transaction_id[]= $customfield-> value;
                }
            }
            $shipping_info[] = FALSE;
            echo '<br>NetSuite SalesOrder List With Tracking Number<br>Tracking Number: ' . $searchResponse->searchResult->recordList->record[$num]->linkedTrackingNumbers . '<br>Bonanza ORDER ID: ' . $searchResponse->searchResult->recordList->record[$num]->customFieldList->customField[1]->value;

        } else { echo "<br>This is neither 'Pending Billing / Fully Billed / Billed' nor 'Cancelled / Closed'<br>";}
        echo '-----------------------------------------------------------------------------<br>';
    }

    if ( $total_pages > 1 ){
        echo "<br>Total Pages are more than 1.<br>";
        for ($pages = 1; $pages < $total_pages; $pages++){
            $request = new SearchMoreWithIdRequest();
            $request->pageIndex = $pages + 1;
            $request->searchId = $searchId;
            $searchResponse = $service_search_so->searchMoreWithId($request); 

            $soos = new SalesOrderOrderStatus();    

            for ($num = 0; $num < count($searchResponse->searchResult->recordList->record); $num++) {
                echo $num;

                if(($searchResponse->searchResult->recordList->record[$num]->status == "Pending Billing") 
                    || ($searchResponse->searchResult->recordList->record[$num]->status == "Fully Billed")
                    || ($searchResponse->searchResult->recordList->record[$num]->status == "Billed")){
                    echo "<br>Status : ".$searchResponse->searchResult->recordList->record[$num]->status."<br>";
                    $tracking_number[] = $searchResponse->searchResult->recordList->record[$num]->linkedTrackingNumbers;//custbody8
                    $customfields = $searchResponse->searchResult->recordList->record[$num]->customFieldList->customField;
                    foreach ($customfields as $customfield){
                        if($customfield -> scriptId == "custbodystorefrontorder"){
                            $tran_id = $customfield-> value;
                            $transaction_id[]= $tran_id;
                        }
                        if($customfield -> scriptId == "custbody8"){
                            $track_num = $customfield->value;
                            if( $track_num != '') $tracking_number[] = $track_num;
                        }
                    }
                    $shipping_info[] = TRUE;
                    echo '<br>NetSuite SalesOrder List With Tracking Number<br>Tracking Number: ' . $searchResponse->searchResult->recordList->record[$num]->linkedTrackingNumbers . '<br>Bonanza ORDER ID: ' . $tran_id . "\n";

                    //            }
                } else if(($searchResponse->searchResult->recordList->record[$num]->status == "Cancelled") 
                || ($searchResponse->searchResult->recordList->record[$num]->status == "Closed")){
                    echo "<br>Status : ".$searchResponse->searchResult->recordList->record[$num]->status."<br>";
                    $tracking_number[] = $searchResponse->searchResult->recordList->record[$num]->linkedTrackingNumbers;//custbody8
                    $customfields = $searchResponse->searchResult->recordList->record[$num]->customFieldList->customField;
                    foreach ($customfields as $customfield){
                        if($customfield -> scriptId == "custbodystorefrontorder"){
                            $transaction_id[]= $customfield-> value;
                        }
                    }
                    $shipping_info[] = FALSE;
                    echo '<br>NetSuite SalesOrder List With Tracking Number<br>Tracking Number: ' . $searchResponse->searchResult->recordList->record[$num]->linkedTrackingNumbers . '<br>Bonanza ORDER ID: ' . $searchResponse->searchResult->recordList->record[$num]->customFieldList->customField[1]->value;

                } else { echo "<br>This is neither 'Pending Billing / Fully Billed / Billed' nor 'Cancelled / Closed'<br>";}
                echo '-----------------------------------------------------------------------------<br>';
            }

        }
    }

    if (count($shipping_info)>0) {
        echo "\nTracking Number : "; print_r($tracking_number);
        echo "\nTransaction ID : "; print_r($transaction_id);
        echo "\nShipped Status : "; print_r($shipping_info);

        $num = 0;                         
        while( $num < count($shipping_info) ){

            $dev_name = "tH1ihRYulwyErDx";
            $cert_name = "DxQ1wu7iM9tgXzo";
            $token = 'TmRtVC00VO'; //HDEsMV0Knl TmRtVC00VO

            $url = "https://api.bonanza.com/api_requests/secure_request";

            $headers = array("X-BONANZLE-API-DEV-NAME: " . $dev_name, "X-BONANZLE-API-CERT-NAME: " . $cert_name);

            echo "<br>".$num."<br>";
            print_r($shipping_info[$num]);
            if($shipping_info[$num] == 1){
                $args = array(
                    'transactionID' => $transaction_id[$num],
                    'shipped' => $shipping_info[$num],
                    'requesterCredentials' => array(
                        'bonanzleAuthToken' => $token
                    ),
                    'shipment' => array(
                        'shippingTrackingNumber' => $tracking_number[$num]
                    )
                );
            } else {
                $args = array(
                    'transactionID' => $transaction_id[$num],
                    'shipped' => $shipping_info[$num],
                    'requesterCredentials' => array(
                        'bonanzleAuthToken' => $token
                    )
                );
            }
            print_r($args);
            $post_fields = "completeSaleRequest=" .  urlencode(json_encode($args));
            echo "<pre> Request: $post_fields \n </pre>";

            $connection = curl_init($url);
            $curl_options = array(
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $post_fields,
                CURLOPT_POST => 1,
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

            print_r($response);

            $num++;
        }     

    }

    
?>