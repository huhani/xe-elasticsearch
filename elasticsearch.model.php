<?php
/*! Copyright (C) 201 BGM STORAGE. All rights reserved. */
/**
 * @class  elasticSearchModel
 * @author Huhani (mmia268@gmail.com)
 * @brief  elasticsearch module model class.
 */

use Elasticsearch\ClientBuilder;


class ElasticSearchClientConnector {

    private $host = null;
    private $port = null;
    private $prefix = "";
    private $client = null;

    function __construct($host, $port, $prefix = "")
    {
        $this->host = $host;
        $this->port = $port;
        $this->prefix = $prefix;
        $this->client = $client = ClientBuilder::create()
            ->setHosts(array($host.":".$port))
            ->build();
    }

    public function getClient() {
        return $this->client;
    }

    public function getPrefix() {
        return $this->prefix;
    }

}

class elasticsearchModel extends elasticsearch
{

    private static $host = "127.0.0.1";
    private static $port = 9200;
    private static $prefix = "es";
    private static $client = null;

    function init()
    {
    }

    public static function getElasticEngineClient() {
        if(!self::$client) {
            self::$client = new ElasticSearchClientConnector(self::$host, self::$port, self::$prefix);
        }

        return self::$client->getClient();
    }

    public static function getElasticEnginePrefix() {
        return self::$prefix;
    }

    function getDocumentListFromSearchResponse($result, $page, $list_count, $page_count, $isExtraVars, $columnList) {
        if(!$result) {
            return null;
        }

        $hits = $result['hits'];
        $hitsData = $hits['hits'];
        $total_count = $hits['total']['value'];
        $total_page = max(1, ceil($total_count / $list_count));
        $data = array();
        $last_id = $total_count - (($page-1) * $list_count);
        $documentSrls = array();

        if(isset($result['aggregations']) && isset($result['aggregations']['group_by_document_srl'])) {
            $total_count = $result['aggregations']['document_count']['value'];
            $total_page = max(1, ceil($total_count / $list_count));
            $last_id = $total_count - (($page-1) * $list_count);

            $groupByResult = $result['aggregations']['group_by_document_srl'];
            $bucket = $groupByResult['buckets'];
            foreach($bucket as $each) {
                $documentSrls[] = $each['key'];
            }
        } else {
            foreach($hitsData as $each) {
                if($each['fields'] && isset($each['fields']['document_srl']) && $each['fields']['document_srl']) {
                    $documentSrls[] = $each['fields']['document_srl'][0];
                }
            }
        }

        $aDocument = $this->getDocuments($documentSrls, $isExtraVars, $columnList);
        if($last_id > count($aDocument)) {
            $last_id -= abs(count($documentSrls) - count($aDocument));

        }
        foreach($documentSrls as $eachDocument_srl) {
            if(isset($aDocument[$eachDocument_srl]) && $aDocument[$eachDocument_srl]) {
                $data[$last_id--] = $aDocument[$eachDocument_srl];
            }
        }

        $page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);

        $output = new BaseObject();
        $output->total_count = $total_count;
        $output->total_page = $total_page;
        $output->page = $page;
        $output->data = $data;
        $output->page_navigation = $page_navigation;

