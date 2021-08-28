<?php
/**
 * @class elasticsearchAdminmodel
 * @author Huhani (mmia268@gmail.com)
 * @brief elasticsearch 모듈의 admin.model class
 **/

use Elasticsearch\ClientBuilder;

class ElasticSearchInstall {

    private $indexSettings = [
        "number_of_replicas" => 1,
        "number_of_shards" => 5,
        "index" => [
            "analysis" => [
                "analyzer" => [
                    "my_ngram" => [
                        "tokenizer" => "my_t_ngram",
                        "filter" => ["lowercase", "asciifolding"],
                        //"char_filter" => ["html_strip"]
                    ]
                ],
                "tokenizer" => [
                    "my_t_ngram" => [
                        "type" => "ngram",
                        "min_gram" => 2,
                        "max_gram" => 3,
                        "token_chars"=> ['letter', 'digit']
                    ]
                ],
                "filter" => [
                    "my_shingle" => [
                        "type" => "shingle",
                        "token_separator" => "",
                        "max_shingle_size" => 3
                    ],
                    "my_ngram" => [
                        "type" => "nGram",
                        "min_gram" => 2,
                        "max_gram" => 3,
                        "token_chars"=> ['letter', 'digit']
                    ],
                ]
            ]
        ]];

    private $indexList = [
        "documents" => [
            "document_srl" => ["type" => "long"],
            "module_srl" => ["type" => "long"],
            "category_srl" => ["type" => "long"],
            //"lang_code" => ["type" => "keyword", "index" => false],
            //"is_notice" => ["type" => "keyword", "index" => false],
            "title" => ["type" => "text",
                "analyzer" => "standard",
                "fields" => [
                    "my_ngram" => [
                        "type" => "text",
                        "analyzer" => "my_ngram",
                        "search_analyzer" => "my_ngram"
                    ]
                ]],
            //"title_bold" => ["type" => "keyword", "index" => false],
            //"title_color" => ["type" => "keyword", "index" => false],
            "content" => ["type" => "text",
                "analyzer" => "standard",
                "fields" => [
                    "my_ngram" => [
                        "type" => "text",
                        "analyzer" => "my_ngram",
                        "search_analyzer" => "my_ngram"
                    ]
                ]],
            //"readed_count" => ["type" => "unsigned_long"],
            //"voted_count" => ["type" => "long"],
            //"blamed_count" => ["type" => "long"],
            //"comment_count" => ["type" => "unsigned_long"],
            //"trackback_count" => ["type" => "unsigned_long"],
            //"uploaded_count" => ["type" => "unsigned_long"],
            //"password" => ["type" => "keyword", "index" => false],
            "user_id" => ["type" => "keyword"],
            "user_name" => ["type" => "keyword"],
            "nick_name" => ["type" => "keyword"],
            "member_srl" => ["type" => "long"],
            "email_address" => ["type" => "keyword"],
            //"homepage" => ["type" => "keyword"],
            "tags" => ["type" => "text",
                "analyzer" => "standard",
                "fields" => [
                    "my_ngram" => [
                        "type" => "text",
                        "analyzer" => "my_ngram",
                        "search_analyzer" => "my_ngram"
                    ]
                ]],
            //"extra_vars" => ["type" => "keyword", "index" => false],
            "regdate" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
            //"last_update" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
            //"last_updater" => ["type" => "keyword"],
            "ipaddress" => ["type" => "ip"],
            "list_order" => ["type" => "long"],
            //"update_order" => ["type" => "long"],
            //"allow_trackback" => ["type" => "keyword"],
            //"notify_message" => ["type" => "keyword"],
            "status" => ["type" => "keyword"],
            "comment_status" => ["type" => "keyword"],
        ],
        "document_extra_vars" => [
            "document_srl" => ["type" => "long"],
            "module_srl" => ["type" => "long"],
            "var_idx" => ["type" => "long"],
            "lang_code" => ["type" => "keyword", "index" => false],
            "value" => ["type" => "text", "analyzer" => "standard",
                "fields" => [
                    "my_ngram" => [
                        "type" => "text",
                        "analyzer" => "my_ngram",
                        "search_analyzer" => "my_ngram"
                    ]
                ]],
            "eid" => ["type" => "keyword", "index" => false],

            "doc_list_order" => ["type" => "long"],
            "doc_user_id" => ["type" => "keyword"],
            "doc_regdate" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
            "doc_member_srl" => ["type" => "long"],
            "doc_category_srl" => ["type" => "long"]
        ],
        "comments" => [
            "comment_srl" => ["type" => "long"],
            "module_srl" => ["type" => "long"],
            "document_srl" => ["type" => "long"],
            "parent_srl" => ["type" => "long"],
            "is_secret" => ["type" => "keyword"],
            "content" => ["type" => "text",
                "analyzer" => "standard",
                "fields" => [
                    "my_ngram" => [
                        "type" => "text",
                        "analyzer" => "my_ngram",
                        "search_analyzer" => "my_ngram"
                    ]
                ]],
            //"voted_count" => ["type" => "long"],
            //"blamed_count" => ["type" => "long"],
            //"notify_message" => ["type" => "keyword"],
            //"password" => ["type" => "keyword", "index" => false],
            "user_id" => ["type" => "keyword"],
            "user_name" => ["type" => "keyword"],
            "nick_name" => ["type" => "keyword"],
            "member_srl" => ["type" => "long"],
            "email_address" => ["type" => "keyword"],
            "homepage" => ["type" => "keyword"],
            //"uploaded_count" => ["type" => "long"],
            "regdate" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
            "last_update" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
            "ipaddress" => ["type" => "ip"],
            "list_order" => ["type" => "long"],
            "status" => ["type" => "long"],

            "doc_list_order" => ["type" => "long"],
            "doc_user_id" => ["type" => "keyword"],
            "doc_regdate" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
            "doc_member_srl" => ["type" => "long"],
            "doc_category_srl" => ["type" => "long"]
        ],
        /*       "files" => [
                   "file_srl" => ["type" => "unsigned_long"],
                   "upload_target_srl" => ["type" => "unsigned_long"],
                   "upload_target_type" => ["type" => "keyword"],
                   "module_srl" => ["type" => "unsigned_long"],
                   "member_srl" => ["type" => "unsigned_long"],
                   //"download_count" => ["type" => "unsigned_long"],
                   "direct_download" => ["type" => "keyword"],
                   "source_filename" => ["type" => "text", "fields" => [
                       "nori_discard" => [
                           "type" => "text",
                           "analyzer" => "nori_discard",
                           "search_analyzer" => "standard"
                       ]
                   ]],
                   "uploaded_filename" => ["type" => "text", "fields" => [
                       "nori_discard" => [
                           "type" => "text",
                           "analyzer" => "nori_discard",
                           "search_analyzer" => "standard"
                       ]
                   ]],
                   "file_size" => ["type" => "unsigned_long"],
                   //"comment" => ["type" => "text"],
                   "isvalid" => ["type" => "keyword"],
                   //"cover_image" => ["type" => "keyword"],
                   "regdate" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
                   "ipaddress" => ["type" => "ip"]
               ]*/
    ];

