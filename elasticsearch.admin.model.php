<?php
/**
 * @class elasticsearchAdminmodel
 * @author Huhani (mmia268@gmail.com)
 * @brief elasticsearch 모듈의 admin.model class
 **/

use Elasticsearch\ClientBuilder;

class ElasticSearchInstall {

    private $indexSettings = [
        "number_of_replicas" => 0,
        "number_of_shards" => 4,
        "refresh_interval" => "1s",
        "index" => [
            "max_ngram_diff" => 1,
            "analysis" => [
                "normalizer" => [
                    "my_normalizer" => [
                        "type" => "custom",
                        "filter" => ["lowercase", "asciifolding"]
                    ]
                ],
                "analyzer" => [
                    "my_ngram" => [
                        "tokenizer" => "my_t_ngram",
                        "filter" => ["lowercase", "asciifolding"],
                        //"char_filter" => ["html_strip"]
                    ],
                    "my_edge_ngram" => [
                        "tokenizer" => "my_t_edge_ngram",
                    ],
                    "my_ngram_keyword" => [
                        "tokenizer" => "my_t_ngram_keyword"
                    ]
                ],
                "tokenizer" => [
                    "my_t_ngram" => [
                        "type" => "ngram",
                        "min_gram" => 1,
                        "max_gram" => 2,
                        "token_chars"=> ['letter', 'digit']
                    ],
                    "my_t_ngram_keyword" => [
                        "type" => "ngram",
                        "min_gram" => 1,
                        "max_gram" => 2
                    ],
                    "my_t_edge_ngram" => [
                        "type"=> "edge_ngram",
                        "min_gram" => 1,
                        "max_gram" => 80,
                        "side"=> "front"
                    ]
                ]
            ]
        ]];