        return $output;
    }

    function getIndexDocumentSearchCount($obj) {
        $oElasticsearchController = getController('elasticsearch');
        $client = self::getElasticEngineClient();
        $prefix = self::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $module_srl = isset($obj->module_srl) && $obj->module_srl ? explode(',', $obj->module_srl) : null;
        $exclude_module_srl = isset($obj->exclude_module_srl) && $obj->exclude_module_srl ? explode(',', $obj->exclude_module_srl) : null;
        $category_srl = isset($obj->category_srl) ? $obj->category_srl : 0;
        $search_target = $obj->search_target;
        $search_keyword = $obj->search_keyword;
        $_searchTarget = $search_target;
        $varIdx = -1;
        if(strpos($_searchTarget, "extra_vars") !== false) {
            $str = explode("extra_vars", $_searchTarget);
            $varIdx = (int)$str[1];
            if(!$varIdx) {
                return null;
            }
            $_searchTarget = "extra_vars";
        }

        $params = null;
        switch ($_searchTarget) {
            case "title_content" :
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["bool" => [
                                        "should" => [
                                            ["match_phrase" => ["title.my_ngram" => $search_keyword]],
                                            ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                        ],
                                        "minimum_should_match" => 1
                                    ]]
                                ]
                            ]
                        ]
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }

                try {
                    $result = $client->count($params);
                    return $result['count'];
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('count', $params, $e);
                }

                return false;

            case "title":
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["title.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                try {
                    $result = $client->count($params);
                    return $result['count'];
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('count', $params, $e);
                }

                return false;

            case "content":
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ]
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                try {
                    $result = $client->count($params);
                    return $result['count'];
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('count', $params, $e);
                }

                return false;

            case "comment":
                $params = [
                    'index' => $prefix.'comments',
                    'body' => [
                        "size" => 0,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                        'aggs' => [
                            "document_count" => ["cardinality" => ["field"=>"document_srl"]]
                        ],
                    ]
                ];
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["doc_category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
                }

                try {
                    $result = $client->search($params);
                    return $result['aggregations']['document_count']['value'];
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $params, $e);
                }

                return false;


            case "extra_vars":
                $params = [
                    'index' => $prefix.'document_extra_vars',
                    'body' => [
                        'query' => [
                            "bool" => [
                                "should" => [
                                    ["match_phrase" => ["value.my_ngram" => $search_keyword]],
                                    ["match" => ["value" => $search_keyword]]
                                ],
                                "filter" => [
                                    ["terms" => ["module_srl" => $module_srl]],
                                    ["term" => ["var_idx" => $varIdx]]
                                ],
                                "minimum_should_match" => 1
                            ]
                        ]
                    ]
                ];
                if($category_srl) {
                    $params['body']['query']['bool']['filter'][] = ["match" => ["doc_category_srl" => $category_srl]];
                }

                try {
                    $result = $client->count($params);
                    return $result['count'];
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('count', $params, $e);
                }

                return false;
        }

        return 0;
    }

    function getIndexDocumentApproximatedOffset($obj, $total_count = -1) {
        $oElasticsearchController = getController('elasticsearch');
        $compression = 50;
        if($total_count === -1) {
            $total_count = $this->getIndexDocumentSearchCount($obj);
        }
        if(!$total_count) {
            return null;
        }
        $client = self::getElasticEngineClient();
        $prefix = self::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $module_srl = isset($obj->module_srl) && $obj->module_srl ? explode(',', $obj->module_srl) : null;
        $exclude_module_srl = isset($obj->exclude_module_srl) && $obj->exclude_module_srl ? explode(',', $obj->exclude_module_srl) : null;
        $page = $obj->page;
        if(!$page || $page < 1) {
            $page = 1;
        }
        $list_count = $obj->list_count;
        $category_srl = isset($obj->category_srl) ? $obj->category_srl : 0;
        $sort_index = isset($obj->sort_index) ? $obj->sort_index : "regdate";
        $order_type = (!isset($obj->order_type) && $sort_index === "list_order") || $obj->order_type === "asc" ? "asc" : "desc";
        $search_target = $obj->search_target;
        $search_keyword = $obj->search_keyword;
        $fromPage = max(0, $page-1);
        $from = $fromPage * $list_count;
        $percent = $from / $total_count * 100;
        if($order_type === "desc") {
            $percent = 100 - $percent;
        }
        $_searchTarget = $search_target;
        $varIdx = -1;
        if(strpos($_searchTarget, "extra_vars") !== false) {
            $str = explode("extra_vars", $_searchTarget);
            $varIdx = (int)$str[1];
            if(!$varIdx) {
                return null;
            }
            $_searchTarget = "extra_vars";
        }

        switch ($_searchTarget) {
            case "title_content" :
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        "size" => 0,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["bool" => [
                                        "should" => [
                                            ["match_phrase" => ["title.my_ngram" => $search_keyword]],
                                            ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                        ],
                                        "minimum_should_match" => 1
                                    ]]
                                ]
                            ]
                        ],
                        "_source" => false,
                        "aggs" => [
                            'percentile' => [
                                'percentiles' => [
                                    "field" => $sort_index,
                                    "percents" => $percent,
                                    "tdigest" => ["compression" => $compression]
                                ]
                            ]
                        ]

                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                try {
                    $result = $client->search($params);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $params, $e);
                    return false;
                }

                $aggregations = $result['aggregations'];
                $percentile = $aggregations['percentile'];
                $approximatedOffset = end($percentile['values']);

                return $approximatedOffset;

            case "title":
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        "size" => 0,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["title.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                        "_source" => false,
                        "aggs" => [
                            'percentile' => [
                                'percentiles' => [
                                    "field" => $sort_index,
                                    "percents" => $percent,
                                    "tdigest" => ["compression" => $compression]
                                ]
                            ]
                        ]

                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                try {
                    $result = $client->search($params);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $params, $e);
                    return false;
                }
                $aggregations = $result['aggregations'];
                $percentile = $aggregations['percentile'];
                $approximatedOffset = end($percentile['values']);

                return $approximatedOffset;

            case "content":
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        "size" => 0,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                        "_source" => false,
                        "aggs" => [
                            'percentile' => [
                                'percentiles' => [
                                    "field" => $sort_index,
                                    "percents" => $percent,
                                    "tdigest" => ["compression" => $compression]
                                ]
                            ]
                        ]

                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                try {
                    $result = $client->search($params);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $params, $e);
                    return false;
                }
                $aggregations = $result['aggregations'];
                $percentile = $aggregations['percentile'];
                $approximatedOffset = end($percentile['values']);

                return $approximatedOffset;

            case "extra_vars":
                $params = [
                    'index' => $prefix.'document_extra_vars',
                    'body' => [
                        "size" => 0,
                        'query' => [
                            "bool" => [
                                "should" => [
                                    ["match_phrase" => ["value.my_ngram" => $search_keyword]],
                                    ["match" => ["value" => $search_keyword]]
                                ],
                                "filter" => [
                                    ["terms" => ["module_srl" => $module_srl]],
                                    ["term" => ["var_idx" => $varIdx]]
                                ],
                                "minimum_should_match" => 1
                            ]
                        ],
                        "aggs" => [
                            'percentile' => [
                                'percentiles' => [
                                    "field" => "doc_".$sort_index,
                                    "percents" => $percent,
                                    "tdigest" => ["compression" => $compression]
                                ]
                            ]
                        ]

                    ]
                ];
                if($category_srl) {
                    $params['body']['query']['bool']['filter'][] = ["match" => ["doc_category_srl" => $category_srl]];
                }
                try {
                    $result = $client->search($params);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $params, $e);
                    return false;
                }

                $aggregations = $result['aggregations'];
                $percentile = $aggregations['percentile'];
                $approximatedOffset = end($percentile['values']);

                return $approximatedOffset;
        }

        return null;
    }

    function getIndexAfterOffset($obj, $total_count = 0) {
        $oElasticsearchController = getController('elasticsearch');
        $chunkSize = 10000;
        if($total_count === -1) {
            $total_count = $this->getIndexDocumentSearchCount($obj);
        }
        $client = self::getElasticEngineClient();
        $prefix = self::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $module_srl = isset($obj->module_srl) && $obj->module_srl ? explode(',', $obj->module_srl) : null;
        $exclude_module_srl = isset($obj->exclude_module_srl) && $obj->exclude_module_srl ? explode(',', $obj->exclude_module_srl) : null;
        $page = $obj->page;
        $list_count = $obj->list_count;
        $category_srl = isset($obj->category_srl) ? $obj->category_srl : 0;
        $sort_index = isset($obj->sort_index) ? $obj->sort_index : "regdate";
        $order_type = (!isset($obj->order_type) && $sort_index === "list_order") || $obj->order_type === "asc" ? "asc" : "desc";
        $search_target = $obj->search_target;
        $search_keyword = $obj->search_keyword;
        $fromPage = max(0, $page-1);
        $from = $fromPage * $list_count;


        $_searchTarget = $search_target;
        $varIdx = -1;
        if(strpos($_searchTarget, "extra_vars") !== false) {
            $str = explode("extra_vars", $_searchTarget);
            $varIdx = (int)$str[1];
            if(!$varIdx) {
                return null;
            }
            $_searchTarget = "extra_vars";
        }

        $approximatedOffset = $this->getIndexDocumentApproximatedOffset($obj, $total_count);
        if($approximatedOffset === false) {
            return false;
        }
        switch ($_searchTarget) {
            case "title_content" :
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["bool" => [
                                        "should" => [
                                            ["match_phrase" => ["title.my_ngram" => $search_keyword]],
                                            ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                        ],
                                        "minimum_should_match" => 1
                                    ]]
                                ]
                            ]
                        ]
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                $filter[] = ["range" => [$sort_index => [
                    ($order_type === "asc" ? "lte" : "gte") => $approximatedOffset
                ]]];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }

                try {
                    $result = $client->count($params);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('count', $params, $e);
                    return false;
                }

                $count = $result['count'];
                $diff = (int)($from-$count);
                if($diff === 0) {
                    return $approximatedOffset;
                }

                $params2 = [
                    'index' => $prefix.'documents',
                    'size' => abs($diff) + ($diff < 0 ? 1 : 0),
                    'body' => [
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["bool" => [
                                        "should" => [
                                            ["match_phrase" => ["title.my_ngram" => $search_keyword]],
                                            ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                        ]
                                    ]]
                                ]
                            ]
                        ],
                        "fields" => [$sort_index],
                        "_source" => false
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params2['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($diff > 0) {
                    if($order_type === "desc") {
                        $filter[] = ["range" => [$sort_index => [
                             "lte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "desc"
                        ];
                    } else {
                        $filter[] = ["range" => [$sort_index => [
                            "gte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "asc"
                        ];
                    }
                } else {
                    if($order_type === "desc") {
                        $filter[] = ["range" => [$sort_index => [
                            "gte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "asc"
                        ];
                    } else {
                        $filter[] = ["range" => [$sort_index => [
                            "lte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "desc"
                        ];
                    }
                }
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params2['body']['query']['bool']['filter']) && isset($params2['body']['query']['bool']['filter']['bool'])) {
                        $params2['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params2['body']['query']['bool']['filter'] = $filter;
                    }
                }

                try {
                    $result = $client->search($params2);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $params2, $e);
                    return false;
                }
                $hits = $result['hits'];
                $hitsData = $hits['hits'];
                $last = end($hitsData);

                return end($last['fields'][$sort_index]);

            case "title":
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["title.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ]
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                $filter[] = ["range" => [$sort_index => [
                    ($order_type === "asc" ? "lte" : "gte") => $approximatedOffset
                ]]];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                try {
                    $result = $client->count($params);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('count', $params, $e);
                    return false;
                }
                $count = $result['count'];
                $diff = (int)($from-$count);
                if($diff === 0) {
                    return $approximatedOffset;
                }

                $params2 = [
                    'index' => $prefix.'documents',
                    'size' => abs($diff) + ($diff < 0 ? 1 : 0),
                    'body' => [
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["title.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                        "fields" => [$sort_index],
                        "_source" => false
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params2['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($diff > 0) {
                    if($order_type === "desc") {
                        $filter[] = ["range" => [$sort_index => [
                            "lte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "desc"
                        ];
                    } else {
                        $filter[] = ["range" => [$sort_index => [
                            "gte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "asc"
                        ];
                    }
                } else {
                    if($order_type === "desc") {
                        $filter[] = ["range" => [$sort_index => [
                            "gte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "asc"
                        ];
                    } else {
                        $filter[] = ["range" => [$sort_index => [
                            "lte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "desc"
                        ];
                    }
                }

                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params2['body']['query']['bool']['filter']) && isset($params2['body']['query']['bool']['filter']['bool'])) {
                        $params2['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params2['body']['query']['bool']['filter'] = $filter;
                    }
                }
                try {
                    $result = $client->search($params2);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $params2, $e);
                    return false;
                }
                $hits = $result['hits'];
                $hitsData = $hits['hits'];
                $last = end($hitsData);

                return end($last['fields'][$sort_index]);

            case "content":
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ]
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                $filter[] = ["range" => [$sort_index => [
                    ($order_type === "asc" ? "lte" : "gte") => $approximatedOffset
                ]]];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                $result = $client->count($params);
                $count = $result['count'];
                $diff = (int)($from-$count);
                if($diff === 0) {
                    return $approximatedOffset;
                }

                $params2 = [
                    'index' => $prefix.'documents',
                    'size' => abs($diff) + ($diff < 0 ? 1 : 0),
                    'body' => [
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                        "fields" => [$sort_index],
                        "_source" => false
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params2['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($diff > 0) {
                    if($order_type === "desc") {
                        $filter[] = ["range" => [$sort_index => [
                            "lte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "desc"
                        ];
                    } else {
                        $filter[] = ["range" => [$sort_index => [
                            "gte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "asc"
                        ];
                    }
                } else {
                    if($order_type === "desc") {
                        $filter[] = ["range" => [$sort_index => [
                            "gte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "asc"
                        ];
                    } else {
                        $filter[] = ["range" => [$sort_index => [
                            "lte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            $sort_index => "desc"
                        ];
                    }
                }

                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params2['body']['query']['bool']['filter']) && isset($params2['body']['query']['bool']['filter']['bool'])) {
                        $params2['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params2['body']['query']['bool']['filter'] = $filter;
                    }
                }
                try {
                    $result = $client->search($params2);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $params2, $e);
                    return false;
                }
                $hits = $result['hits'];
                $hitsData = $hits['hits'];
                $last = end($hitsData);

                return end($last['fields'][$sort_index]);

            case "extra_vars":
                $params = [
                    'index' => $prefix.'document_extra_vars',
                    'body' => [
                        'query' => [
                            "bool" => [
                                "should" => [
                                    ["match_phrase" => ["value.my_ngram" => $search_keyword]],
                                    ["match" => ["value" => $search_keyword]]
                                ],
                                "filter" => [
                                    ["terms" => ["module_srl" => $module_srl]],
                                    ["term" => ["var_idx" => $varIdx]]
                                ],
                                "minimum_should_match" => 1
                            ]
                        ]
                    ]
                ];
                $filter = [];
                $filter[] = ["range" => ["doc_".$sort_index => [
                    ($order_type === "asc" ? "lte" : "gte") => $approximatedOffset
                ]]];
                if($category_srl) {
                    $params['body']['query']['bool']['filter'][] = ["match" => ["doc_category_srl" => $category_srl]];
                }
                try {
                    $result = $client->count($params);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('count', $params, $e);
                    return false;
                }

                $count = $result['count'];
                $diff = (int)($from-$count);
                if($diff === 0) {
                    return $approximatedOffset;
                }

                $params2 = [
                    'index' => $prefix.'document_extra_vars',
                    'size' => abs($diff) + ($diff < 0 ? 1 : 0),
                    'body' => [
                        'query' => [
                            "bool" => [
                                "should" => [
                                    ["match_phrase" => ["value.my_ngram" => $search_keyword]],
                                    ["match" => ["value" => $search_keyword]]
                                ],
                                "filter" => [
                                    ["terms" => ["module_srl" => $module_srl]],
                                    ["term" => ["var_idx" => $varIdx]]
                                ],
                                "minimum_should_match" => 1
                            ]
                        ],
                        "fields" => ["doc_".$sort_index],
                        "_source" => false
                    ]
                ];
                $filter = [];
                if($diff > 0) {
                    if($order_type === "desc") {
                        $filter[] = ["range" => ["doc_".$sort_index => [
                            "lte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            "doc_".$sort_index => "desc"
                        ];
                    } else {
                        $filter[] = ["range" => ["doc_".$sort_index => [
                            "gte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            "doc_".$sort_index => "asc"
                        ];
                    }
                } else {
                    if($order_type === "desc") {
                        $filter[] = ["range" => ["doc_".$sort_index => [
                            "gte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            "doc_".$sort_index => "asc"
                        ];
                    } else {
                        $filter[] = ["range" => ["doc_".$sort_index => [
                            "lte" => $approximatedOffset
                        ]]];
                        $params2['body']['sort'] = [
                            "doc_".$sort_index => "desc"
                        ];
                    }
                }

                if($category_srl) {
                    $params2['body']['query']['bool']['filter'][] = ["match" => ["doc_category_srl" => $category_srl]];
                }
                try {
                    $result = $client->search($params2);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $params2, $e);
                    return false;
                }
                $hits = $result['hits'];
                $hitsData = $hits['hits'];
                $last = end($hitsData);

                return end($last['fields'][$sort_index]);

        }

        return null;
    }

    function getDocumentFromSearchFromSearchAfter($obj, $columnList) {
        $chunkSize = 10000;
        $oElasticsearchController = getController('elasticsearch');
        $total_count = $this->getIndexDocumentSearchCount($obj);

        $prefix = self::getElasticEnginePrefix();
        $client = self::getElasticEngineClient();
        $isExtraVars = $obj->isExtraVars;
        $module_srl = isset($obj->module_srl) && $obj->module_srl ? explode(',', $obj->module_srl) : null;
        $exclude_module_srl = isset($obj->exclude_module_srl) && $obj->exclude_module_srl ? explode(',', $obj->exclude_module_srl) : null;
        $page = $obj->page;
        $list_count = $obj->list_count;
        $page_count = $obj->page_count;
        $category_srl = isset($obj->category_srl) ? $obj->category_srl : 0;
        $sort_index = isset($obj->sort_index) ? $obj->sort_index : "regdate";
        $order_type = (!isset($obj->order_type) && $sort_index === "list_order") || $obj->order_type === "asc" ? "asc" : "desc";
        $search_target = $obj->search_target;
        $search_keyword = $obj->search_keyword;
        $fromPage = max(0, $page-1);
        $endPage = $fromPage + 1;
        $from = $fromPage * $list_count;
        $end = $endPage * $list_count;
        $search_after = null;
        if($prefix) {
            $prefix .= "_";
        }
        if(!$page) {
            $page = 1;
        }

        $_searchTarget = $search_target;
        $varIdx = -1;
        if(strpos($_searchTarget, "extra_vars") !== false) {
            $str = explode("extra_vars", $_searchTarget);
            $varIdx = (int)$str[1];
            if(!$varIdx) {
                return null;
            }
            $_searchTarget = "extra_vars";
        }

        $_result = null;
        $moveOffset = floor($from / $chunkSize) * $chunkSize;
        $leftOffset = $moveOffset;
        $afterFromOffset = $from - $moveOffset;
        $afterSizeOffset = $end - $moveOffset;
        switch ($_searchTarget) {
            case "title_content" :
                $search_after = $page > 1 ? $this->getIndexAfterOffset($obj, $total_count) : null;
                $_params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        "size" => $list_count,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["bool" => [
                                        "should" => [
                                            ["match_phrase" => ["title.my_ngram" => $search_keyword]],
                                            ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                        ]
                                    ]]
                                ]
                            ]
                        ],
                        "fields" => ["document_srl"],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => false,
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $_params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if($search_after) {
                    $filter[] = ["range" =>
                        [$sort_index =>
                            [($order_type === "asc" ? "gt" : "lt") => $search_after]
                        ]
                    ];
                }
                if(count($filter) > 0) {
                    if(isset($_params['body']['query']['bool']['filter']) && isset($_params['body']['query']['bool']['filter']['bool'])) {
                        $_params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $_params['body']['query']['bool']['filter'] = $filter;
                    }
                }

                try {
                    $_result = $client->search($_params);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $_params, $e);
                    $_result = null;
                }

                break;

            case "title":
                $search_after = $page > 1 ? $this->getIndexAfterOffset($obj, $total_count) : null;
                $_params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        "size" => $list_count,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["title.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                        "fields" => ["document_srl"],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => false,
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $_params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if($search_after) {
                    $filter[] = ["range" =>
                        [$sort_index =>
                            [($order_type === "asc" ? "gt" : "lt") => $search_after]
                        ]
                    ];
                }
                if(count($filter) > 0) {
                    if(isset($_params['body']['query']['bool']['filter']) && isset($_params['body']['query']['bool']['filter']['bool'])) {
                        $_params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $_params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                try {
                    $_result = $client->search($_params);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $_params, $e);
                    $_result = null;
                }
                break;

            case "content":
                $search_after = $page > 1 ? $this->getIndexAfterOffset($obj, $total_count) : null;
                $_params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        "size" => $list_count,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                        "fields" => ["document_srl"],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => false,
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $_params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if($search_after) {
                    $filter[] = ["range" =>
                        [$sort_index =>
                            [($order_type === "asc" ? "gt" : "lt") => $search_after]
                        ]
                    ];
                }
                if(count($filter) > 0) {
                    if(isset($_params['body']['query']['bool']['filter']) && isset($_params['body']['query']['bool']['filter']['bool'])) {
                        $_params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $_params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                try {
                    $_result = $client->search($_params);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $_params, $e);
                    $_result = null;
                }
                break;

            case "comment":
                $afterRegdate = null;
                $afterListOrder = null;
                while(true) {
                    $params = [
                        'index' => $prefix.'comments',
                        'body' => [
                            "size" => 0,
                            'query' => [
                                "bool" => [
                                    "must" => [
                                        ["match_phrase" => ["content.my_ngram" => $search_keyword]],
                                        ["exists" => ["field" => "doc_".$sort_index]]
                                    ]
                                ]
                            ],
                            "_source" => false,
                            'aggs' => [
                                'group_by_document_srl' => [
                                    "terms" => [
                                        "field" => "document_srl",
                                        "size" => $leftOffset > 0 ? $chunkSize : $afterSizeOffset,
                                        "order" => ["doc_".$sort_index => $order_type]
                                    ],
                                    'aggs' => [
                                        'doc_regdate' => ["min" => ["field" => "doc_regdate"]],
                                        'doc_list_order' => ["min" => ["field" => "doc_list_order"]]
                                    ]
                                ]
                            ],
                        ]
                    ];
                    $filter = [];
                    if($category_srl) {
                        $filter[] = ["match" => ["doc_category_srl" => $category_srl]];
                    }
                    if($module_srl) {
                        $filter[] = ["terms" => ["module_srl" => $module_srl]];
                        $filter[] = ["match" => ["doc_status" => "PUBLIC"]];
                    }
                    if(count($filter) > 0) {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                    if($afterRegdate || $afterListOrder) {
                        $params['body']['query']['bool']['must'][] = ["range" =>
                            ["doc_".$sort_index =>
                                [($order_type === "asc" ? "gt" : "lt") => $afterRegdate ? $afterRegdate : $afterRegdate]
                            ]
                        ];
                    }

                    try {
                        $result = $client->search($params);
                        $aggregations = $result['aggregations'];
                        $group_by_document_srl = $aggregations['group_by_document_srl'];
                        $buckets = $group_by_document_srl['buckets'];
                        if($leftOffset > 0) {
                            $leftOffset -= $chunkSize;
                            $last = end($buckets);
                            if("doc_".$sort_index === "doc_regdate") {
                                $afterRegdate = $last['doc_regdate']['value_as_string'];
                            } else {
                                $afterListOrder = $last['doc_list_order']['value'];
                            }

                            continue;
                        }

                        $_result = $result;
                    } catch(Exception $e) {
                        $oElasticsearchController->insertErrorLog('search', $params, $e);
                        $_result = null;
                    }

                    break;
                }
                break;

            case "extra_vars":
                $search_after = $page > 1 ? $this->getIndexAfterOffset($obj, $total_count) : null;
                $_params = [
                    'index' => $prefix.'document_extra_vars',
                    'body' => [
                        "size" => $list_count,
                        'query' => [
                            "bool" => [
                                "should" => [
                                    ["match_phrase" => ["value.my_ngram" => $search_keyword]],
                                    ["match" => ["value" => $search_keyword]]
                                ],
                                "filter" => [
                                    ["term" => ["module_srl" => $module_srl]],
                                    ["term" => ["var_idx" => $varIdx]]
                                ],
                                "minimum_should_match" => 1
                            ]
                        ],
                        "fields" => ["document_srl"],
                        'sort' => [
                            "doc_".$sort_index => $order_type
                        ],
                        "_source" => false,
                    ]
                ];
                if($category_srl) {
                    $_params['body']['query']['bool']['filter'][] = ["match" => ["doc_category_srl" => $category_srl]];
                }
                if($search_after) {
                    $filter[] = ["range" =>
                        [$sort_index =>
                            [($order_type === "asc" ? "gt" : "lt") => $search_after]
                        ]
                    ];
                }
                if(count($filter) > 0) {
                    $_params['body']['query']['bool']['filter'] = $filter;
                }
                try {
                    $_result = $client->search($_params);
                } catch(Exception $e) {
                    $oElasticsearchController->insertErrorLog('search', $_params, $e);
                    $_result = null;
                }

                break;

            default:
                return null;
        }

        $documentSrls = array();
        $data = array();
        $last_id = $total_count - (($page-1) * $list_count);
        if($_result !== null) {
            if(isset($_result['aggregations']) && isset($_result['aggregations']['group_by_document_srl'])) {
                $groupByResult = $_result['aggregations']['group_by_document_srl'];
                $bucket = $groupByResult['buckets'];
                $bucketCount = count($bucket);
                for($i=$afterFromOffset; $i<$bucketCount; $i++) {
                    $each = $bucket[$i];
                    $documentSrls[] = $each['key'];
                }
            } else {
                $hits = $_result['hits'];
                $hitsData = $hits['hits'];
                $hitsDataCount = count($hitsData);
                for($i=0; $i<$hitsDataCount; $i++) {
                    $each = $hitsData[$i];
                    $documentSrls[] = $each['fields']['document_srl'][0];
                }
            }
        }

        $aDocument = $this->getDocuments($documentSrls, $isExtraVars, $columnList);
        foreach($documentSrls as $eachDocument_srl) {
            if(isset($aDocument[$eachDocument_srl]) && $aDocument[$eachDocument_srl]) {
                $data[$last_id--] = $aDocument[$eachDocument_srl];
            }
        }

        $total_page = max(1, ceil($total_count / $list_count));
        $page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);

        $output = new BaseObject();
        $output->total_count = $total_count;
        $output->total_page = $total_page;
        $output->page = $page;
        $output->data = $data;
        $output->page_navigation = $page_navigation;

        return $output;
    }

    function getDocumentFromSearch($obj, $columnList) {
        $config = $this->getModuleConfig();
        if($config->use_search_after === "Y") {
            return $this->getDocumentFromSearchFromSearchAfter($obj, $columnList);
        }
        $prefix = self::getElasticEnginePrefix();
        $isExtraVars = $obj->isExtraVars;
        $module_srl = isset($obj->module_srl) && $obj->module_srl ? explode(',', $obj->module_srl) : null;
        $exclude_module_srl = isset($obj->exclude_module_srl) && $obj->exclude_module_srl ? explode(',', $obj->exclude_module_srl) : null;

        $page = $obj->page;
        $list_count = $obj->list_count;
        $page_count = $obj->page_count;
        $category_srl = isset($obj->category_srl) ? $obj->category_srl : 0;
        $sort_index = isset($obj->sort_index) ? $obj->sort_index : "regdate";
        $order_type = (!isset($obj->order_type) && $sort_index === "list_order") || $obj->order_type === "asc" ? "asc" : "desc";
        $search_target = $obj->search_target;
        $search_keyword = $obj->search_keyword;
        $params = null;
        if($prefix) {
            $prefix .= "_";
        }
        if(!$page) {
            $page = 1;
        }

        $newColumnList = array();
        foreach($columnList as $each) {
            $newColumnList[] = explode(".", $each)[1];
        }

        $_searchTarget = $search_target;
        $varIdx = -1;
        if(strpos($_searchTarget, "extra_vars") !== false) {
            $str = explode("extra_vars", $_searchTarget);
            $varIdx = (int)$str[1];
            if(!$varIdx) {
                return null;
            }
            $_searchTarget = "extra_vars";
        }

        switch ($_searchTarget) {
            case "title_content" :
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        "from" => max($page-1, 0) * $list_count,
                        "size" => $list_count,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["bool" => [
                                        "should" => [
                                            ["match_phrase" => ["title.my_ngram" => $search_keyword]],
                                            ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                        ],
                                        "minimum_should_match" => 1
                                    ]]
                                ]
                            ]
                        ],
                        "fields" => ["document_srl"],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => false,
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                break;

            case "title":
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        "from" => max($page-1, 0) * $list_count,
                        "size" => $list_count,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["title.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                        "fields" => ["document_srl"],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => false,
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                break;

            case "content":
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        "from" => max($page-1, 0) * $list_count,
                        "size" => $list_count,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                        "fields" => ["document_srl"],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => false,
                    ]
                ];
                if($exclude_module_srl && count($exclude_module_srl) > 0) {
                    $params['body']['query']['bool']['filter'] = [
                        "bool" => [
                            "must_not" => [
                                "terms" => [
                                    "module_srl" => $exclude_module_srl
                                ]
                            ]
                        ]
                    ];
                }
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    if(isset($params['body']['query']['bool']['filter']) && isset($params['body']['query']['bool']['filter']['bool'])) {
                        $params['body']['query']['bool']['filter']['bool']['must'] = $filter;
                    } else {
                        $params['body']['query']['bool']['filter'] = $filter;
                    }
                }
                break;

            case "comment":
                $params = [
                    'index' => $prefix.'comments',
                    'body' => [
                        "size" => 0,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                        "fields" => ["document_srl", "doc_".$sort_index],
                        "_source" => false,
                        'aggs' => [
                            'group_by_document_srl' => [
                                "terms" => [
                                    "field" => "document_srl",
                                    "size" => 500000,
                                    //"order" => ["doc_".$sort_index => $order_type]
                                ],
                                'aggs' => [
                                    'doc_regdate' => ["min" => ["field" => "doc_regdate"]],
                                    'doc_list_order' => ["min" => ["field" => "doc_list_order"]],
                                    'document_sort' => [
                                        "bucket_sort" => [
                                            'sort' => [
                                                in_array($sort_index, array("list_order", "regdate")) ? "doc_".$sort_index : "_key" => $order_type
                                            ],
                                            "size" => $list_count,
                                            "from" => max($page-1, 0) * $list_count,
                                        ]
                                    ]
                                ]
                            ],
                            "document_count" => ["cardinality" => ["field"=>"document_srl"]]
                        ],
                    ]
                ];
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["doc_category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["terms" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["doc_status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
                }
                break;

            case "extra_vars":
                $params = [
                    'index' => $prefix.'document_extra_vars',
                    'body' => [
                        "from" => max($page-1, 0) * $list_count,
                        "size" => $list_count,
                        'query' => [
                            "bool" => [
                                "should" => [
                                    ["match_phrase" => ["value.my_ngram" => $search_keyword]],
                                    ["match" => ["value" => $search_keyword]]
                                ],
                                "filter" => [
                                    'bool' => [
                                        ["terms" => ["module_srl" => [$module_srl]]],
                                        ["term" => ["var_idx" => $varIdx]]
                                    ]
                                ],
                                "minimum_should_match" => 1
                            ]
                        ],
                        "fields" => ["document_srl"],
                        'sort' => [
                            "doc_".$sort_index => $order_type
                        ],
                        "_source" => false,
                    ]
                ];
                if($category_srl) {
                    $params['body']['query']['bool']['filter'][] = ["match" => ["doc_category_srl" => $category_srl]];
                }
                break;

        }

        if($params) {
            $client = self::getElasticEngineClient();
            $oElasticsearchController = getController('elasticsearch');
            try {
                $response = $client->search($params);
            } catch(Exception $e) {
                $oElasticsearchController->insertErrorLog('search', $params, $e);
                return null;
            }
            
            $output = $this->getDocumentListFromSearchResponse($response, $page, $list_count, $page_count, $isExtraVars, $columnList);

            return $output;
        }

        return null;
    }

    function getDocuments($documentSrls = array(), $isExtraVars, $columnList) {
        $aDocument = array();
        if(count($documentSrls) > 0) {
            $args = new stdClass();
            $args->document_srls = implode(',',$documentSrls);
            $args->list_count = count($documentSrls);
            $args->order_type = 'asc';
            $documentOutput = executeQueryArray('document.getDocuments', $args, $columnList);
            if($documentOutput->toBool()) {
                foreach($documentOutput->data as $eachDoc) {
                    $aDocument[$eachDoc->document_srl] = $eachDoc;
                }
            }
        }

        return $aDocument;
    }

    function getDocumentList($obj, $columnList = array()) {
        if(!$obj->search_target) {
            return null;
        }
        if(!in_array($obj->sort_index, array("regdate", "list_order"))) {
            $obj->sort_index = "regdate";
            $obj->list_order = "desc";
        }

        return $this->getDocumentFromSearch($obj, $columnList);
    }

    function getIndexList() {
        $client = self::getElasticEngineClient();
        try {
            $indices = $client->cat()->indices(array('index' => '*'));

            return $indices;
        } catch(Exception $e) {
            return null;
        }

        return array();
    }

    function hasIndices($indexNameArray = array()) {
        $list = $this->getIndexList();
        if($list === null) {
            return array_fill(0, count($indexNameArray), false);
        }
        $hasIndices = array();
        foreach($indexNameArray as $each) {
            $hasIndices[] = array_search($each, array_column($list, 'index')) !== false;
        }

        return $hasIndices;
    }

    function getIndexDocument($indexName, $id) {
        $client = self::getElasticEngineClient();
        $params = [
            'index' => $indexName,
            'id' => $id,
            '_source' => true
        ];

        $response = $client->get($params);

        return $response;
    }

    function getLastDocumentSrl() {
        $args = new stdClass();
        $args->sort_index = "document_srl";
        $args->order_type = "desc";
        $output = executeQuery('elasticsearch.getLastDocumentSrl', $args);

        return $output->toBool() && $output->data ? $output->data->document_srl : 0;
    }

    function getLastCommentSrl() {
        $args = new stdClass();
        $args->sort_index = "comment_srl";
        $args->order_type = "desc";
        $output = executeQuery('elasticsearch.getLastCommentSrl', $args);

        return $output->toBool() && $output->data ? $output->data->comment_srl : 0;
    }

    function getLastFileSrl() {
        $args = new stdClass();
        $args->sort_index = "file_srl";
        $args->order_type = "desc";
        $output = executeQuery('elasticsearch.getLastFileSrl', $args);

        return $output->toBool() && $output->data ? $output->data->file_srl : 0;
    }

    function getModuleDefaultConfig() {
        $config = new stdClass();
        $config->use_alternate_search = "Y";
        $config->use_search_after = "N";
        $config->skin = "default";
        $config->search_module_target = "include";
        $config->search_target_module_srl = "";

        return $config;
    }

    function getModuleConfig() {
        $oModuleModel = getModel('module');
        $config = $oModuleModel->getModuleConfig('elasticsearch');
        if(!$config) {
            $config = $this->getModuleDefaultConfig();
        }

        return $config;
    }

    function getIntegrationSearchCount($params) {
        $oElasticsearchController = getController('elasticsearch');
        $client = self::getElasticEngineClient();
        $newParams = [
            'index'=> $params['index'],
            'body' => [
                'query' => $params['body']['query']
            ]
        ];

        try {
            $result = $client->count($newParams);

            return $result['count'];
        } catch(Exception $e) {
            $oElasticsearchController->insertErrorLog('count', $params, $e);
        }
    }

    function getIntegrationSearchApproximatedOffset($obj, $params, $total_count = -1) {
        $oElasticsearchController = getController('elasticsearch');
        $client = self::getElasticEngineClient();
        $compression = 50;
        if($total_count === -1) {
            $total_count = $this->getIntegrationSearchCount($obj, $params);
        }
        $page = $obj->page;
        if(!$page || $page < 1) {
            $page = 1;
        }
        $list_count = $obj->list_count;
        $fromPage = max(0, $page-1);
        $from = $fromPage * $list_count;
        $percent = $total_count > 0 ? $from / $total_count * 100 : 0;

        $sort_index = isset($obj->sort_index) ? $obj->sort_index : "regdate";
        $order_type = (!isset($obj->order_type) && $sort_index === "list_order") || $obj->order_type === "asc" ? "asc" : "desc";
        if($order_type === "desc") {
            $percent = 100 - $percent;
        }

        $params1 = [
            'index' => $params['index'],
            'body' => [
                "size" => 0,
                'query' => $params['body']['query'],
                "_source" => false,
                "aggs" => [
                    'percentile' => [
                        'percentiles' => [
                            "field" => $sort_index,
                            "percents" => $percent,
                            "tdigest" => ["compression" => $compression]
                        ]
                    ]
                ]

            ]
        ];

        try {
            $result = $client->search($params1);
        } catch(Exception $e) {
            $oElasticsearchController->insertErrorLog('search', $params1, $e);
            return false;
        }
        $aggregations = $result['aggregations'];
        $percentile = $aggregations['percentile'];
        $approximatedOffset = end($percentile['values']);
        return $approximatedOffset;

    }

    function getIntegrationSearchAfterOffset($obj, $params, $total_count = -1) {
        $oElasticsearchController = getController('elasticsearch');
        $chunkSize = 10000;
        if($total_count === -1) {
            $total_count = $this->getIntegrationSearchCount($params);
        }
        $client = self::getElasticEngineClient();
        $page = $obj->page;
        $list_count = $obj->list_count;
        $sort_index = isset($obj->sort_index) ? $obj->sort_index : "regdate";
        $order_type = (!isset($obj->order_type) && $sort_index === "list_order") || $obj->order_type === "asc" ? "asc" : "desc";
        $fromPage = max(0, $page-1);
        $from = $fromPage * $list_count;
        $approximatedOffset = $this->getIntegrationSearchApproximatedOffset($obj, $params, $total_count);

        if($approximatedOffset === false) {
            return false;
        }
        //$approximatedOffset = "20210703020609";
        //$approximatedOffset = "20210703002630";

        $params2 = [
            'index' => $params['index'],
            'body' => [
                'query' => $params['body']['query']
            ]
        ];
        $params2['body']['query']['bool']['filter']['bool']['must'][] = ["range" => [$sort_index => [
            ($order_type === "asc" ? "lte" : "gte") => $approximatedOffset
        ]]];

        try {
            $result = $client->count($params2);
        } catch(Exception $e) {
            $oElasticsearchController->insertErrorLog('count', $params2, $e);
            return false;
        }

        $count = $result['count'];
        $diff = (int)($from-$count);
        if($diff === 0) {
            return $approximatedOffset;
        }

        $params3 = [
            'index' => $params['index'],
            'body' => [
                'size' => abs($diff) + ($diff < 0 ? 1 : 0),
                'query' => $params['body']['query'],
                "fields" => [$sort_index],
                "_source" => false
            ]
        ];

        if($diff > 0) {
            if($order_type === "desc") {
                $params3['body']['query']['bool']['filter']['bool']['must'][] = ["range" => [$sort_index => [
                    "lte" => $approximatedOffset
                ]]];
                $params3['body']['sort'] = [
                    $sort_index => "desc"
                ];
            } else {
                $params3['body']['query']['bool']['filter']['bool']['must'][] = ["range" => [$sort_index => [
                    "gte" => $approximatedOffset
                ]]];
                $params3['body']['sort'] = [
                    $sort_index => "asc"
                ];
            }
        } else {
            if($order_type === "desc") {
                $params3['body']['query']['bool']['filter']['bool']['must'][] = ["range" => [$sort_index => [
                    "gte" => $approximatedOffset
                ]]];
                $params3['body']['sort'] = [
                    $sort_index => "asc"
                ];
            } else {
                $params3['body']['query']['bool']['filter']['bool']['must'][] = ["range" => [$sort_index => [
                    "lte" => $approximatedOffset
                ]]];
                $params3['body']['sort'] = [
                    $sort_index => "desc"
                ];
            }
        }

        try {
            $result = $client->search($params3);
        } catch(Exception $e) {
            $oElasticsearchController->insertErrorLog('search', $params3, $e);
            return false;
        }

        $hits = $result['hits'];
        $hitsData = $hits['hits'];
        $last = end($hitsData);

        return end($last['fields'][$sort_index]);
    }

    function getIntegrationSearchDataFromSearchAfter($obj, $params) {
        $client = self::getElasticEngineClient();
        $oElasticsearchController = getController('elasticsearch');
        $total_count = $this->getIntegrationSearchCount($params);
        $page = $obj->page;
        $list_count = $obj->list_count;
        $page_count = $obj->page_count;
        $sort_index = isset($obj->sort_index) ? $obj->sort_index : "regdate";
        $order_type = (!isset($obj->order_type) && $sort_index === "list_order") || $obj->order_type === "asc" ? "asc" : "desc";
        $search_after = null;
        if(!$page) {
            $page = 1;
        }

        $search_after = $page > 1 ? $this->getIntegrationSearchAfterOffset($obj, $params, $total_count) : null;
        $_params = [
            'index' => $params['index'],
            'body' => [
                "size" => $list_count,
                'query' => $params['body']['query'],
                'sort' => $params['body']['sort'],
                "_source" => false,
            ]
        ];
        if(isset($params['body']['fields']) && count($params['body']['fields'])) {
            $_params['body']['fields'] = $params['body']['fields'];
        }
        if($search_after) {
            $_params['body']['query']['bool']['filter']['bool']['must'][] = ["range" =>
                [$sort_index =>
                    [($order_type === "asc" ? "gt" : "lt") => $search_after]
                ]
            ];
        }
        try {
            $response = $client->search($_params);
            $hits = $response['hits'];
            $hitsData = $hits['hits'];
            $total_page = max(1, ceil($total_count / $list_count));
            $ids = array();
            $data = array();
            foreach($hitsData as $each) {
                $id = (int)$each['_id'];
                $ids[] = $id;
                $data[$id] = $each['fields'];
            }

            $page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
            $output = new BaseObject();
            $output->total_count = $total_count;
            $output->total_page = $total_page;
            $output->page = $page;
            $output->ids = $ids;
            $output->data = $data;
            $output->page_navigation = $page_navigation;

            return $output;
        } catch(Exception $e) {
            $oElasticsearchController->insertErrorLog('search', $_params, $e);
            $_result = null;
        }

    }

    function getIntegrationSearchData($obj, array $columnList = array()) {
        $config = $this->getModuleConfig();
        $prefix = self::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $type = $obj->type;
        $module_target = $obj->module_target;
        $module_srls = $obj->module_srls;
        $page = $obj->page;
        $list_count = $obj->list_count;
        $page_count = $obj->page_count;
        $sort_index = isset($obj->sort_index) ? $obj->sort_index : "regdate";
        $order_type = (!isset($obj->order_type) && $sort_index === "list_order") || $obj->order_type === "asc" ? "asc" : "desc";
        $search_target = $obj->search_target;
        $search_keyword = $obj->search_keyword;
        $params = null;
        switch($type) {
            case "documents":
                $params = [
                    'index' => $prefix.'documents',
                    'body' => [
                        "from" => max($page-1, 0) * $list_count,
                        "size" => $list_count,
                        'query' => [
                            "bool" => [
                                "must" => [],
                                "filter" => [
                                    "bool" => [
                                        "must" => [
                                            ["match" => ["status" => "PUBLIC"]]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => false
                    ]
                ];
                if(count($columnList)) {
                    $params['body']['fields'] = $columnList;
                }
                if($module_target === "include") {
                    $params['body']['query']['bool']['filter']['bool']['must'][] = [
                        "terms" => [
                            "module_srl" => $module_srls
                        ],
                    ];
                } else {
                    $params['body']['query']['bool']['filter']['bool']['must_not'] = [
                        "terms" => [
                            "module_srl" => $module_srls
                        ]
                    ];

                }
                if($search_target === "title_content") {
                    $params['body']['query']['bool']['must'][] = ["bool" => [
                        "should" => [
                            ["match_phrase" => ["title.my_ngram" => $search_keyword]],
                            ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                        ]
                    ]];
                } else if($search_target === "content") {
                    $params['body']['query']['bool']['must'][] = ["match_phrase" => ["content.my_ngram" => $search_keyword]];
                } else if($search_target === "tag") {
                    $params['body']['query']['bool']['must'][] = ["match_phrase" => ["tags.my_ngram" => $search_keyword]];
                } else {
                    $params['body']['query']['bool']['must'][] = ["match_phrase" => ["title.my_ngram" => $search_keyword]];
                }

                break;

            case "comments":
                $params = [
                    'index' => $prefix.'comments',
                    'body' => [
                        "from" => max($page-1, 0) * $list_count,
                        "size" => $list_count,
                        'query' => [
                            "bool" => [
                                "must" => [
                                    ["match_phrase" => ["content.my_ngram" => $search_keyword]]
                                ],
                                "filter" => [
                                    "bool" => [
                                        "must" => [
                                            ["match" => ["is_secret" => "N"]]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => false
                    ]
                ];
                if(count($columnList)) {
                    $params['body']['fields'] = $columnList;
                }
                if($module_target === "include") {
                    $params['body']['query']['bool']['filter']['bool']['must'][] = [
                        "terms" => [
                            "module_srl" => $module_srls
                        ],
                    ];
                } else {
                    $params['body']['query']['bool']['filter']['bool']['must_not'] = [
                        "terms" => [
                            "module_srl" => $module_srls
                        ]
                    ];
                }
                break;

            case "files":
                $direct_download = isset($obj->direct_download) ? $obj->direct_download : "N";
                $params = [
                    'index' => $prefix.'files',
                    'body' => [
                        "from" => max($page-1, 0) * $list_count,
                        "size" => $list_count,
                        'query' => [
                            "bool" => [
                                "should" => [
                                    ["match" => ["source_filename" => $search_keyword]],
                                    ["match" => ["source_filename.my_ngram" => $search_keyword]]
                                ],
                                "filter" => [
                                    "bool" => [
                                        "must" => [
                                            ["match" => ["isvalid" => "Y"]],
                                            ["match" => ["direct_download" => $direct_download]],
                                            ["match" => ["doc_status" => "PUBLIC"]],
                                            ["match" => ["cmt_is_secret" => "N"]]
                                        ]
                                    ]
                                ],
                                "minimum_should_match" => 1
                            ]
                        ],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => false
                    ]
                ];
                if(count($columnList)) {
                    $params['body']['fields'] = $columnList;
                }
                if($module_target === "include") {
                    $params['body']['query']['bool']['filter']['bool']['must'][] = [
                        "terms" => [
                            "module_srl" => $module_srls
                        ],
                    ];
                } else {
                    $params['body']['query']['bool']['filter']['bool']['must_not'] = [
                        "terms" => [
                            "module_srl" => $module_srls
                        ]
                    ];
                }
                break;
        }
        if($params && $config->use_search_after === "Y") {
            return $this->getIntegrationSearchDataFromSearchAfter($obj, $params);
        }

        if($params) {
            $client = self::getElasticEngineClient();
            try {
                $response = $client->search($params);
                $hits = $response['hits'];
                $hitsData = $hits['hits'];
                $total_count = $hits['total']['value'];
                $total_page = max(1, ceil($total_count / $list_count));
                $ids = array();
                $data = array();
                foreach($hitsData as $each) {
                    $id = (int)$each['_id'];
                    $ids[] = $id;
                    $data[$id] = $each['fields'];
                }
                
                $page_navigation = new PageHandler($total_count, $total_page, $page, $page_count);
                $output = new BaseObject();
                $output->total_count = $total_count;
                $output->total_page = $total_page;
                $output->page = $page;
                $output->ids = $ids;
                $output->data = $data;
                $output->page_navigation = $page_navigation;

                return $output;
            } catch(Exception $e) {
                var_dump($e);
                exit();
                //$oElasticsearchController->insertErrorLog('search', $params, $e);
                //return null;
            }

        }

        return null;
    }

    function getIntegrationSearchDocuments($module_target = 'include', array $module_srl_list, $search_target, $is_keyword, $page, $list_count = 10) {
        $oDocumentModel = getModel('document');
        $obj = new stdClass();
        $obj->type = "documents";
        $obj->module_target = $module_target;
        $obj->module_srls = $module_srl_list;
        $obj->search_target = $search_target;
        $obj->search_keyword = $is_keyword;
        $obj->page = $page;
        $obj->page_count = 10;
        $obj->list_count = $list_count;
        $output = $this->getIntegrationSearchData($obj);
        $documents = null;
        if(count($output->data) > 0) {
            $documents = $oDocumentModel->getDocuments($output->ids);
            if(count($documents) > 0) {
                $output->data = $documents;
            }
        }

        return $output;
    }

    function getIntegrationSearchComments($module_target = 'include', array $module_srl_list, $is_keyword, $page, $list_count = 10) {
        $oCommentModel = getModel('comment');
        $obj = new stdClass();
        $obj->type = "comments";
        $obj->module_target = $module_target;
        $obj->module_srls = $module_srl_list;
        $obj->search_target = null;
        $obj->search_keyword = $is_keyword;
        $obj->page = $page;
        $obj->page_count = 10;
        $obj->list_count = $list_count;
        $output = $this->getIntegrationSearchData($obj);
        $comments = null;
        if(count($output->data) > 0) {
            $comments = $oCommentModel->getComments($output->ids);
            if(count($comments) > 0) {
                $output->data = $comments;
            }
        }

        return $output;
    }

    function getIntegrationSearchTrackbacks() {
        // 아무 데이터도 주지 않음.
    }

    function getIntegrationSearchFiles($module_target = 'include', array $module_srl_list, $is_keyword, $page, $list_count = 20, $direct_download = "N") {
        $oFileModel = getModel('file');
        $oDocumentModel = getModel('document');
        $oCommentModel = getModel('comment');
        $obj = new stdClass();
        $obj->type = "files";
        $obj->module_target = $module_target;
        $obj->module_srls = $module_srl_list;
        $obj->search_target = null;
        $obj->search_keyword = $is_keyword;
        $obj->page = $page;
        $obj->direct_download = $direct_download === "Y" ? "Y" : "N";
        $obj->page_count = 10;
        $obj->list_count = $list_count;
        
        $output = $this->getIntegrationSearchData($obj, array('document_srl', 'comment_srl'));
        $ids = $output->ids;
        $outputData = $output->data;
        $document_srls = array();
        $comment_srls = array();
        $list = array();
        if(count($ids) > 0) {
            $args = new stdClass();
            $args->file_srl = implode(",", $ids);
            $args->sort_index = "file_srl";
            $args->order_type = "desc";
            $output2 = executeQueryArray('elasticsearch.getFilesByFileSrl', $args);
            if($output2->toBool()) {
                foreach($output2->data as $key => $val) {
                    $_obj = new stdClass;
                    $_obj->filename = $val->source_filename;
                    $_obj->download_count = $val->download_count;
                    $val->download_url = $_obj->direct_download === "N" ? $oFileModel->getDownloadUrl($val->file_srl, $val->sid, $val->module_srl) : str_replace('./', '', $val->uploaded_filename);
                    if(substr($val->download_url,0,2)=='./') {
                        $val->download_url = substr($val->download_url,2);
                    }
                    $_obj->download_url = Context::getRequestUri().$val->download_url;
                    $_obj->target_srl = $val->upload_target_srl;
                    $_obj->file_size = $val->file_size;
                    if(preg_match('/\.(jpg|jpeg|gif|png)$/i', $val->source_filename))
                    {
                        $_obj->type = 'image';
                        $thumbnail_path = sprintf('files/thumbnails/%s',getNumberingPath($val->file_srl, 3));
                        if(!is_dir($thumbnail_path)) FileHandler::makeDir($thumbnail_path);
                        $thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, 180, 180, 'crop');
                        $thumbnail_url  = Context::getRequestUri().$thumbnail_file;
                        if(!file_exists($thumbnail_file)) FileHandler::createImageFile($val->uploaded_filename, $thumbnail_file, 180, 180, 'jpg', 'crop');
                        $_obj->src = sprintf('<img src="%s" alt="%s" width="%d" height="%d" />', $thumbnail_url, htmlspecialchars($obj->filename, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), 180, 180);
                    }
                    else
                    {
                        $_obj->type = 'binary';
                        $_obj->src = '';
                    }

                    $list[] = $_obj;
                }
            }
        }

        foreach($outputData as $key=>$val) {
            if(isset($val['comment_srl'])) {
                $comment_srls[] = $val['comment_srl'][0];
            } else if(isset($val['document_srl'])) {
                $document_srls[] = $val['document_srl'][0];
            }
        }



        $comment_list = count($comment_srls) > 0 ? $oCommentModel->getComments($comment_srls) : array();
        $document_list = count($document_srls) > 0 ? $oDocumentModel->getDocuments($document_srls): array();
        foreach($list as $key=>&$val) {
            $found = false;
            foreach($comment_list as $_key=>$_val) {
                if($val->target_srl == $_val->comment_srl) {
                    $val->url = $_val->getPermanentUrl();
                    $val->regdate = $_val->getRegdate("Y-m-d H:i");
                    $val->nick_name = $_val->getNickName();
                    $found = true;
                    break;
                }
            }
            if($found){
                continue;
            }
            foreach($document_list as $_key=>$_val) {
                if($val->target_srl == $_val->document_srl) {
                    $val->url = $_val->getPermanentUrl();
                    $val->regdate = $_val->getRegdate("Y-m-d H:i");
                    $val->nick_name = $_val->getNickName();
                    break;
                }
            }
        }
        $output->data = $list;

        return $output;
    }

}