    protected $debug = false;

    function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    function installAliases() {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $addAliasList = [
            'body' => [
                "actions" => [
                    ["add" => [
                        "index" => $prefix."documents",
                        "alias" => $prefix."doc_cmt_alias",
                    ]],
                    ["add" => [
                        "index" => $prefix."comments",
                        "alias" => $prefix."doc_cmt_alias",
                        "is_write_index" => true
                    ]]
                ]
            ]

        ];
        $client->indices()->updateAliases($addAliasList);
    }

    function removeAliases() {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $removeAliasList = [
            'body' => [
                "actions" => [
                    ["remove" => [
                        "index" => $prefix."documents",
                        "alias" => $prefix."doc_cmt_alias"
                    ]],
                    ["remove" => [
                        "index" => $prefix."comments",
                        "alias" => $prefix."doc_cmt_alias"
                    ]],
                ]
            ]
        ];
        $client->indices()->updateAliases($removeAliasList);
    }

    function installIndexes() {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        foreach($this->indexList as $key=>$val) {
            $indexExists = false;
            try {
                $settings = $client->indices()->getSettings(array('index'=> $prefix.$key));
                $indexExists = true;
            } catch(Exception $e) {
                //TODO
            }
            if($indexExists) {
                continue;
            }

            $params = [
                "index" => $prefix.$key,
                "body" => [
                    "settings" => $this->indexSettings,
                    "mappings" => [
                        "_source" => [
                            "enabled" => true
                        ],
                        "properties" => $val
                    ]
                ]
            ];
            $response = $client->indices()->create($params);
            if($this->debug) {
                print_r($response);
            }
        }

    }

