<?php
/*! Copyright (C) 2021 BGM STORAGE. All rights reserved. */
/**
 * @class elasticSearchController
 * @author Huhani (mmia268@gmail.com)
 * @brief ElasticSearch module controller class.
 */
use Elasticsearch\ClientBuilder;


class elasticsearchController extends elasticsearch
{
    function init(){

    }

    function triggerBeforeGetDocumentList(&$obj) {
        if($obj->search_target) {
            $oElasticSearchModel = getModel('elasticsearch');
            $config = $oElasticSearchModel->getModuleConfig();
            if($config->use_alternate_search === "Y") {
                $list = $oElasticSearchModel->getDocumentList($obj, $obj->columnList);
                if($list) {
                    $obj->use_alternate_output = $list;
                }
            }
        }

        return null;
    }

    function triggerBeforeModuleInit(&$obj) {
        $document_srl = Context::get('document_srl');
        $page = Context::get('page');
        $search_target = Context::get('search_target');
        $search_keyword = Context::get('search_keyword');
        if($document_srl && $search_target && $search_keyword && !$page) {
            Context::set("page", 1);
        }

        $act = Context::get('');
    }

    function triggerAfterInsertDocument(&$obj) {
        $this->insertDocument($obj);
    }

    function triggerAfterUpdateDocument(&$obj) {
        $this->insertDocument($obj);

        return new BaseObject();
    }

    function triggerAfterDeleteDocument(&$obj) {
        if($obj->document_srl) {
            $this->deleteDocument($obj->document_srl);
            $this->deleteExtraVars($obj->document_srl);
        }

        return new BaseObject();
    }

    function triggerAfterInsertComment(&$obj) {
        $this->insertComment($obj);

        return new BaseObject();
    }

    function triggerAfterUpdateComment(&$obj) {
        $this->insertComment($obj);

        return new BaseObject();
    }

    function triggerAfterDeleteComment(&$obj) {
        if($obj->comment_srl) {
            $this->deleteComment($obj->comment_srl);
        }

        return new BaseObject();
    }

    function triggerAfterTrashDocument(&$obj) {
        $document_srl = (int)$obj->document_srl;
        $this->deleteDocument($document_srl);
        $this->deleteCommentByDocumentSrl($document_srl);

        return new BaseObject();
    }

    function triggerAfterRestoreTrashDocument(&$obj) {
        $this->insertCommentByDocumentSrl($obj->document_srl);
    }

    function triggerAfterMoveDocumentModule(&$obj) {
        $document_srls = $obj->document_srls;
        $module_srl = $obj->module_srl;
        $category_srl = $obj->category_srl;
        $document_srls = explode(",", $document_srls);
        if(!count($document_srls)) {
            return;
        }

        $this->moveDocumentModule($document_srls, $module_srl, $category_srl);
        $this->moveCommentModule($document_srls, $module_srl, $category_srl);
        $this->moveExtraVarsModule($document_srls, $module_srl, $category_srl);
    }

    function triggerDeleteModuleData(&$obj) {
        $module_srl = $obj->module_srl;
        if(!$module_srl) {
            return new BaseObject();
        }

        $this->deleteModuleDocuments($module_srl);
        $this->deleteModuleExtraVars($module_srl);
        $this->deleteModuleComments($module_srl);
    }

    function moveDocumentModule($document_srls, $module_srl, $category_srl) {
        if(!count($document_srls)) {
            return;
        }
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $paramsArray = array("body" => array());
        foreach($document_srls as $each) {
            $docIndex = array(
                'update' => [
                    '_index' => $prefix.'documents',
                    '_id' => $each,
                    '_type' => '_doc'
                ]
            );
            $docBody = array(
                'doc' => [
                    'module_srl' => $module_srl,
                    'category_srl' => $category_srl
                ]
            );
            $paramsArray['body'][] = $docIndex;
            $paramsArray['body'][] = $docBody;
        }
        $response = $client->bulk($paramsArray);
    }

    function moveCommentModule($document_srls, $module_srl, $category_srl) {
        if(!count($document_srls)) {
            return;
        }
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }

