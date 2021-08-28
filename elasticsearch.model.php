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
        $client = self::getElasticEngineClient();
        $prefix = self::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $module_srl = $obj->module_srl;
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
                                        ]
                                    ]]
                                ]
                            ]
                        ]
                    ]
                ];
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
                }
                $result = $client->count($params);
                return $result['count'];

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
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
                }
                $result = $client->count($params);
                return $result['count'];

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
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
                }
                $result = $client->count($params);
                return $result['count'];

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
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
                }
                $result = $client->search($params);
                return $result['aggregations']['document_count']['value'];

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
                                    ["term" => ["module_srl" => $module_srl]],
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

                $result = $client->count($params);
                return $result['count'];
        }

        return 0;
    }

    function getIndexDocumentApproximatedOffset($obj, $total_count = -1) {
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
        $module_srl = $obj->module_srl;
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
                                        ]
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
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
                }
                $result = $client->search($params);
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
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
                }
                $result = $client->search($params);
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
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
                }
                $result = $client->search($params);
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
                                    ["term" => ["module_srl" => $module_srl]],
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
                $result = $client->search($params);

                $aggregations = $result['aggregations'];
                $percentile = $aggregations['percentile'];
                $approximatedOffset = end($percentile['values']);

                return $approximatedOffset;
        }

        return null;
    }

    function getIndexAfterOffset($obj, $total_count = 0) {
        $chunkSize = 10000;
        if($total_count === -1) {
            $total_count = $this->getIndexDocumentSearchCount($obj);
        }
        $client = self::getElasticEngineClient();
        $prefix = self::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $module_srl = $obj->module_srl;
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

        $approximatedOffset = (int)$this->getIndexDocumentApproximatedOffset($obj, $total_count);
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
                                        ]
                                    ]]
                                ]
                            ]
                        ]
                    ]
                ];
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
                    $params['body']['query']['bool']['filter'] = $filter;
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
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params2['body']['query']['bool']['filter'] = $filter;
                }
                $result = $client->search($params2);
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
                    $params['body']['query']['bool']['filter'] = $filter;
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
                                    ["match_phrase" => ["title.my_ngram" => $search_keyword]]
                                ]
                            ]
                        ],
                        "fields" => [$sort_index],
                        "_source" => false
                    ]
                ];
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
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params2['body']['query']['bool']['filter'] = $filter;
                }
                $result = $client->search($params2);
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
                    $params['body']['query']['bool']['filter'] = $filter;
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
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params2['body']['query']['bool']['filter'] = $filter;
                }
                $result = $client->search($params2);
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
                                    ["term" => ["module_srl" => $module_srl]],
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
                $result = $client->count($params);
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
                                    ["term" => ["module_srl" => $module_srl]],
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
                $result = $client->search($params2);
                $hits = $result['hits'];
                $hitsData = $hits['hits'];
                $last = end($hitsData);

                return end($last['fields'][$sort_index]);

        }

        return null;
    }

    function getDocumentFromSearchFromSearchAfter($obj, $columnList) {
        $chunkSize = 10000;

        $total_count = $this->getIndexDocumentSearchCount($obj);

        $prefix = self::getElasticEnginePrefix();
        $client = self::getElasticEngineClient();
        $isExtraVars = $obj->isExtraVars;
        $module_srl = $obj->module_srl;
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
                $search_after = $this->getIndexAfterOffset($obj, $total_count);
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
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
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
                    $_params['body']['query']['bool']['filter'] = $filter;
                }
                $_result = $client->search($_params);
                break;

            case "title":
                $search_after = $this->getIndexAfterOffset($obj, $total_count);
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
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
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
                    $_params['body']['query']['bool']['filter'] = $filter;
                }
                $_result = $client->search($_params);
                break;

            case "content":
                $search_after = $this->getIndexAfterOffset($obj, $total_count);
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
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
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
                    $_params['body']['query']['bool']['filter'] = $filter;
                }
                $_result = $client->search($_params);
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
                        $filter[] = ["match" => ["module_srl" => $module_srl]];
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
                    break;
                }
                break;

            case "extra_vars":
                $search_after = $this->getIndexAfterOffset($obj, $total_count);
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
                $_result = $client->search($_params);

                break;

            default:
                return null;
        }


        $documentSrls = array();
        $data = array();
        $last_id = $total_count - (($page-1) * $list_count);
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
        $module_srl = $obj->module_srl;
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
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
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
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
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
                $filter = [];
                if($category_srl) {
                    $filter[] = ["match" => ["category_srl" => $category_srl]];
                }
                if($module_srl) {
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
                    $filter[] = ["match" => ["status" => "PUBLIC"]];
                }
                if(count($filter) > 0) {
                    $params['body']['query']['bool']['filter'] = $filter;
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
                    $filter[] = ["match" => ["module_srl" => $module_srl]];
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
                    $params['body']['query']['bool']['filter'][] = ["match" => ["doc_category_srl" => $category_srl]];
                }
                break;

        }

        if($params) {
            $client = self::getElasticEngineClient();
            $response = $client->search($params);
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

    function getModuleDefaultConfig() {
        $config = new stdClass();
        $config->use_alternate_search = "Y";
        $config->use_search_after = "N";

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

}