    function removeIndexes() {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        foreach($this->indexList as $key=>$val) {
            try {
                $response = $client->indices()->delete(array("index"=> $prefix.$key));
                if($this->debug) {
                    print_r($response);
                }
            } catch(Exception $e) {
                if($this->debug) {
                    print_r($e);
                }
                //echo "ERROR!";
                //print_r($e);
            }
        }
    }

}

class ElasticSearchBaseImporter {

    protected $debug = false;

    function __construct($debug = false)
    {
        $this->debug = $debug;
    }

    function getLastItemID($indexName, $fieldName) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $params = [
            'index' => $prefix.$indexName,
            'body' => [
                'query' => [
                    'match_all' => (object)[],
                ],
                'sort' => [
                    $fieldName => 'desc'
                ],
                'size' => 1
            ]
        ];
        try {
            $response = $client->search($params);
            $hits = $response['hits'];
            $data = $hits['hits'];
            if($data && count($data) > 0) {
                return $data[0]['_source'][$fieldName];
            }
        } catch(Exception $e) {
            if($this->debug) {
                print_r($e);
            }
        }

        return -1;
    }

    function import() {

    }
}

class ElasticSearchDocumentImporter extends ElasticSearchBaseImporter {

    function import($documentStartOffset = -1, $documentEndOffset = -1, $chunkSize = 1000,  $loop = true) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $insertCount = 0;
        $updateCount = 0;
        $failCount = 0;
        $last_document_srl = $documentStartOffset === -1 ? $this->getLastItemID("documents", "document_srl") : $documentStartOffset;
        if($last_document_srl < 0) {
            $last_document_srl = 0;
        }
        while(true) {
            $args = new stdClass();
            $args->document_srl = $last_document_srl;
            $args->sort_index = "document_srl";
            $args->order_type = "asc";
            $args->list_count = max(1, $chunkSize);
            if($documentEndOffset >= 0) {
                $args->end_document_srl = $documentEndOffset;
            }
            $output = executeQueryArray('elasticsearch.getImportDocument', $args);
            if(!$output->toBool() || $output->data == null || count($output->data) === 0) {
                break;
            }

            $paramsArray = array("body" => array());
            foreach($output->data as $each) {
                $docIndex = array(
                    'index' => [
                        '_index' => $prefix.'documents',
                        '_id' => $each->document_srl,
                        '_type' => '_doc']
                );
                $document = array();
                foreach(get_object_vars($each) as $key=>$val) {
                    $document[$key] = $val;
                }

                $paramsArray['body'][] = $docIndex;
                $paramsArray['body'][] = $document;
                $last_document_srl = $each->document_srl;
            }

            $response = $client->bulk($paramsArray);
            foreach($response['items'] as $each) {
                if($each['index']['result'] === "created") {
                    $insertCount++;
                } else if($each['index']['result'] === "updated") {
                    $updateCount++;
                } else {
                    $failCount++;
                }
            }
            if($this->debug) {
                echo("last_document_srl : ".$last_document_srl."\n");
            }
            unset($paramsArray);
            if(!$loop) {
                break;
            }
        }

