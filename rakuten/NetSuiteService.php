<?php

class NetSuite_Service
{
    private static $_service;
    
    private static function _init()
    {
        if (!self::$_service) {
            self::$_service = new NetsuiteService();
        }
    }

    public static function searchMoreForId($internalSearchId, $page)
    {
        $request = new SearchMoreWithIdRequest();
        $request->searchId = $internalSearchId;
        $request->pageIndex = $page;

        Log::data($request);

        self::_init();
        $response = self::$_service->searchMoreWithId($request);

        Log::data($response);

        if (!$response->searchResult->status->isSuccess) {
            throw new Exception('Unable to search saved search');
        }
        return $response;
    }

    public static function searchForId($searchId, &$internalSearchId, &$nrOfPages)
    {
        $search = new ItemSearchAdvanced();
        //$search->savedSearchScriptId = $searchId; 
        $search->savedSearchId = $searchId; 

        $request = new SearchRequest();
        $request->searchRecord = $search;

        Log::data($request);

        self::_init();
        $response = self::$_service->search($request);

        Log::data($response);

        if (!$response->searchResult->status->isSuccess) {
            throw new Exception('Unable to search saved search');
        }
        $internalSearchId = $response->searchResult->searchId;
        $nrOfPages = $response->searchResult->totalPages;

        return $response;
    }

    public static function getSavedSearch($searchId)
    {
        $record = new GetSavedSearchRecord();
        //$record->searchType = 

        $search = new CustomRecordSearchAdvanced();
        $search->savedSearchId = "63";

        $request = new SearchRequest();
        $request->searchRecord = $search;

        $searchResponse = $service->search($request);
        $request = new GetSavedSearchRequest();
        $request->record = $record;

        Log::data($request);

        self::_init();
        $response = self::$_service->getSavedSearch($request);

        Log::data($response);

        if (!$response->getSavedSearchResult->status->isSuccess) {
            throw new Exception('Unable to search saved search');
        }
        return $response;
    }

    public static function update($record, $retry = 0)
    {
        try {
            $request = new UpdateRequest();
            $request->record = $record;

            Log::data($request);

            self::_init();
            $response = self::$_service->update($request);

            Log::data($response);

            if (!$response->writeResponse->status->isSuccess) {
                throw new Exception('Unable to get: ', $response->writeResponse->status->statusDetail);
            }
            return $response;
        } catch(Exception $ex) {
            Log::ex($ex, 'update');

            if ($retry < 5) {
                sleep(10);
                return self::update($record, $retry+1);
            } else {
                throw $ex;
            }
        }
    }

    public static function get($type, $internalId, $retry = 0)
    {
        try {
            $request = new GetRequest();
            $request->baseRef = new RecordRef();
            $request->baseRef->internalId = $internalId;
            $request->baseRef->type = $type;

            Log::data(array('type' => $type, 'internalId' => $internalId));

            self::_init();

            $response = self::$_service->get($request);

            Log::data($response);

            if (!$response->readResponse->status->isSuccess) {
                throw new Exception('Unable to get: ', $response->readResponse->status->statusDetail);
            }
            return $response;
        } catch(Exception $ex) {
            Log::ex($ex, 'get');

            if ($retry < 5) {
                sleep(10);
                return self::get($type, $internalId, $retry+1);
            } else {
                throw $ex;
            }
        }
    }

    public static function add($record, $retry = 0) 
    {
        try {
            $request = new AddRequest();
            $request->record = $record;

            Log::data($request);

            self::_init();
            $response = self::$_service->add($request);

            Log::data($response);

            if (!$response->writeResponse->status->isSuccess) {
                throw new Exception('Unable to add record: ' . $response->writeResponse->status->statusDetail);
            }

            return $response->writeResponse->baseRef->internalId;
        } catch(Exception $ex) {
            Log::ex($ex, 'add');

            if ($retry < 5) {
                sleep(10);
                return self::add($record, $retry+1);
            } else {
                throw $ex;
            }
        }
    }

    public static function search($recordName, $recordType, $query, $retry = 0)
    {
        try {
            if (!$recordName) {
                throw new Exception('No record name');
            }
            if (!$query) {
                throw new Exception('No query');
            }
            if (!is_array($query)) {
                throw new Exception('Query must be an array');
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
                    } else {
                        throw new Exception('Unsupported search type');
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
                        throw new Exception('Unsupported type');
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

            Log::data($request);

            self::_init();
            self::$_service->setSearchPreferences(false, 102);
            $response = self::$_service->search($request);

            Log::data($response);

            if (!$response->searchResult->status->isSuccess) {
                throw new Exception('Unable to search');
            }
            return $response->searchResult;

        } catch(Exception $ex) {
            Log::ex($ex, 'search');

            if ($retry < 5) {
                sleep(10);
                return self::search($recordName, $recordType, $query, $retry+1);
            } else {
                throw $ex;
            }
        }

    }

}