    private $indexList = [
        "documents" => [
            "document_srl" => ["type" => "long"],
            "module_srl" => ["type" => "long"],
            "category_srl" => ["type" => "long"],
            "title" => ["type" => "text",
                "analyzer" => "standard",
                "fields" => [
                    "my_ngram" => [
                        "type" => "text",
                        "analyzer" => "my_ngram",
                        "search_analyzer" => "my_ngram"
                    ]
                ]],
            "content" => ["type" => "text",
                "analyzer" => "standard",
                "fields" => [
                    "my_ngram" => [
                        "type" => "text",
                        "analyzer" => "my_ngram",
                        "search_analyzer" => "my_ngram"
                    ]
                ]],
            "user_id" => ["type" => "keyword"],
            "user_name" => ["type" => "keyword"],
            "nick_name" => ["type" => "keyword",
                "fields" => [
                    "my_edge_ngram" => [
                        "type" => "text",
                        "analyzer" => "my_edge_ngram",
                        "search_analyzer" => "my_edge_ngram"
                    ]
                ]],
            "member_srl" => ["type" => "long"],
            "email_address" => ["type" => "keyword"],
            "tags" => [
                "type" => "keyword",
                "normalizer" => "my_normalizer"
            ],
            "tags_string" => [
                "type" => "text",
                "analyzer" => "my_ngram_keyword",
                "search_analyzer" => "my_ngram_keyword"
            ],
            "regdate" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
            "ipaddress" => ["type" => "ip"],
            "list_order" => ["type" => "long"],
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
            "doc_category_srl" => ["type" => "long"],
            "doc_status" => ["type" => "keyword"]
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

            "user_id" => ["type" => "keyword"],
            "user_name" => ["type" => "keyword"],
            "nick_name" => ["type" => "keyword",
                "fields" => [
                    "my_edge_ngram" => [
                        "type" => "text",
                        "analyzer" => "my_edge_ngram",
                        "search_analyzer" => "my_edge_ngram"
                    ]
                ]],
            "member_srl" => ["type" => "long"],
            "email_address" => ["type" => "keyword"],
            "homepage" => ["type" => "keyword"],
            "regdate" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
            "last_update" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
            "ipaddress" => ["type" => "ip"],
            "list_order" => ["type" => "long"],
            "status" => ["type" => "long"],
            "doc_list_order" => ["type" => "long"],
            "doc_user_id" => ["type" => "keyword"],
            "doc_regdate" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
            "doc_member_srl" => ["type" => "long"],
            "doc_category_srl" => ["type" => "long"],
            "doc_status" => ["type" => "keyword"]
        ],
        "files" => [
            "file_srl" => ["type" => "long"],
            "upload_target_srl" => ["type" => "long"],
            "upload_target_type" => ["type" => "keyword"],
            "document_srl" => ["type" => "long"],
            "comment_srl" => ["type" => "long"],
            "module_srl" => ["type" => "long"],
            "member_srl" => ["type" => "long"],
            "nick_name" => ["type" => "keyword"],
            "user_id" => ["type" => "keyword"],
            "direct_download" => ["type" => "keyword"],
            "file_extension" => ["type" => "keyword"],
            "source_filename" => ["type" => "text", "fields" => [
                "my_ngram" => [
                    "type" => "text",
                    "analyzer" => "my_ngram",
                    "search_analyzer" => "standard"
                ]
            ]],
            "uploaded_filename" => ["type" => "text", "fields" => [
                "my_ngram" => [
                    "type" => "text",
                    "analyzer" => "my_ngram",
                    "search_analyzer" => "standard"
                ]
            ]],
            "file_size" => ["type" => "long"],
            "isvalid" => ["type" => "keyword"],
            "regdate" => ["type" => "date", "format" => "yyyyMMddHHmmss"],
            "ipaddress" => ["type" => "ip"],
            "doc_status" => ["type" => "keyword"],
            "cmt_is_secret" => ["type" => "keyword"]
        ]
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

    function import($documentStartOffset = -1, $documentEndOffset = -1, $chunkSize = 2000,  $loop = true) {
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

                $document['tags'] = $each->tags ? explode(',', $each->tags) : array();
                $document['tags_string'] = $each->tags;

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
    function import($commentStartOffset = -1, $commentEndOffset = -1, $chunkSize = 3000, $loop = true) {
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
    function import($startFileOffset = -1, $endFileOffset = -1, $chunkCount = 3000, $loop = true) {
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
            $args->sort_index = "files.file_srl";
            $args->order_type = "asc";
            $args->list_count = max(1, $chunkCount);
            if($endFileOffset >= 0) {
                $args->end_file_srl = $endFileOffset;
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
                $each_document_srl = $each->document_srl ? $each->document_srl :
                    ($each->cmt_document_srl ? $each->cmt_document_srl : null);
                $each_comment_srl = $each->comment_srl ? $each->comment_srl : null;
                $each_nick_name = $each->doc_nick_name ? $each->doc_nick_name :
                    ($each->cmt_nick_name ? $each->cmt_nick_name :
                        ($each->nick_name ? $each->nick_name : null));
                $each_user_id = $each->user_id ? $each->user_id :
                    ($each->doc_user_id ? $each->doc_user_id :
                        ($each->cmt_user_id ? $each->cmt_user_id : null));
                $doc_status = $each->doc_status ? $each->doc_status :
                    ($each->doc_status2 ? $each->doc_status2 : null);
                $extension = pathinfo($each->source_filename, PATHINFO_EXTENSION);
                if($extension && strlen($extension) > 0) {
                    $extension = strtolower($extension);
                } else {
                    $extension = null;
                }
                $file = array();
                foreach(get_object_vars($each) as $key=>$val) {
                    $file[$key] = $val;
                }
                unset($file['cmt_document_srl']);
                unset($file['cmt_nick_name']);
                unset($file['cmt_user_id']);
                unset($file['doc_nick_name']);
                unset($file['doc_user_id']);
                unset($file['doc_nick_name']);
                unset($file['doc_status2']);
                $file['document_srl'] = $each_document_srl;
                $file['comment_srl'] = $each_comment_srl;
                $file['nick_name'] = $each_nick_name;
                $file['user_id'] = $each_user_id;
                $file['file_extension'] = $extension;
                $file['doc_status'] = $doc_status;
                if(!$file['cmt_is_secret']) {
                    $file['cmt_is_secret'] = "N";
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
    function import($startDocumentOffset = -1, $startVarIndexOffset = -1, $endDocumentOffset = -1, $chunkCount = 3000, $loop = true) {
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
        $oElasticsearchController = getController('elasticsearch');
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

        try {
            $result = $client->count($params);
        } catch(Exception $e) {
            $oElasticsearchController->insertErrorLog('count', $params, $e);
            return 0;
        }

        return $result['count'];
    }

    function getIndexDocumentApproximatedOffset($obj, $total_count = -1) {
        $oElasticsearchModel = getModel('elasticsearch');
        $oElasticsearchController = getController('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $compression = 200;
        if($total_count === -1) {
            $total_count = $this->getIndexDocumentSearchCount($obj);
        }
        $target_index = $obj->target_index;
        $page = !isset($obj->page) || !$obj->page ? 1 : $obj->page;
        $list_count= !isset($obj->list_count) || !$obj->list_count ? 50 : $obj->list_count;
        $sort_index = !isset($obj->sort_index) || !$obj->sort_index ? null : $obj->sort_index;
        $order_type = !isset($obj->order_type) || !$obj->order_type ? null : "desc";
        $search_target = !isset($obj->search_target) || !$obj->search_target ? null : $obj->search_target;
        $search_keyword = !isset($obj->search_keyword) || !$obj->search_keyword ? null : $obj->search_keyword;
        $fromPage = max(0, $page-1);
        $from = $fromPage * $list_count;
        $percent = $from / $total_count * 100;
        if($order_type === "desc") {
            $percent = 100 - $percent;
        }

        $params = [
            'index' => $target_index,
            'body' => [
                "size" => 0,
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

        if($search_target && $search_keyword) {
            $params['body']['query'] = [
                "bool" => [
                    "must" => [
                        ["match" => [$search_target => $search_keyword]],
                    ]
                ]
            ];
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

    function getIndexAfterOffset($obj, $total_count = -1) {
        if($total_count === -1) {
            $total_count = $this->getIndexDocumentSearchCount($obj);
        }
        $oElasticsearchModel = getModel('elasticsearch');
        $oElasticsearchController = getController('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $target_index = $obj->target_index;
        $page = !isset($obj->page) || !$obj->page ? 1 : $obj->page;
        $list_count= !isset($obj->list_count) || !$obj->list_count ? 50 : $obj->list_count;
        $sort_index = isset($obj->sort_index) ? $obj->sort_index : "regdate";
        $order_type = (!isset($obj->order_type) && $sort_index === "list_order") || $obj->order_type === "asc" ? "asc" : "desc";
        $search_target = $obj->search_target;
        $search_keyword = $obj->search_keyword;
        $fromPage = max(0, $page-1);
        $from = $fromPage * $list_count;

        $approximatedOffset = $this->getIndexDocumentApproximatedOffset($obj, $total_count);
        if($approximatedOffset === false) {
            return false;
        }
        $params = [
            'index' => $target_index
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
        $filter = [];
        $filter[] = ["range" => [$sort_index => [
            ($order_type === "asc" ? "lte" : "gte") => $approximatedOffset
        ]]];
        if(count($filter) > 0) {
            $params['body']['query']['bool']['filter'] = $filter;
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
            'index' => $target_index,
            'size' => abs($diff) + ($diff < 0 ? 1 : 0),
            'body' => [
                "fields" => [$sort_index],
                "_source" => false
            ]
        ];
        $filter = [];
        if($search_target && $search_keyword) {
            $params2['body']['query'] = [
                "bool" => [
                    "must" => [
                        ["match" => [$search_target => $search_keyword]],
                    ]
                ]
            ];
        }
        if(count($filter) > 0) {
            $params2['body']['query']['bool']['filter'] = $filter;
        }

        return $oElasticsearchModel->_getLastItem($params2, $diff, $approximatedOffset, $sort_index, $order_type);
    }

    function getIndexDocumentListFromSearchAfter($obj) {
        $oElasticsearchModel = getModel('elasticsearch');
        $oElasticsearchController = getController('elasticsearch');
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
        $search_after = null;
        if($page > 1) {
            $search_after = $this->getIndexAfterOffset($obj, $total_count);
        }
        $params = [
            'index' => $target_index,
            'body' => [
                "size" => $list_count,
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
        $params['body']['fields'] = $columnList;
        if($search_after) {
            $params['body']['query']['bool']['filter'] = ["range" =>
                [$sort_index =>
                    [($order_type === "asc" ? "gt" : "lt") => $search_after]
                ]
            ];
        }
        try {
            $result = $client->search($params);
        } catch(Exception $e) {
            $oElasticsearchController->insertErrorLog('search', $params, $e);
            $result = null;
        }

        $data = array();
        $last_id = $total_count - (($page-1) * $list_count);
        if($result) {
            $hits = $result['hits'];
            $hitsData = $hits['hits'];
            $hitsDataCount = count($hitsData);
            for($i=0; $i<$hitsDataCount; $i++) {
                $each = $hitsData[$i];
                $obj = new stdClass();
                foreach($each['fields'] as $key=>$val) {
                    $obj->{$key} = $val[0];
                }
                $obj->_id = $each['_id'];
                $data[$last_id--] = $obj;
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

    function getIndexDocumentList($obj) {
        $oElasticsearchModel = getModel('elasticsearch');
        $oElasticsearchController = getController('elasticsearch');
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
        try {
            $result = $client->search($params);
        } catch(Exception $e) {
            $oElasticsearchController->insertErrorLog('search', $params, $e);
            $result = null;
        }

        $data = array();
        if($result) {
            $hits = $result['hits'];
            $hitsData = $hits['hits'];
            $total_count = $hits['total']['value'];
            $total_page = max(1, ceil($total_count / $list_count));
            $last_id = $total_count - (($page-1) * $list_count);
            foreach($hitsData as $each) {
                $obj = new stdClass();
                foreach($each['fields'] as $key=>$val) {
                    $obj->{$key} = $val[0];
                }
                $obj->_id = $each['_id'];
                $data[$last_id--] = $obj;
            }
        } else {
            $total_count = 0;
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

    function getErrorLogList($obj) {
        if(!$obj) {
            $obj = new stdClass();
        }
        $obj->page = isset($obj->page) ? $obj->page : 1;
        $obj->sort_index = "error_id";
        $obj->order_type = "desc";

        $output = executeQueryArray('elasticsearch.getElasticSearchErrorLogList', $obj);

        return $output;
    }

    function getErrorLog($error_id = -1) {
        $args = new stdClass();
        $args->error_id = $error_id;
        $output = executeQuery('elasticsearch.getErrorLogById', $args);

        return $output;
    }

}

/* End of file : elasticsearch.admin.model.php */
/* Location : ./modules/elasticsearch/elasticsearch.admin.model.php */