        $retObj = new stdClass();
        $retObj->lastDocumentSrl = $last_document_srl;
        $retObj->insertCount = $insertCount;
        $retObj->updateCount = $updateCount;
        $retObj->failCount = $failCount;

        return $retObj;
    }
}

class ElasticSearchCommentImporter extends ElasticSearchBaseImporter {
    function import($commentStartOffset = -1, $commentEndOffset = -1, $chunkSize = 1000, $loop = true) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $insertCount = 0;
        $updateCount = 0;
        $failCount = 0;
        $last_comment_srl = $commentStartOffset === -1 ? $this->getLastItemID("comments", "comment_srl") : $commentStartOffset;
        if($last_comment_srl < 0) {
            $last_comment_srl = 0;
        }

        while(true) {
            $args = new stdClass();
            $args->comment_srl = $last_comment_srl;
            $args->sort_index = "comment_srl";
            $args->order_type = "asc";
            $args->list_count = max(1, $chunkSize);
            if($commentEndOffset >= 0) {
                $args->end_comment_srl = $commentEndOffset;
            }
            $output = executeQueryArray('elasticsearch.getImportComment', $args);
            if(!$output->toBool() || $output->data == null || count($output->data) === 0) {
                break;
            }
            $paramsArray = array("body" => array());
            foreach($output->data as $each) {
                $cmtIndex = array(
                    'index' => ['_index' => $prefix.'comments',
                        '_id' => $each->comment_srl,
                        '_type' => '_doc']
                );

                $comment = array();
                foreach(get_object_vars($each) as $key=>$val) {
                    $comment[$key] = $val;
                }

                $paramsArray['body'][] = $cmtIndex;
                $paramsArray['body'][] = $comment;
                $last_comment_srl = $each->comment_srl;
            }

            $response = $client->bulk($paramsArray);
            foreach($response['items'] as $each) {
                if($each['index']['result'] === "created") {
                    $insertCount++;
                } else if($each['index']['result'] === "updated") {
                    $updateCount++;
                } else {
                    $failCount++;
                }
            }
            if($this->debug) {
                echo("last_comment_srl : ".$last_comment_srl."\n");
            }
            unset($paramsArray);
            if(!$loop) {
                break;
            }
        }

        $retObj = new stdClass();
        $retObj->lastCommentSrl = $last_comment_srl;
        $retObj->insertCount = $insertCount;
        $retObj->updateCount = $updateCount;
        $retObj->failCount = $failCount;

        return $retObj;
    }
}

class ElasticSearchFileImporter extends ElasticSearchBaseImporter {
    function import($startFileOffset = -1, $endFileOffset = -1, $chunkCount = 1000, $loop = true) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $insertCount = 0;
        $updateCount = 0;
        $failCount = 0;
        $last_file_srl = $startFileOffset === -1 ? $this->getLastItemID("files", "file_srl") : $startFileOffset;
        if($last_file_srl < 0) {
            $last_file_srl = 0;
        }
        while(true) {
            $args = new stdClass();
            $args->file_srl = $last_file_srl;
            $args->sort_index = "file_srl";
            $args->order_type = "asc";
            $args->list_count = max(1, $chunkCount);
            if($endFileOffset < 0) {
                $args->end_file_srl = "$endFileOffset";
            }
            $output = executeQueryArray('elasticsearch.getImportFile', $args);
            if(!$output->toBool() || $output->data == null || count($output->data) === 0) {
                break;
            }
            $paramsArray = array("body" => array());
            foreach($output->data as $each) {
                $fileIndex = array(
                    'index' => ['_index' => $prefix.'files',
                        '_id' => $each->file_srl,
                        '_type' => '_doc']
                );

                $file = array();
                foreach(get_object_vars($each) as $key=>$val) {
                    $file[$key] = $val;
                }
                $paramsArray['body'][] = $fileIndex;
                $paramsArray['body'][] = $file;
                $last_file_srl = $each->file_srl;
            }

            $response = $client->bulk($paramsArray);
            foreach($response['items'] as $each) {
                if($each['index']['result'] === "created") {
                    $insertCount++;
                } else if($each['index']['result'] === "updated") {
                    $updateCount++;
                } else {
                    $failCount++;
                }
            }

            if($this->debug) {
                echo("last_file_srl : ".$last_file_srl."\n");
            }
            unset($paramsArray);
            if(!$loop) {
                break;
            }
        }

