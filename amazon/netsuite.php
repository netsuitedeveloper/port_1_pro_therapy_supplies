<?php

///////////////////////////////////////////////////////////////////////////////////////////////////

require_once 'netsuite-lib.php';

///////////////////////////////////////////////////////////////////////////////////////////////////

class NetSuite 
{
    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public $service;
    public $config;

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct($config) { 
        $this->config = $config;
        $this->service = new NetsuiteServiceClient($config, array(
            'no_retry' => NetsuiteConfig::RETRY === false,
        ));
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function exportInventoryItems($path = null, $options = null) { 
        if (is_null($path)) { 
            $path = __DIR__ . '/amazon-items.csv';
        }
        require_value($path, 'export path');

        //$temp_path = __DIR__ . '/log.dat';
        //$response = unserialize(file_get_contents($temp_path));

        $response = $this->service->getInventoryItems(array(
            array('field' => 'custom', 'operator' => 'notEmpty', 'type' => 'string', 'internalId' => 'custitemamazonsku'),
            array('field' => 'custom', 'operator' => 'is', 'type' => 'boolean', 'searchValue' => 'true', 'internalId' => $this->config['custom_fields']['fba']),
            //array('field' => 'string', 'operator' => 'is', 'type' => 'string', 'searchValue' => '375'),
        ));
        //file_put_contents($temp_path, serialize($response));

        Log::data($response);

        $total_pages = get_and_require($response, 'totalPages');
        $page_index = get_and_require($response, 'pageIndex');
        $search_id = get_and_require($response, 'searchId');

        while(true) { 
            try { 
                $items = array();
                foreach(get_or_array($response, 'recordList', 'record') as $inventory_item) { 
                    $internal_id = get_or_null($inventory_item, 'internalId');
                    foreach(get_or_array($inventory_item, 'customFieldList', 'customField') as $custom_field) { 
                        if (get_or_null($custom_field, 'internalId') === 'custitemamazonsku') { 
                            $amazon_sku = get_or_null($custom_field, 'value');
                            break;
                        }
                    }
                    if ($amazon_sku == false || $internal_id == false) { 
                        Log::warning('Can not export inventory item: no amazon sku or no internal id for inventory item ', $inventory_item);
                    } else { 
                        $items[] = array(
                            'internal_id' => $internal_id,
                            'amazon_sku' => $amazon_sku,
                        );
                    }
                }

                //print_n($items);

                if ($items) { 
                    $csv = array(
                        'header' => array(
                            'internal_id',
                            'amazon_sku',
                        ),
                        'rows' => $items,
                    );
                    if ($page_index === 1 && get_or_null($options, 'append') == false) { 
                        CSV::write($path, $csv);
                    } else { 
                        CSV::append($path, $csv);
                    }
                }
            } catch(\Exception $ex) { 
                Log::ex($ex, 'exportInventoryItems');
            }

            $page_index++;

            //print_n($page_index);

            if ($page_index <= $total_pages) { 
                try { 
                    $response = $this->service->searchMoreForId($search_id, $page_index);
                } catch(\Exception $ex) { 
                    Log::ex($ex, 'exportInventoryItems');
                }
            } else { 
                break;
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function exportInventoryItemsByAmazonSku($amazon_skus, $path = null) { 
        if (!$amazon_skus) { 
            return;
        } else if (is_null($path)) { 
            $path = __DIR__ . '/amazon-items.csv';
        }
        require_value($path, 'export path');

        $items = null;

        foreach(array_wrap($amazon_skus) as $amazon_sku) { 
            try { 
                $response = $this->service->getInventoryItems(array(
                    array('field' => 'custom', 'operator' => 'is', 'type' => 'string', 'internalId' => 'custitemamazonsku', 'searchValue' => $amazon_sku),
                    array('field' => 'custom', 'operator' => 'is', 'type' => 'boolean', 'searchValue' => 'true', 'internalId' => $this->config['custom_fields']['fba']),
                ));

                Log::data($response);

                foreach(get_or_array($response, 'recordList', 'record') as $inventory_item) { 
                    $internal_id = get_or_null($inventory_item, 'internalId');
                    foreach(get_or_array($inventory_item, 'customFieldList', 'customField') as $custom_field) { 
                        if (get_or_null($custom_field, 'internalId') === 'custitemamazonsku') { 
                            $amazon_sku = get_or_null($custom_field, 'value');
                            break;
                        }
                    }
                    if ($amazon_sku == false || $internal_id == false) { 
                        Log::warning('Can not export inventory item: no amazon sku or no internal id for inventory item ', $amazon_sku);
                    } else { 
                        $items[] = array(
                            'internal_id' => $internal_id,
                            'amazon_sku' => $amazon_sku,
                        );
                    }
                    break;
                }

            } catch(\Exception $ex) { 
                Log::ex($ex, 'exportInventoryItems');
            }
        }

        if ($items) { 
            $csv = array(
                'header' => array(
                    'internal_id',
                    'amazon_sku',
                ),
                'rows' => $items,
            );
            CSV::append($path, $csv);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function updateInventoryItems($data) { 
        if (!$data) { 
            return;
        }
        $use_internal_ids = $this->config['use_internal_ids'];
        if (is_array($use_internal_ids)) { 
            Log::warning('================================================');
            Log::warning('Skipping internal ids for inventory item update!');
            Log::warning('================================================');
        }

        $data_groups = array_split($data, 50);

        foreach($data_groups as $data_group) { 
            try {
                $records = array();
                foreach($data_group as $datum) { 
                    foreach(array('InventoryItem', 'KitItem') as $inventory_type) { 
                        $record = new $inventory_type();
                        $record->internalId = get_or_null($datum, 'internal_id');

                        if (is_array($use_internal_ids) && in_array($record->internalId, $use_internal_ids) == false) { 
                            continue;
                        }

                        $custom_fields = array();

                        if (isset($datum['fba_30_days_sold'])) { 
                            $custom_field = new StringCustomFieldRef();
                            $custom_field->internalId = $this->config['custom_fields']['fba_30_days_sold'];
                            $custom_field->value = get_or_null($datum, 'fba_30_days_sold');
                            $custom_fields[] = $custom_field;
                        }

                        if (isset($datum['fba_qty'])) { 
                            $custom_field = new StringCustomFieldRef();
                            $custom_field->internalId = $this->config['custom_fields']['fba_qty'];
                            $custom_field->value = get_or_null($datum, 'fba_qty');
                            $custom_fields[] = $custom_field;
                        }

                        if ($custom_fields) { 
                            $record->customFieldList = new CustomFieldList();
                            $record->customFieldList->customField = $custom_fields;
                        }

                        $records[] = $record;
                    }
                }

                if ($records) { 
                    $this->service->updateList($records, $internal_ids_with_errors);

                    if ($internal_ids_with_errors) { 
                        Log::error($internal_ids_with_errors);
                    }
                }
            } catch(Exception $ex) { 
                Log::ex($ex, 'updateInventoryItems');
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

}

///////////////////////////////////////////////////////////////////////////////////////////////////

class NetsuiteServiceClient
{
    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private $_service;

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public $options;

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct($config, $options) { 
        $this->options = $options;
        $wsdl = 'file://' . str_replace('\\', '/', PATH::combine(__DIR__, 'netsuite.wsdl.xml'));
        $this->_service = new NetSuiteService($config, $wsdl);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function searchMoreForId($internalSearchId, $page, $retry = 0) {
        try { 
            $request = new SearchMoreWithIdRequest();
            $request->searchId = $internalSearchId;
            $request->pageIndex = $page;

            $this->_log($request);

            $response = $this->_service->searchMoreWithId($request);

            $this->_log($response);

            if (!$response->searchResult->status->isSuccess) {
                throw new CustomException('Unable to search saved search');
            }
            return $response->searchResult;
        } catch(\Exception $ex) {
            Log::ex($ex, 'update');

            if (get_or_null($this->options, 'no_retry') == false) { 
                if ($retry < 5) {
                    sleep(10);
                    return self::searchMoreForId($internalSearchId, $page, $retry+1);
                } else {
                    throw $ex;
                }
            } else { 
                throw $ex;
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function searchForId($searchId, &$internalSearchId, &$nrOfPages) {
        $search = new ItemSearchAdvanced();
        //$search->savedSearchScriptId = $searchId; 
        $search->savedSearchId = $searchId; 

        $request = new SearchRequest();
        $request->searchRecord = $search;

        $this->_log($request);

        $response = $this->_service->search($request);

        $this->_log($response);

        if (!$response->searchResult->status->isSuccess) {
            throw new CustomException('Unable to search saved search');
        }
        $internalSearchId = $response->searchResult->searchId;
        $nrOfPages = $response->searchResult->totalPages;

        return $response;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function getSavedSearch($searchId) {
        $record = new GetSavedSearchRecord();
        //$record->searchType = 

        $search = new CustomRecordSearchAdvanced();
        $search->savedSearchId = "63";

        $request = new SearchRequest();
        $request->searchRecord = $search;

        $searchResponse = $service->search($request);
        $request = new GetSavedSearchRequest();
        $request->record = $record;

        $this->_log($request);

        $response = $this->_service->getSavedSearch($request);

        $this->_log($response);

        if (!$response->getSavedSearchResult->status->isSuccess) {
            throw new CustomException('Unable to search saved search');
        }
        return $response;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function updateList($records, & $internal_ids_with_errors, $retry = 0) {
        try {
            $request = new UpdateListRequest();
            $request->record = $records;

            $this->_log($request);

            $response = $this->_service->updateList($request);

            $this->_log($response);

            $internal_ids_with_errors = null;
            if ($response->writeResponseList->writeResponse) {
                foreach($response->writeResponseList->writeResponse as $write_response) { 
                    if (get_or_null($write_response, 'status', 'isSuccess') != 1) { 
                        $status_detail = get_or_null($write_response, 'status', 'statusDetail', 0, 'code');
                        if ($status_detail === 'SSS_RECORD_TYPE_MISMATCH') { 
                            continue;
                        }
                        $internal_ids_with_errors[] = get_or_null($write_response, 'baseRef', 'internalId');
                    }
                }
            }
            return $response;
        } catch(\Exception $ex) {
            Log::ex($ex, 'updateList');

            if (get_or_null($this->options, 'no_retry') == false) { 
                if ($retry < 5) {
                    sleep(10);
                    $internal_ids_with_errors = null;
                    return self::updateList($records, $internal_ids_with_errors, $retry+1);
                } else {
                    throw $ex;
                }
            } else { 
                throw $ex;
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function update($record, $retry = 0) {
        try {
            $request = new UpdateRequest();
            $request->record = $record;

            $this->_log($request);

            $response = $this->_service->update($request);

            $this->_log($response);

            if (!$response->writeResponse->status->isSuccess) {
                throw new CustomException('Unable to update: ', $response->writeResponse->status->statusDetail);
            }
            return $response;
        } catch(\Exception $ex) {
            Log::ex($ex, 'update');

            if (get_or_null($this->options, 'no_retry') == false) { 
                if ($retry < 5) {
                    sleep(10);
                    return self::update($record, $retry+1);
                } else {
                    throw $ex;
                }
            } else { 
                throw $ex;
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function get($type, $internalId, $retry = 0) {
        try {
            $request = new GetRequest();
            $request->baseRef = new RecordRef();
            $request->baseRef->internalId = $internalId;
            $request->baseRef->type = $type;

            $this->_log(array('type' => $type, 'internalId' => $internalId));

            $response = $this->_service->get($request);

            $this->_log($response);

            if (!$response->readResponse->status->isSuccess) {
                throw new CustomException('Unable to get: ', $response->readResponse->status->statusDetail);
            }
            return $response;
        } catch(\Exception $ex) {
            Log::ex($ex, 'get');

            if (get_or_null($this->options, 'no_retry') == false) { 
                if ($retry < 5) {
                    sleep(10);
                    return self::get($type, $internalId, $retry+1);
                } else {
                    throw $ex;
                }
            } else { 
                throw $ex;
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function add($record, $retry = 0) {
        try {
            $request = new AddRequest();
            $request->record = $record;

            $this->_log($request);

            $response = $this->_service->add($request);

            $this->_log($response);

            if (!$response->writeResponse->status->isSuccess) {
                throw new CustomException('Unable to add record: ', $response->writeResponse->status->statusDetail);
            }

            return $response->writeResponse->baseRef->internalId;
        } catch(\Exception $ex) {
            Log::ex($ex, 'add');

            if (get_or_null($this->options, 'no_retry') == false) { 
                if ($retry < 5) {
                    sleep(10);
                    return self::add($record, $retry+1);
                } else {
                    throw $ex;
                }
            } else { 
                throw $ex;
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function getInventoryItems($query, $retry = 0) {
        try {
            $search = new ItemSearch();
            $search->basic = new ItemSearchBasic();

            $this->_getCustomFields($search->basic, $query);

            $request = new SearchRequest();
            $request->searchRecord = $search;

            $this->_log($request);

            //$this->_service->setSearchPreferences(false, 102); 
            //$this->_service->setSearchPreferences(false, 1000); 
            $this->_service->setSearchPreferences(false, 10);
            $response = $this->_service->search($request);

            $this->_log($response);

            if (!$response->searchResult->status->isSuccess) {
                throw new CustomException('Unable to search: ', $response->searchResult->status->statusDetail); //?
            }
            return $response->searchResult;

        } catch(\Exception $ex) {
            Log::ex($ex, 'search');

            if (get_or_null($this->options, 'no_retry') == false) { 
                if ($retry < 5) {
                    sleep(10);
                    return $this->getInventoryItems($query, $retry+1);
                } else {
                    throw $ex;
                }
            } else { 
                throw $ex;
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private function _getCustomFields($search, $query) { 
        $searchCustomFields = new SearchCustomFieldList();

        $values = array();
        $customValues = array();

        foreach($query as $q) {
            $field = APP::get($q, 'field');
            $type = APP::get($q, 'type');
            $operator = APP::get($q, 'operator');
            $value = APP::get($q, 'searchValue');
            $internalId = APP::get($q, 'internalId');

            if ($field == 'custom') {
                if ($type == 'string') {
                    $searchValue = new SearchStringCustomField();
                } else if ($type == 'boolean') {
                    $searchValue = new SearchBooleanCustomField();
                } else if ($type == 'multiselect') {
                    $searchValue = new SearchMultiSelectCustomField();
                    $value = APP::toArray($value);
                    $values = array();
                    foreach($value as $v) {
                        $searchValue_ = new ListOrRecordRef();
                        $searchValue_->name = $v;
                        $values[] = $searchValue_;
                    }
                    $value = $values;
                } else {
                    throw new CustomException('Unsupported search type');
                }
                if ($value !== null) {
                    $searchValue->searchValue = $value;
                }
                if ($internalId !== null) {
                    $searchValue->internalId = $internalId;
                }
                if ($operator !== null) {
                    $searchValue->operator = $operator;
                }
                $customValues[] = $searchValue;
            } else {
                if ($type == 'string') {
                    $searchField = new SearchStringField();
                    $searchField->operator = $operator;
                    $searchField->searchValue = $value;

                    $search->$field = $searchField;
                } else if ($type == 'boolean') {
                    $searchField = new SearchBooleanField();
                    $searchField->searchValue = $value;

                    $search->$field = $searchField;
                } else if ($type == 'enummultiselect') {
                    $searchField = new SearchEnumMultiSelectField();
                    $searchField->operator = $operator;
                    $searchField->searchValue = APP::toArray($value);
                    $search->$field = $searchField;
                } else if ($type == 'multiselect') {
                    $searchField = new SearchMultiSelectField();
                    $searchField->operator = $operator;
                    $value = APP::toArray($value);
                    $values = array();
                    foreach($value as $v) {
                        $searchValue = new RecordRef();
                        $searchValue->name = $v;
                        //$searchValue->type = 'string';
                        $values[] = $searchValue;
                    }
                    $searchField->searchValue = $values;
                    $search->$field = $searchField;
                } else if (isset($search, $type)) { 
                    $searchField = new SearchMultiSelectField();
                    $searchField->operator = $operator;
                    $value = APP::toArray($value);
                    $values = array();
                    foreach($value as $v) {
                        $searchValue = new RecordRef();
                        $searchValue->internalId = $v;
                        //$searchValue->type = 'string';
                        $values[] = $searchValue;
                    }
                    $searchField->searchValue = $values;
                    $search->$type = $searchField;
                } else { 
                    throw new CustomException('Unsupported type');
                }
            }
        }

        if ($customValues) {
            $searchCustomFields->customField = $customValues;
            $search->customFieldList = $searchCustomFields;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    public function search($recordName, $recordType, $query, $retry = 0) {
        try {
            if (!$recordName) {
                throw new CustomException('No record name');
            }
            //if (!$query) {
            //throw new CustomException('No query');
            //}
            if (!is_array($query)) {
                throw new CustomException('Query must be an array');
            }

            $search = new $recordName();
            $searchCustomFields = new SearchCustomFieldList();

            $values = array();
            $customValues = array();

            foreach($query as $q) {
                $field = APP::get($q, 'field');
                $type = APP::get($q, 'type');
                $operator = APP::get($q, 'operator');
                $value = APP::get($q, 'searchValue');
                $internalId = APP::get($q, 'internalId');

                if ($field == 'custom') {
                    if ($type == 'string') {
                        $searchValue = new SearchStringCustomField();
                    } else if ($type == 'boolean') {
                        $searchValue = new SearchBooleanCustomField();
                    } else if ($type == 'multiselect') {
                        $searchField = new SearchMultiSelectField();
                        $searchField->operator = $operator;
                        $value = APP::toArray($value);
                        $values = array();
                        foreach($value as $v) {
                            $searchValue = new RecordRef();
                            $searchValue->name = $v;
                            //$searchValue->type = 'string';
                            $values[] = $searchValue;
                        }
                        $searchField->searchValue = $values;
                        $search->$field = $searchField;
                    } else {
                        throw new CustomException('Unsupported search type');
                    }
                    if ($value !== null) {
                        $searchValue->searchValue = $value;
                    }
                    if ($internalId !== null) {
                        $searchValue->internalId = $internalId;
                    }
                    if ($operator !== null) {
                        $searchValue->operator = $operator;
                    }
                    $customValues[] = $searchValue;
                } else {
                    if ($type == 'string') {
                        $searchField = new SearchStringField();
                        $searchField->operator = $operator;
                        $searchField->searchValue = $value;

                        $search->$field = $searchField;
                    } else if ($type == 'boolean') {
                        $searchField = new SearchBooleanField();
                        $searchField->searchValue = $value;

                        $search->$field = $searchField;
                    } else if ($type == 'enummultiselect') {
                        $searchField = new SearchEnumMultiSelectField();
                        $searchField->operator = $operator;
                        $searchField->searchValue = APP::toArray($value);
                        $search->$field = $searchField;
                    } else if ($type == 'multiselect') {
                        $searchField = new SearchMultiSelectField();
                        $searchField->operator = $operator;
                        $value = APP::toArray($value);
                        $values = array();
                        foreach($value as $v) {
                            $searchValue = new RecordRef();
                            $searchValue->name = $v;
                            //$searchValue->type = 'string';
                            $values[] = $searchValue;
                        }
                        $searchField->searchValue = $values;
                        $search->$field = $searchField;
                    } else {
                        throw new CustomException('Unsupported type');
                    }
                }
            }

            if ($customValues) {
                $searchCustomFields->customField = $customValues;
                $search->customFieldList = $searchCustomFields;
            }

            if ($recordType) {
                $recType = new SearchStringField();
                $recType->searchValue = $recordType;
                $recType->operator = 'is';
                $search->recordType = $recType;
            }

            $request = new SearchRequest();
            $request->searchRecord = $search;

            $this->_log($request);

            $this->_service->setSearchPreferences(false, 102); 
            $response = $this->_service->search($request);

            $this->_log($response);

            if (!$response->searchResult->status->isSuccess) {
                throw new CustomException('Unable to search: ', $response->searchResult->status->statusDetail); //?
            }
            return $response->searchResult;

        } catch(\Exception $ex) {
            Log::ex($ex, 'search');

            if (get_or_null($this->options, 'no_retry') == false) { 
                if ($retry < 5) {
                    sleep(10);
                    return $this->search($recordName, $recordType, $query, $retry+1);
                } else {
                    throw $ex;
                }
            } else { 
                throw $ex;
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

    private function _log($data) { 
        if (Log::getLogLevel() >= Log::LEVEL_DATA) { 
            $data = APP::objectToArray($data);
            array_walk_recursive_ex($data, function($value, $key, & $array) { 
                if (is_null($value) || is_empty_string($value)) { 
                    unset($array[$key]);
                }
            });
            Log::data($data);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////

}

///////////////////////////////////////////////////////////////////////////////////////////////////

