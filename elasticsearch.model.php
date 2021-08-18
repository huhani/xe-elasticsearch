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
    private static $prefix = "sy";
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

    function getListOutputFromQueryResponse($result, $page, $list_count, $page_count) {
        if(!$result) {
            return null;
        }

        $hits = $result['hits'];
        $result = $hits['hits'];
        $total_count = $hits['total']['value'];
        $total_page = max(1, ceil($total_count / $list_count));
        $data = array();
        $last_id = $total_count - (($page-1) * $list_count);
        foreach($result as $each) {
            $doc = new stdClass();
            foreach($each['_source'] as $key=>$val) {
                $doc->{$key} = $val;
            }
            $data[$last_id--] = $doc;
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

    function getDocumentFromDocumentSearch($obj, $columnList) {
        $prefix = self::getElasticEnginePrefix();
        $module_srl = $obj->module_srl;
        $page = $obj->page;
        $list_count = $obj->list_count;
        $page_count = $obj->page_count;
        $sort_index = $obj->sort_index;
        $order_type = $obj->order_type;
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

        switch ($search_target) {
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
                                            ["match_phrase" => ["title.nori_discard" => $search_keyword]],
                                            ["match_phrase" => ["content.nori_discard" => $search_keyword]]
                                        ]
                                    ]]
                                ],
                                "filter" => [
                                    ["match" => ["module_srl" => $module_srl]],
                                    ["match" => ["status" => "PUBLIC"]],
                                ]
                            ]
                        ],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => $newColumnList,
                    ]
                ];
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
                                    ["term" => ["title.nori_discard" => $search_keyword]]
                                ],
                                "filter" => [
                                    ["term" => ["module_srl" => $module_srl]]
                                ]
                            ]
                        ],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => $newColumnList
                    ]
                ];
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
                                    ["match" => ["content.nori_discard" => $search_keyword]]
                                ],
                                "filter" => [
                                    ["term" => ["module_srl" => $module_srl]]
                                ]
                            ]
                        ],
                        'sort' => [
                            $sort_index => $order_type
                        ],
                        "_source" => $newColumnList
                    ]

                ];
                break;
        }
        if($params) {
            $client = self::getElasticEngineClient();
            $response = $client->search($params);
            $output = $this->getListOutputFromQueryResponse($response, $page, $list_count, $page_count);

            return $output;
        }

        return null;
    }



    function getDocumentFromCommentContent($obj, $columnList = array()) {
        $prefix = self::getElasticEnginePrefix();
        $module_srl = $obj->module_srl;
        $page = $obj->page;
        $list_count = $obj->list_count;
        $page_count = $obj->page_count;
        $sort_index = $obj->sort_index;
        $order_type = $obj->order_type;
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
        $params = [
            'index' => $prefix.'comments',
            'body' => [
                "from" => max($page-1, 0) * $list_count,
                "size" => $list_count,
                'query' => [
                    "bool" => [
                        "must" => [
                            ["match_phrase" => ["content.nori_discard" => $search_keyword]]
                        ],
                        "filter" => [
                            ["term" => ["module_srl" => $module_srl]]
                        ]
                    ]
                ],
                'sort' => [
                    $sort_index => $order_type
                ],
                "_source" => false
            ]
        ];
        $client = self::getElasticEngineClient();
        $response = $client->search($params);
        $output = $this->getListOutputFromQueryResponse($response, $page, $list_count, $page_count);
        var_dump($output);
        exit();

        return $output;
    }

    function getDocumentFromExtraVars($obj, $columnList = array()) {

    }

    function getDocumentFromDocumentSrls($documentSrls = array()) {

    }

    function getDocumentExtraVars($obj) {

    }




    function getDocumentList($obj, $load_extra_vars=true, $columnList = array()) {


        if($obj->search_target === "comment") {
            return $this->getDocumentFromCommentContent($obj, $columnList);
        } else if(strpos($obj->search_target, "extra_vars")) {

        } else {
            return $this->getDocumentFromDocumentSearch($obj, $columnList);
        }



    }



}