        $retObj = new stdClass();
        $retObj->lastFileSrl = $last_file_srl;
        $retObj->insertCount = $insertCount;
        $retObj->updateCount = $updateCount;
        $retObj->failCount = $failCount;

        return $retObj;
    }
}

class ElasticSearchDocumentExtraVarsImporter extends ElasticSearchBaseImporter {
    function import($startDocumentOffset = -1, $startVarIndexOffset = -1, $endDocumentOffset = -1, $chunkCount = 1000, $loop = true) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $insertCount = 0;
        $updateCount = 0;
        $failCount = 0;
        $last_document_srl = $startDocumentOffset === -1 ? $this->getLastDocumentSrl() : $startDocumentOffset;
        $last_var_idx = $startVarIndexOffset;
        if($last_document_srl < 0) {
            $last_document_srl = 0;
        }
        while(true) {
            $args = new stdClass();
            $args->document_srl = $last_document_srl;
            $args->sort_index = "document_extra_vars.document_srl";
            $args->sort_index2 = "document_extra_vars.var_idx";
            $args->order_type = "asc";
            $args->order_type2 = "asc";
            $args->list_count = max(1, $chunkCount);
            $args->last_var_idx = $last_var_idx;
            if($endDocumentOffset > 0) {
                $args->end_document_srl = $endDocumentOffset;
            }
            $output = executeQueryArray('elasticsearch.getImportDocumentExtraVars', $args);
            if(!$output->toBool() || $output->data == null || count($output->data) === 0) {
                break;
            }

            $paramsArray = array("body" => array());
            $extraVarsIndex = array(
                'index' => ['_index' => $prefix.'document_extra_vars',
                    '_type' => '_doc']
            );
            foreach($output->data as $each) {
                $extraVars = array();
                foreach(get_object_vars($each) as $key=>$val) {
                    $extraVars[$key] = $val;
                }

                $paramsArray['body'][] = $extraVarsIndex;
                $paramsArray['body'][] = $extraVars;
                $last_document_srl = $each->document_srl;
                $last_var_idx = $each->var_idx;
            }

            $response = $client->bulk($paramsArray);
            foreach($response['items'] as $each) {
                if($each['index']['result'] === "created") {
                    $insertCount++;
                } else if($each['index']['result'] === "updated") {
                    $updateCount++;
                } else {
                    $failCount++;
                }
            }

            if($this->debug) {
                echo("last_document_srl : ".$last_document_srl."\n");
            }
            unset($paramsArray);
            if(!$loop) {
                break;
            }
        }
        
        $retObj = new stdClass();
        $retObj->lastDocumentSrl = $last_document_srl;
        $retObj->lastVarIdx = $last_var_idx;
        $retObj->insertCount = $insertCount;
        $retObj->updateCount = $updateCount;
        $retObj->failCount = $failCount;

