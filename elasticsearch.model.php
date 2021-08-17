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

    function init()
    {
    }

    function getEncrypt() {
        return new SuyongsoEnc();
    }

    function getElasticSearchClientConnector() {
        return new ElasticSearchClientConnector("127.0.0.1", "9200", "sy");
    }

    function getQuery($prefix ,$obj, $columnList) {
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
                                            ["match" => ["title" => $search_keyword]],
                                            ["match" => ["content" => $search_keyword]]
                                        ]
                                    ]]
                                ],
                                "filter" => [
                                    ["match" => ["module_srl" => 134]]
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
                                    ["match" => ["title" => $search_keyword]]
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
                                    ["match" => ["content" => $search_keyword]]
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

        return $params;
    }

    function getDocumentList($obj, $load_extra_vars=true, $columnList = array()) {
        $module_srl = $obj->module_srl;
        $page = $obj->page;
        $list_count = $obj->list_count;
        $page_count = $obj->page_count;
        $search_target = $obj->search_target;
        $search_keyword = $obj->search_keyword;


        $esConnector = $this->getElasticSearchClientConnector();
        $client = $esConnector->getClient();
        $query = $this->getQuery($esConnector->getPrefix(), $obj, $columnList);

        $response = $client->search($query);
        var_dump($obj);
        var_dump($response);

    }



}
