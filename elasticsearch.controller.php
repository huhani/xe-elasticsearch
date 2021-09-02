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
        $this->setFileValidByDocumentSrl($document_srl, "N", $document_srl);

        return new BaseObject();
    }

    function triggerAfterRestoreTrashDocument(&$obj) {
        $this->insertCommentByDocumentSrl($obj->document_srl);
        $this->setFileValidByDocumentSrl($obj->document_srl, "Y");
    }

    function triggerBeforeMoveDocumentModule(&$obj) {
        $this->setFlag("moveDocumentModule", $obj);
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
        $this->unsetFlag("moveDocumentModule");
        $this->setFlag("moveDocumentModuleAfter", $obj);
    }

    function triggerDeleteModuleData(&$obj) {
        $module_srl = $obj->module_srl;
        if(!$module_srl) {
            return new BaseObject();
        }

        $this->deleteModuleDocuments($module_srl);
        $this->deleteModuleExtraVars($module_srl);
        $this->deleteModuleComments($module_srl);
        $this->deleteModuleFiles($module_srl);
    }

    function triggerAfterInsertFile(&$obj) {
        // 댓글도 함께 처리
        $moveFlag = $this->getFlag('moveDocumentModule');
        $copyFlag = $this->getFlag('copyDocumentModule');
        if(!$moveFlag && !$copyFlag) {
            $this->insertFile($obj);
        }
    }

    function triggerAfterInsertDelete(&$obj) {
        $this->deleteFile($obj->file_srl);
    }

    function triggerBeforeCopyDocument(&$obj) {
        $this->setFlag("copyDocumentModule", $obj);
    }

    function triggerAfterCopyDocument(&$obj) {
        $this->unsetFlag("copyDocumentModule");
        $this->setFlag("copyDocumentModuleAfter", $obj);
    }

    function triggerAfterModuleProc() {
        $target_srls = array();
        $copyFlag = $this->getFlag("copyDocumentModuleAfter");
        $moveFlag = $this->getFlag("moveDocumentModuleAfter");
        if($copyFlag) {
            $target_srls = $copyFlag->copied_srls;
        } else if($moveFlag) {
            $target_srls = explode(",", $moveFlag->document_srls);
        }
        if(count($target_srls) > 0) {
            $this->insertFileByDocumentSrls($target_srls);
        }
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

            try {
                $response = $client->updateByQuery($params);
            } catch(Exception $e) {
                $this->insertErrorLog('updateByQuery', $params, $e);
            }
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
            try {
                $response = $client->updateByQuery($params);
            } catch(Exception $e) {
                $this->insertErrorLog('updateByQuery', $params, $e);
            }
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

        $response = null;
        try {
            $response = $client->index($params);
        } catch(Exception $e) {
            $this->insertErrorLog('index', $params, $e);
        }
        if($response) {
            $this->insertExtraVars($obj->document_srl, $docData['list_order'], $docData['user_id'], $docData['regdate'], $docData['member_srl']);
            $this->setFileValid($obj->document_srl, "Y", $obj->document_srl);
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

        $doc_status = $oDocument->get('status');
        $is_secret = isset($obj->is_secret) ? $obj->is_secret : "N";
        if($prefix) {
            $prefix .= "_";
        }
        $cmtData = array();
        $cmtData['comment_srl'] = $obj->comment_srl;
        $cmtData['module_srl'] = $obj->module_srl;
        $cmtData['document_srl'] = $obj->document_srl;
        $cmtData['parent_srl'] = isset($obj->parent_srl) ? $obj->parent_srl : 0;
        $cmtData['is_secret'] = $is_secret;
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
        $response = null;
        try {
            $response = $client->index($params);
        } catch(Exception $e) {
            $this->insertErrorLog('index', $params, $e);
        }
        if($response) {
            $this->setFileValid($obj->comment_srl, "Y", $obj->document_srl, $obj->comment_srl, $is_secret, $doc_status);
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
        try {
            $response = $client->bulk($paramsArray);
        } catch(Exception $e) {
            $this->insertErrorLog('bulk', $paramsArray, $e);
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
            $this->insertErrorLog('deleteByQuery', $query, $e);
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
            $this->insertErrorLog('bulk', $paramsArray, $e);
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
            $this->insertErrorLog('deleteByQuery', $query, $e);
        }
    }

    function insertFile($obj, $isvalid = "N", $document_srl = null, $comment_srl = null) {
        if(!$obj || !$obj->file_srl) {
            return;
        }

        $oMemberModel = getModel('member');
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }

        $file_srl = $obj->file_srl;
        $upload_target_srl = $obj->upload_target_srl;
        $module_srl = $obj->module_srl;
        $direct_download = $obj->direct_download;
        $source_filename = $obj->source_filename;
        $uploaded_filename = $obj->uploaded_filename;
        $file_size = $obj->file_size;
        $member_srl = $obj->member_srl;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        }
        $nick_name = null;
        $user_id = null;
        $extension = pathinfo($obj->source_filename, PATHINFO_EXTENSION);
        if($extension && strlen($extension) > 0) {
            $extension = strtolower($extension);
        } else {
            $extension = null;
        }
        if($member_srl > 0) {
            $oMemberInfo = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
            if($oMemberInfo) {
                $nick_name = $oMemberInfo->nick_name;
                $user_id = $oMemberInfo->user_id;
            }
        }

        $fileData = array();
        $fileData['file_srl'] = $file_srl;
        $fileData['upload_target_srl'] = $upload_target_srl;
        $fileData['module_srl'] = $module_srl;
        $fileData['direct_download'] = $direct_download;
        $fileData['source_filename'] = $source_filename;
        $fileData['uploaded_filename'] = $uploaded_filename;
        $fileData['file_size'] = $file_size;
        $fileData['isvalid'] = $isvalid;
        $fileData['ipaddress'] = $ipaddress;
        $fileData['file_extension'] = $extension;
        $fileData['document_srl'] = $document_srl;
        $fileData['comment_srl'] = $comment_srl;
        $fileData['nick_name'] = $nick_name;
        $fileData['user_id'] = $user_id;
        $fileData['regdate'] = date("YmdHis");
        $fileData['cmt_is_secret'] = "N";

        $params = [
            'index' => $prefix.'files',
            'id' => $obj->file_srl,
            'type' => '_doc',
            'body' => $fileData
        ];

        try {
            $responses = $client->index($params);
        } catch(Exception $e) {
            $this->insertErrorLog('index', $params, $e);
        }
    }

    function insertFileByDocumentSrls(array $document_srls) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $paramsArray = array("body" => array());
        foreach($document_srls as $document_srl) {
            $upload_target_list = array($document_srl);
            $commentArgs = new stdClass();
            $commentArgs->document_srl = $document_srl;
            $commentOutput = executeQueryArray('elasticsearch.getCommentList', $commentArgs);
            $commentList = $commentOutput->data;
            if(count($commentList) > 0) {
                foreach($commentList as $each) {
                    $upload_target_list[] = $each->comment_srl;
                }
            }
            if(count($upload_target_list)) {
                $target_srl_implode = implode(",", $upload_target_list);
                $args = new stdClass();
                $args->upload_target_srls = $target_srl_implode;
                $output = executeQueryArray('elasticsearch.getFileListWithNickName', $args);
                if($output->toBool() && count($output->data)) {
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
                            ($each->_doc_status ? $each->_doc_status : null);
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
                        unset($file['_doc_status']);
                        $file['document_srl'] = $each_document_srl;
                        $file['comment_srl'] = $each_comment_srl;
                        $file['nick_name'] = $each_nick_name;
                        $file['user_id'] = $each_user_id;
                        $file['doc_status'] = $doc_status;
                        $file['file_extension'] = $extension;
                        if(!$file['cmt_is_secret']) {
                            $file['cmt_is_secret'] = "N";
                        }
                        $paramsArray['body'][] = $fileIndex;
                        $paramsArray['body'][] = $file;
                    }
                }
            }
        }
        try {
            $response = $client->bulk($paramsArray);
        } catch(Exception $e) {
            $this->insertErrorLog('bulk', $paramsArray, $e);
        }
    }

    function deleteFile($file_srl) {
        $oElasticsearchModel = getModel('elasticsearch');
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }

        $index = $prefix.'files';
        $this->deleteIndexDocument($index, $file_srl);
    }

    function setFileValid($upload_target_srl, $isvalid = "Y", $document_srl = null, $comment_srl = null, $is_secret = "N", $doc_status = "PUBLIC") {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }

        $params = [
            "index" => $prefix."files",
            "type" => "_doc",
            "body" => [
                'query' => [
                    'match' => ['upload_target_srl' => $upload_target_srl]
                ],
                'script' => [
                    "source" => "ctx._source.isvalid = params.isvalid; ctx._source.document_srl = params.document_srl; ctx._source.comment_srl = params.comment_srl; ctx._source.cmt_is_secret = params.is_secret; ctx._source.doc_status = params.doc_status;",
                    "lang" => "painless",
                    "params" => [
                        "isvalid" => $isvalid,
                        "document_srl" => $document_srl,
                        "comment_srl" => $comment_srl,
                        "is_secret" => $is_secret,
                        "doc_status" => $doc_status
                    ]
                ]
            ]
        ];

        try {
            $response = $client->updateByQuery($params);
        } catch(Exception $e) {
            $this->insertErrorLog('updateByQuery', $params, $e);
        }
    }

    function setFileValidByDocumentSrl($document_srl, $isvalid = "Y") {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }

        $params = [
            "index" => $prefix."files",
            "type" => "_doc",
            "body" => [
                'query' => [
                    'match' => ['document_srl' => $document_srl]
                ],
                'script' => [
                    "source" => "ctx._source.isvalid = params.isvalid",
                    "lang" => "painless",
                    "params" => [
                        "isvalid" => $isvalid
                    ]
                ]
            ]
        ];

        try {
            $response = $client->updateByQuery($params);
        } catch(Exception $e) {
            $this->insertErrorLog('updateByQuery', $params, $e);
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
            $this->insertErrorLog('deleteByQuery', $query, $e);
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
            $this->insertErrorLog('deleteByQuery', $query, $e);
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
            $this->insertErrorLog('deleteByQuery', $query, $e);
        }
    }

    function deleteModuleFiles($module_srl) {
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
            'index' => $prefix.'files',
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
            $this->insertErrorLog('deleteByQuery', $query, $e);
        }
    }

    function deleteIndex($indexName) {
        if(!$indexName || substr($indexName, 0, 1) == ".") {
            return false;
        }

        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $params = array("index"=> $indexName);
        try {
            $response = $client->indices()->delete($params);
        } catch(Exception $e) {
            $this->insertErrorLog('deleteIndex', $params, $e);
        }

        return true;
    }

    function forecMerge($indexName) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $params = [
            'index' => [$indexName],
            'only_expunge_deletes' => true
        ];
        try {
            $response = $client->indices()->forcemerge($params);
        } catch(Exception $e) {
            $this->insertErrorLog('forcemerge', $params, $e);
        }
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
            $this->insertErrorLog('delete', $params, $e);
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
            $this->insertErrorLog('deleteByQuery', $query, $e);
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
            $this->insertErrorLog('deleteByQuery', $query, $e);
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
            $this->insertErrorLog('deleteByQuery', $query, $e);
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
            $this->insertErrorLog('deleteByQuery', $query, $e);
        }
    }

    function deleteIndexFileByRange($indexName, $start_file_srl, $end_file_srl) {
        $oElasticsearchModel = getModel('elasticsearch');
        $client = $oElasticsearchModel::getElasticEngineClient();
        $query = [
            'index' => $indexName,
            'type' => '_doc',
            'body' => [
                'query' => [
                    'range' => [
                        "file_srl" => [
                            "gte" => $start_file_srl,
                            "lte" => $end_file_srl
                        ]
                    ]
                ]
            ]
        ];

        try {
            $responses = $client->deleteByQuery($query);

            return $responses;
        } catch(Exception $e) {
            $this->insertErrorLog('deleteByQuery', $query, $e);
        }
    }

    function remappingIndices() {
        $oElasticsearchAdminModel = getAdminModel('elasticsearch');
        $installer = $oElasticsearchAdminModel->getElasticSearchInstall();
        $installer->removeIndexes();
        $installer->installIndexes();
    }

    function insertErrorLog($type, array $params, Exception $error){
        $logged_info = Context::get('logged_info');
        $act = Context::get('act');
        $module = Context::get('module');
        $member_srl = $logged_info ? $logged_info->member_srl : 0;
        $nick_name = $logged_info ? $logged_info->nick_name : null;
        $request_uri = $_SERVER['REQUEST_URI'];
        $ipaddress = null;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        }
        $regdate = date("YmdHis");
        $params_json = $params ? json_encode($params, JSON_PRETTY_PRINT) : null;

        $errorMsgJSON = @json_decode($error->getMessage());
        if(!$errorMsgJSON) {
            $errorMsgJSON = $error->getMessage();
        }
        $error_json  = json_encode(array(
            'error' => array(
                'msg' => $errorMsgJSON,
                'code' => $error->getCode(),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'trace' => $error->getTrace()
            ),
        ), JSON_PRETTY_PRINT);

        $args = new stdClass();
        $args->act = $act;
        $args->module = $module;
        $args->type = $type;
        $args->params = $params_json;
        $args->error = $error_json;
        $args->member_srl = $member_srl;
        $args->nick_name = $nick_name;
        $args->request_uri = $request_uri;
        $args->ipaddress = $ipaddress;
        $args->regdate = $regdate;
        $output = executeQuery('elasticsearch.insertElasticSearchErrorLog', $args);
    }

    function deleteErrorLog(array $error_id){
        if(!count($error_id)) {
            return null;
        }

        $id = implode(",", $error_id);
        $args = new stdClass();
        $args->error_id = $id;
        $output = executeQuery('elasticsearch.deleteElasticSearchErrorLogById', $args);

        return $output;
    }

    function deleteErrorLogsAll() {
        $args = new stdClass();
        $output = executeQuery('elasticsearch.deleteElasticSearchErrorLogAll', $args);

        return $output;
    }

    private function setFlag($name, $data = null) {
        if(!isset($GLOBALS['__elasticSearch__'])) {
            $GLOBALS['__elasticSearch__'] = array();
        }
        $GLOBALS['__elasticSearch__'][$name] = $data;
    }

    private function getFlag($name) {
        if(isset($GLOBALS['__elasticSearch__']) && isset($GLOBALS['__elasticSearch__'][$name])) {
            return $GLOBALS['__elasticSearch__'][$name];
        }

        return null;
    }

    private function unsetFlag($name) {
        if(isset($GLOBALS['__elasticSearch__']) && isset($GLOBALS['__elasticSearch__'][$name])) {
            unset($GLOBALS['__elasticSearch__'][$name]);
        }
    }

}

/* End of file elasticsearch.controller.php */
/* Location: ./modules/elasticsearch/elasticsearch.controller.php */