        return $retObj;
    }

    function getLastDocumentSrl() {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $params = [
            'index' => $prefix.'document_extra_vars',
            'body' => [
                'query' => [
                    "match_all" => (object)[]
                ],
                'sort' => [
                    'document_srl' => 'desc'
                ],
                "fields" => ['document_srl'],
                "_source" => false,
                'size' => 1
            ]
        ];
        $response = $client->search($params);
        $hits = $response['hits'];
        $hitsData = $hits['hits'];
        if($hitsData && count($hitsData) > 0) {
            $last_document_srl = $hitsData[0]['fields']['document_srl'][0];
            // 중간에 마이그레이션 되다 말았는 데이터가 있을경우를 위해 마지막 마이그레이션 부분 삭제.
            $this->deleteExtraVarsByDocumentSrl($last_document_srl);

            return $last_document_srl-1;
        }

        return -1;

    }

    function deleteExtraVarsByDocumentSrl($document_srl) {
        if(!$document_srl || $document_srl < 0) {
            return;
        }

        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        if($document_srl) {
            $params = [
                'index' => $prefix.'document_extra_vars',
                'body' => [
                    'query' => [
                        "match" => ["document_srl" => $document_srl]
                    ]
                ]
            ];
        }
        $output = $client->deleteByQuery($params);
    }

}

class elasticsearchAdminModel extends elasticsearch
{
    public function init() {
    }

    public static function getElasticSearchInstall($debug = false) {
        return new ElasticSearchInstall($debug);
    }

    public static function getElasticSearchDocumentImporter($debug = false) {
        return new ElasticSearchDocumentImporter($debug);
    }

    public static function getElasticSearchCommentImporter($debug = false) {
        return new ElasticSearchCommentImporter($debug);
    }

    public static function getElasticSearchFileImporter($debug = false) {
        return new ElasticSearchFileImporter($debug);
    }

    public static function getElasticSearchDocumentExtraVarsImporter($debug = false) {
        return new ElasticSearchDocumentExtraVarsImporter($debug);
    }

    function getIndexDocumentListCount($obj) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();

        $target_index = $obj->target_index;
        $search_target = !isset($obj->search_target) || !$obj->search_target ? null : $obj->search_target;
        $search_keyword = !isset($obj->search_keyword) || !$obj->search_keyword ? null : $obj->search_keyword;
        $params = [
            'index' => $target_index,
            'body' => []
        ];
        if($search_target && $search_keyword) {
            $params['body']['query'] = [
                "bool" => [
                    "must" => [
                        ["match" => [$search_target => $search_keyword]],
                    ]
                ]
            ];
        }

        $result = $client->count($params);