        foreach($document_srls as $each) {
            $params = [
                "index" => $prefix."comments",
                "type" => "_doc",
                "body" => [
                    'query' => [
                        'match' => ['document_srl' => $each]
                    ],
                    'script' => [
                        "source" => "ctx._source.module_srl = params.module_srl; ctx._source.doc_category_srl = params.doc_category_srl",
                        "lang" => "painless",
                        "params" => [
                            "module_srl" => $module_srl,
                            "doc_category_srl" => $category_srl
                        ]
                    ]
                ]
            ];

            $response = $client->updateByQuery($params);
        }

    }

    function moveExtraVarsModule($document_srls, $module_srl, $category_srl) {
        if(!count($document_srls)) {
            return;
        }
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }

        foreach($document_srls as $each) {
            $params = [
                "index" => $prefix."document_extra_vars",
                "type" => "_doc",
                "body" => [
                    'query' => [
                        'match' => ['document_srl' => $each]
                    ],
                    'script' => [
                        "source" => "ctx._source.module_srl = params.module_srl; ctx._source.doc_category_srl = params.doc_category_srl",
                        "lang" => "painless",
                        "params" => [
                            "module_srl" => $module_srl,
                            "doc_category_srl" => $category_srl
                        ]
                    ]
                ]
            ];
            $response = $client->updateByQuery($params);
        }

    }

    function insertDocument($obj) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }

        $docData = array();
        $docData['module_srl'] = $obj->module_srl;
        $docData['document_srl'] = $obj->document_srl;
        $docData['category_srl'] = isset($obj->category_srl) ? $obj->category_srl : 0;
        $docData['title'] = isset($obj->title) ? $obj->title : "";
        $docData['content'] =  $obj->content;
        $docData['user_id'] = isset($obj->user_id) ? $obj->user_id : "";
        $docData['user_name'] = isset($obj->user_name) ? $obj->user_name : "";
        $docData['nick_name'] = $obj->nick_name;
        $docData['member_srl'] = isset($obj->member_srl) ? $obj->member_srl : 0;
        $docData['email_address'] = isset($obj->email_address) ? $obj->email_address : "";
        $docData['tags'] = isset($obj->tags) ? $obj->tags : null;
        $docData['regdate'] = isset($obj->regdate) ? $obj->regdate : date("YmdHis");
        $docData['ipaddress'] = isset($obj->ipaddress) ? $obj->ipaddress : $_SERVER['REMOTE_ADDR'];
        $docData['list_order'] = isset($obj->list_order) ? $obj->list_order : 0;
        $docData['status'] = isset($obj->status) ? $obj->status : "PUBLIC";
        $docData['comment_status'] = isset($obj->comment_status) ? $obj->comment_status : "ALLOW";
        $params = [
            'index' => $prefix.'documents',
            'id' => $obj->document_srl,
            'type' => '_doc',
            'body' => $docData
        ];

        try {
            $responses = $client->index($params);
            $this->insertExtraVars($obj->document_srl, $docData['list_order'], $docData['user_id'], $docData['regdate'], $docData['member_srl']);
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
    }

    function deleteDocument($document_srl) {
        $oElasticsearchModel = getModel('elasticsearch');
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }

        $index = $prefix.'documents';
        $this->deleteIndexDocument($index, $document_srl);
    }

    function insertComment($obj) {
        $oDocumentModel = getModel('document');
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        $document_srl = $obj->document_srl;
        $oDocument = $oDocumentModel->getDocument($document_srl);
        if(!$oDocument->isExists()) {
            return;
        }
        if($prefix) {
            $prefix .= "_";
        }

        $cmtData = array();
        $cmtData['comment_srl'] = $obj->comment_srl;
        $cmtData['module_srl'] = $obj->module_srl;
        $cmtData['document_srl'] = $obj->document_srl;
        $cmtData['parent_srl'] = isset($obj->parent_srl) ? $obj->parent_srl : 0;
        $cmtData['is_secret'] = isset($obj->is_secret) ? $obj->is_secret : "N";
        $cmtData['list_order'] = isset($obj->list_order) ? $obj->list_order : 0;
        $cmtData['content'] = $obj->content;
        $cmtData['user_id'] = isset($obj->user_id) ? $obj->user_id : "";
        $cmtData['user_name'] = isset($obj->user_name) ? $obj->user_name : "";
        $cmtData['nick_name'] = $obj->nick_name;
        $cmtData['member_srl'] = isset($obj->member_srl) ? $obj->member_srl : 0;
        $cmtData['email_address'] = isset($obj->email_address) ? $obj->email_address : "";
        $cmtData['homepage'] = isset($obj->homepage) ? $obj->homepage : "";
        $cmtData['status'] = $obj->status;
        $cmtData['regdate'] = isset($obj->regdate) ? $obj->regdate : date("YmdHis");
        $cmtData['last_update'] = isset($obj->last_update) ? $obj->last_update : date("YmdHis");
        $cmtData['ipaddress'] = $_SERVER['REMOTE_ADDR'];
        $cmtData['doc_list_order'] = $oDocument->get('list_order');
        $cmtData['doc_user_id'] = $oDocument->get('user_id');
        $cmtData['doc_regdate'] = $oDocument->get('regdate');
        $cmtData['doc_member_srl'] = $oDocument->get('member_srl');
        $params = [
            'index' => $prefix.'comments',
            'id' => $obj->comment_srl,
            'type' => '_doc',
            'body' => $cmtData
        ];

        try {
            $responses = $client->index($params);
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
    }

    function insertCommentByDocumentSrl($document_srl = 0) {
        if(!$document_srl) {
            return;
        }
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }

        $args = new stdClass();
        $args->document_srl = $document_srl;
        $output = executeQueryArray('elasticsearch.getElasticsearchCommentByDocumentSrl', $args);
        if(!$output->toBool() || !$output->data || !count($output->data)) {
            return;
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
        }

        $response = $client->bulk($paramsArray);
        foreach($response['items'] as $each) {
            if($each['index']['result'] !== "created") {
                var_dump($each);
            }
        }
    }

    function deleteComment($comment_srl) {
        $oElasticsearchModel = getModel('elasticsearch');
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }

        $index = $prefix.'comments';
        $this->deleteIndexDocument($index, $comment_srl);
    }

    function deleteCommentByDocumentSrl($document_srl) {
        if(!$document_srl) {
            return;
        }
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $query = [
            'index' => $prefix.'comments',
            'type' => '_doc',
            'body'=> [
                'query' => [
                    'match' => [
                        'document_srl' => $document_srl
                    ]
                ]
            ]
        ];

        try {
            $responses = $client->deleteByQuery($query);
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
    }

    function insertExtraVars($document_srl, $list_order, $user_id, $regdate, $member_srl) {
        if(!$document_srl) {
            return;
        }
        $this->deleteExtraVars($document_srl);

        $oDocumentModel = getModel('document');
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        $extraVars = $oDocumentModel->getDocumentExtraVarsFromDB([$document_srl]);
        if(!$extraVars->toBool() || !count($extraVars->data)) {
            return;
        }
        if($prefix) {
            $prefix .= "_";
        }

        $paramsArray = array("body" => array());
        $extraVarsIndex = array(
            'index' => ['_index' => $prefix.'document_extra_vars',
                '_type' => '_doc']
        );
        foreach($extraVars->data as $each) {
            $obj = array();
            foreach(get_object_vars($each) as $key=>$val) {
                $obj[$key] = $val;
            }
            $obj['doc_list_order'] = $list_order;
            $obj['doc_user_id'] = $user_id;
            $obj['doc_regdate'] = $regdate;
            $obj['doc_member_srl'] = $member_srl;
            $paramsArray['body'][] = $extraVarsIndex;
            $paramsArray['body'][] = $obj;
        }
        try {
            $response = $client->bulk($paramsArray);
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
        foreach($response['items'] as $each) {
            if($each['index']['result'] !== "created") {
                //var_dump($each);
            }
        }
    }

    function deleteExtraVars($document_srl) {
        if(!$document_srl) {
            return;
        }

        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $query = [
            'index' => $prefix.'document_extra_vars',
            'type' => '_doc',
            'body'=> [
                'query' => [
                    'match' => [
                        'document_srl' => $document_srl
                    ]
                ]
            ]
        ];

        try {
            $responses = $client->deleteByQuery($query);
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
    }

    function deleteModuleDocuments($module_srl) {
        if(!$module_srl) {
            return;
        }
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $query = [
            'index' => $prefix.'documents',
            'type' => '_doc',
            'body'=> [
                'query' => [
                    'match' => [
                        'module_srl' => $module_srl
                    ]
                ]
            ]
        ];
        try {
            $responses = $client->deleteByQuery($query);
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
    }

    function deleteModuleExtraVars($module_srl) {
        if(!$module_srl) {
            return;
        }
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $query = [
            'index' => $prefix.'document_extra_vars',
            'type' => '_doc',
            'body'=> [
                'query' => [
                    'match' => [
                        'module_srl' => $module_srl
                    ]
                ]
            ]
        ];
        try {
            $responses = $client->deleteByQuery($query);
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
    }

    function deleteModuleComments($module_srl) {
        if(!$module_srl) {
            return;
        }
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $query = [
            'index' => $prefix.'comments',
            'type' => '_doc',
            'body'=> [
                'query' => [
                    'match' => [
                        'module_srl' => $module_srl
                    ]
                ]
            ]
        ];
        try {
            $responses = $client->deleteByQuery($query);
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
    }

    function deleteIndex($indexName) {
        if(!$indexName || substr($indexName, 0, 1) == ".") {
            return false;
        }

        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        try {
            $response = $client->indices()->delete(array("index"=> $indexName));
        } catch(Exception $e) {}

        return true;
    }

    function forecMerge($indexName) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $params = [
            'index' => [$indexName],
            'only_expunge_deletes' => true
        ];
        $response = $client->indices()->forcemerge($params);

        return $response;
    }

    function deleteIndexDocument($indexName, $id) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $params = [
            'index' => $indexName,
            'id' => $id
        ];

        try {
            $responses = $client->delete($params);

            return $responses;
        } catch(Exception $e) {
            //print_r($e);
            //exit();
        }
    }

    function deleteIndexDocuments($indexName, array $ids) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $query = [
            'index' => $indexName,
            'body'=> [
                'query' => [
                    'terms' => [
                        '_id' => $ids
                    ]
                ]
            ]
        ];

        try {
            $responses = $client->deleteByQuery($query);

            return $responses;
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
    }

    function deleteIndexDocumentsByRange($indexName, $start_document_srl, $end_document_srl) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $query = [
            'index' => $indexName,
            'type' => '_doc',
            'body' => [
                'query' => [
                    'range' => [
                        "document_srl" => [
                            "gte" => $start_document_srl,
                            "lte" => $end_document_srl
                        ]
                    ]
                ]
            ]
        ];

        try {
            $responses = $client->deleteByQuery($query);

            return $responses;
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
    }

    function deleteIndexCommentsByRange($indexName, $start_comment_srl, $end_comment_srl) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $query = [
            'index' => $indexName,
            'type' => '_doc',
            'body' => [
                'query' => [
                    'range' => [
                        "comment_srl" => [
                            "gte" => $start_comment_srl,
                            "lte" => $end_comment_srl
                        ]
                    ]
                ]
            ]
        ];

        try {
            $responses = $client->deleteByQuery($query);

            return $responses;
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
    }

    function deleteIndexDocumentExtraVarsByRange($indexName, $start_document_srl, $end_document_srl) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $query = [
            'index' => $indexName,
            'body' => [
                'query' => [
                    'range' => [
                        "document_srl" => [
                            "gte" => $start_document_srl,
                            "lte" => $end_document_srl
                        ]
                    ]
                ]
            ]
        ];

        try {
            $responses = $client->deleteByQuery($query);

            return $responses;
        } catch(Exception $e) {
            print_r($e);
            exit();
        }
    }

    function remappingIndices() {
        $oElasticsearchAdminModel = getAdminModel('elasticsearch');
        $installer = $oElasticsearchAdminModel->getElasticSearchInstall();
        $installer->removeIndexes();
        $installer->installIndexes();
    }

}

/* End of file elasticsearch.controller.php */
/* Location: ./modules/elasticsearch/elasticsearch.controller.php */