        return $result['count'];
    }

    //documentList를 from이 아닌 searchAfter로 가져옴
    function getIndexDocumentListFromSearchAfter($obj) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $total_count = $this->getIndexDocumentListCount($obj);

        $target_index = $obj->target_index;
        $page = !isset($obj->page) || !$obj->page ? 1 : $obj->page;
        $list_count= !isset($obj->list_count) || !$obj->list_count ? 50 : $obj->list_count;
        $page_count= !isset($obj->page_count) || !$obj->page_count ? 10 : $obj->page_count;
        $sort_index = !isset($obj->sort_index) || !$obj->sort_index ? null : $obj->sort_index;
        $order_type = !isset($obj->order_type) || !$obj->order_type ? null : "desc";
        $search_target = !isset($obj->search_target) || !$obj->search_target ? null : $obj->search_target;
        $search_keyword = !isset($obj->search_keyword) || !$obj->search_keyword ? null : $obj->search_keyword;
        $columnList = !isset($obj->columnList) || !count($obj->columnList) ? ["*"] : $obj->columnList;
        $fromPage = max(0, $page-1);
        $endPage = $fromPage + 1;
        $from = $fromPage * $list_count;
        $end = $endPage * $list_count;
        $search_after = null;

        $moveOffset = floor($from / 10000) * 10000;
        $leftOffset = $moveOffset;
        $afterFromOffset = $from - $moveOffset;
        $afterSizeOffset = $end - $moveOffset;
        while(true) {
            $params = [
                'index' => $target_index,
                'body' => [
                    "size" => $leftOffset > 0 ? 10000 : $afterSizeOffset,
                    "_source" => false,
                    "sort" => [
                        $sort_index => $order_type
                    ]
                ]
            ];

            if($search_target && $search_keyword) {
                $params['body']['query'] = [
                    "bool" => [
                        "must" => [
                            ["match" => [$search_target => $search_keyword]],
                        ]
                    ]
                ];
            }
            if($search_after) {
                $params['body']['search_after'] = $search_after;
            }

            if(!$leftOffset) {
                $params['body']['fields'] = $columnList;
            }
            $result = $client->search($params);
            $hits = $result['hits'];
            $hitsData = $hits['hits'];
            if($leftOffset > 0) {
                $leftOffset -= 10000;
                $last = end($hitsData);
                $search_after = $last['sort'];
                continue;
            }

            $data = array();
            $last_id = $total_count - (($page-1) * $list_count);
            $hitsDataCount = count($hitsData);
            for($i=$afterFromOffset; $i<$hitsDataCount; $i++) {
                $each = $hitsData[$i];
                $obj = new stdClass();
                foreach($each['fields'] as $key=>$val) {
                    $obj->{$key} = $val[0];
                }
                $obj->_id = $each['_id'];
                $data[$last_id--] = $obj;
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

    }

    function getIndexDocumentList($obj) {
        $oElasticsearchModel = getModel('elasticsearch');
        $config = $oElasticsearchModel->getModuleConfig();
        if($config->use_search_after === "Y") {
            return $this->getIndexDocumentListFromSearchAfter($obj);
        }

        $client = $oElasticsearchModel::getElasticEngineClient();
        $target_index = $obj->target_index;
        $page = !isset($obj->page) || !$obj->page ? 1 : $obj->page;
        $list_count= !isset($obj->list_count) || !$obj->list_count ? 50 : $obj->list_count;
        $page_count= !isset($obj->page_count) || !$obj->page_count ? 10 : $obj->page_count;
        $sort_index = !isset($obj->sort_index) || !$obj->sort_index ? null : $obj->sort_index;
        $order_type = !isset($obj->order_type) || !$obj->order_type ? null : "desc";
        $search_target = !isset($obj->search_target) || !$obj->search_target ? null : $obj->search_target;
        $search_keyword = !isset($obj->search_keyword) || !$obj->search_keyword ? null : $obj->search_keyword;
        $columnList = !isset($obj->columnList) || !count($obj->columnList) ? ["*"] : $obj->columnList;

        $params = [
            'index' => $target_index,
            'body' => [
                "from" => max($page-1, 0) * $list_count,
                "size" => $list_count,
                "fields" => $columnList,
                "_source" => false,
                //"track_total_hits" => true
            ]
        ];
        if($search_target && $search_keyword) {
            $params['body']['query'] = [
                "bool" => [
                    "must" => [
                        ["match" => [$search_target => $search_keyword]],
                    ]
                ]
            ];
        }
        if($sort_index) {
            $params['body']['sort'] = [
                $sort_index => $order_type
            ];
        }
        $result = $client->search($params);

        $hits = $result['hits'];
        $hitsData = $hits['hits'];
        $total_count = $hits['total']['value'];
        $total_page = max(1, ceil($total_count / $list_count));
        $data = array();
        $last_id = $total_count - (($page-1) * $list_count);

        foreach($hitsData as $each) {
            $obj = new stdClass();
            foreach($each['fields'] as $key=>$val) {
                $obj->{$key} = $val[0];
            }
            $obj->_id = $each['_id'];
            $data[$last_id--] = $obj;
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

}

/* End of file : elasticsearch.admin.model.php */
/* Location : ./modules/elasticsearch/elasticsearch.admin.model.php */
