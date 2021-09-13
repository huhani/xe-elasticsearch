<?php
/*! Copyright (C) 2021 BGM STORAGE. All rights reserved. */
/**
 * @class  elasticsearchAdminController
 * @author Huhani (mmia268@dnip.co.kr)
 * @brief  ElasticSearch module admin controller class.
 */


class elasticsearchAdminController extends elasticsearch
{
    function init()
    {
    }

    function procElasticsearchAdminIndexDelete() {
        $oElasticsearchModel = getModel('elasticsearch');
        $oElasticsearchController = getController('elasticsearch');
        $targetIndex = Context::get('target_index');
        if(!$targetIndex || substr($targetIndex, 0, 1) == "." || !$oElasticsearchModel->hasIndices([$targetIndex])[0]) {
            return new BaseObject(-1, "올바르지 않거나 존재하지 않는 인덱스입니다.");
        }

        $oElasticsearchController->deleteIndex($targetIndex);
    }

    function procElasticsearchAdminIndexPurge() {
        $oElasticsearchModel = getModel('elasticsearch');
        $oElasticsearchController = getController('elasticsearch');
        $targetIndex = Context::get('target_index');
        if(!$targetIndex || substr($targetIndex, 0, 1) == "." || !$oElasticsearchModel->hasIndices([$targetIndex])[0]) {
            return new BaseObject(-1, "올바르지 않거나 존재하지 않는 인덱스입니다.");
        }

        $output = $oElasticsearchController->forceMerge($targetIndex);

        $this->add("result", $output);
    }

    function procElasticsearchAdminIndexDocumentDelete() {
        $oElasticsearchModel = getModel('elasticsearch');
        $oElasticsearchController = getController('elasticsearch');
        $targetIndex = Context::get('target_index');
        $_ids = Context::get('_ids');
        if(!$targetIndex || substr($targetIndex, 0, 1) == "." || !$oElasticsearchModel->hasIndices([$targetIndex])[0]) {
            return new BaseObject(-1, "올바르지 않거나 존재하지 않는 인덱스입니다.");
        }
        if(!$_ids || (is_array($_ids) && !count($_ids))) {
            return new BaseObject(-1, "삭제할 데이터의 id값이 없습니다.");
        }
        if(!is_array($_ids)) {
            $_ids = array($_ids);
        }

        $output = $oElasticsearchController->deleteIndexDocuments($targetIndex, $_ids);
        $deletedCount = isset($output['deleted']) ? $output['deleted'] : 0;
        $this->add("deleted_count", $deletedCount);

        $this->setMessage('success_deleted');
        $returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispElasticsearchAdminIndexDocumentList', 'target_index', $targetIndex);
        $this->setRedirectUrl($returnUrl);
    }

    function procElasticsearchAdminIndexDocumentManage() {
        $targetIndex = Context::get("target_index");
        $startDocumentSrl = Context::get("start_document_srl");
        $endDocumentSrl = Context::get("end_document_srl");
        $job = Context::get('job');
        if($startDocumentSrl >= $endDocumentSrl) {
            return new BaseObject(-1, "end_document_srl 값이 start_document_srl보다 커야합니다.");
        }

        switch($job) {
            case "insert":
                $oElasticsearchAdminModel = getAdminModel('elasticsearch');
                $chunkCount = Context::get("chunk_count");
                if($chunkCount <= 0 || $chunkCount >= 20000) {
                    $chunkCount = 1000;
                }
                $importer = $oElasticsearchAdminModel->getElasticSearchDocumentImporter();
                $output = $importer->import($startDocumentSrl, $endDocumentSrl, $chunkCount, false);
                $this->add('insertCount', $output->insertCount);
                $this->add('updateCount', $output->updateCount);
                $this->add('failCount', $output->failCount);
                $this->add('lastDocumentSrl', $output->lastDocumentSrl);

                break;

            case "delete":
                $oElasticsearchController = getController('elasticsearch');
                $output = $oElasticsearchController->deleteIndexDocumentsByRange($targetIndex, $startDocumentSrl, $endDocumentSrl);

                $this->add('deletedCount', $output['deleted']);
                break;
        }

        return;
    }

    function procElasticsearchAdminIndexCommentManage() {
        $targetIndex = Context::get("target_index");
        $startCommentSrl = Context::get("start_comment_srl");
        $endCommentSrl = Context::get("end_comment_srl");
        $job = Context::get('job');
        if($startCommentSrl >= $endCommentSrl) {
            return new BaseObject(-1, "end_comment_srl 값이 start_comment_srl보다 커야합니다.");
        }

        switch($job) {
            case "insert":
                $oElasticsearchAdminModel = getAdminModel('elasticsearch');
                $chunkCount = Context::get("chunk_count");
                if($chunkCount <= 0 || $chunkCount >= 20000) {
                    $chunkCount = 1000;
                }
                $importer = $oElasticsearchAdminModel->getElasticSearchCommentImporter();
                $output = $importer->import($startCommentSrl, $endCommentSrl, $chunkCount, false);
                $this->add('insertCount', $output->insertCount);
                $this->add('updateCount', $output->updateCount);
                $this->add('failCount', $output->failCount);
                $this->add('lastCommentSrl', $output->lastCommentSrl);

                break;

            case "delete":
                $oElasticsearchController = getController('elasticsearch');
                $output = $oElasticsearchController->deleteIndexCommentsByRange($targetIndex, $startCommentSrl, $endCommentSrl);

                $this->add('deletedCount', $output['deleted']);
                break;
        }

        return;
    }

    function procElasticsearchAdminIndexDocumentExtraVarsManage() {
        $targetIndex = Context::get("target_index");
        $startDocumentSrl = Context::get("start_document_srl");
        $endDocumentSrl = Context::get("end_document_srl");
        $job = Context::get('job');
        if($startDocumentSrl >= $endDocumentSrl) {
            return new BaseObject(-1, "end_document_srl 값이 start_document_srl보다 커야합니다.");
        }

        switch($job) {
            case "insert":
                $oElasticsearchAdminModel = getAdminModel('elasticsearch');
                $chunkCount = Context::get("chunk_count");
                $lastVarIdx = Context::get('last_var_idx');
                if($chunkCount <= 0 || $chunkCount >= 20000) {
                    $chunkCount = 1000;
                }
                if(!$lastVarIdx && $lastVarIdx != 0) {
                    $lastVarIdx = -1;
                }

                $importer = $oElasticsearchAdminModel->getElasticSearchDocumentExtraVarsImporter();
                $output = $importer->import($startDocumentSrl, $lastVarIdx,  $endDocumentSrl, $chunkCount, false);
                $this->add('insertCount', $output->insertCount);
                $this->add('updateCount', $output->updateCount);
                $this->add('failCount', $output->failCount);
                $this->add('lastDocumentSrl', $output->lastDocumentSrl);
                $this->add('lastVarIdx', $output->lastVarIdx);

                break;

            case "delete":
                $oElasticsearchController = getController('elasticsearch');
                $output = $oElasticsearchController->deleteIndexDocumentExtraVarsByRange($targetIndex, $startDocumentSrl, $endDocumentSrl);

                $this->add('deletedCount', $output['deleted']);
                break;
        }

        return;
    }

    function procElasticsearchAdminIndexFileManage() {
        $targetIndex = Context::get("target_index");
        $startFileSrl = Context::get("start_file_srl");
        $endFileSrl = Context::get("end_file_srl");
        $job = Context::get('job');
        if($startFileSrl >= $endFileSrl) {
            return new BaseObject(-1, "end_comment_srl 값이 start_comment_srl보다 커야합니다.");
        }

        switch($job) {
            case "insert":
                $oElasticsearchAdminModel = getAdminModel('elasticsearch');
                $chunkCount = Context::get("chunk_count");
                if($chunkCount <= 0 || $chunkCount >= 20000) {
                    $chunkCount = 1000;
                }
                $importer = $oElasticsearchAdminModel->getElasticSearchFileImporter();
                $output = $importer->import($startFileSrl, $endFileSrl, $chunkCount, false);
                $this->add('insertCount', $output->insertCount);
                $this->add('updateCount', $output->updateCount);
                $this->add('failCount', $output->failCount);
                $this->add('lastFileSrl', $output->lastFileSrl);

                break;

            case "delete":
                $oElasticsearchController = getController('elasticsearch');
                $output = $oElasticsearchController->deleteIndexFileByRange($targetIndex, $startFileSrl, $endFileSrl);

                $this->add('deletedCount', $output['deleted']);
                break;
        }

        return;
    }

    function procElasticsearchAdminIndexRefreshInterval() {
        $refreshInterval = (int)Context::get('refresh_interval');

        return $this->setIndicesRefreshInterval($refreshInterval);
    }

    function procElasticsearchAdminModuleSetting() {
        $oElasticsearchModel = getModel('elasticsearch');
        $conf = $oElasticsearchModel->getModuleConfig();
        $oModuleController = getController('module');
        $vars = Context::getRequestVars();
        $config = new stdClass();
        $new_search_target_list = array();
        if(isset($vars->search_target_list) && is_array($vars->search_target_list)) {
            foreach($vars->search_target_list as $each) {
                if(in_array($each, array("title_content", "title", "content", "nick_name", "comment", "tags", "extra_vars")) && !in_array($each, $new_search_target_list)) {
                    $new_search_target_list[] = $each;
                }
            }
        }
        
        $config->use_alternate_search = $vars->use_alternate_search === "Y" ? "Y" : "N";
        $config->use_search_after = $vars->use_search_after === "Y" ? "Y" : "N";

        $config->search_target_module_srl = Context::get('search_target_module_srl');
        if(!$config->search_target_module_srl) $config->search_target_module_srl = '';
        $config->search_target_list = $new_search_target_list;
        $config->skin = Context::get('skin');
        $config->search_module_target = Context::get('search_module_target');
        $config->skin_vars = $conf->skin_vars;

        $output = $oModuleController->updateModuleConfig('elasticsearch', $config);
        if (!$output->toBool())
        {
            return $output;
        }

        $this->setMessage('success_saved');
        $returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispElasticsearchAdminOtherSetting');
        $this->setRedirectUrl($returnUrl);
    }

    function procElasticsearchAdminIndexRemapping() {
        $oElasticsearchController = getController('elasticsearch');
        $oElasticsearchController->remappingIndices();
    }

    function procElasticsearchAdminErrorLogDelete() {
        $oElasticsearchController = getController('elasticsearch');
        $arr = array();
        $error_id = Context::get('error_id');
        if($error_id && is_array($error_id)) {
            $arr = $error_id;
        } else if($error_id) {
            $arr[] = $error_id;
        }

        $output = $oElasticsearchController->deleteErrorLog($arr);

        return $output;
    }

    function procElasticsearchAdminErrorLogDeleteAll() {
        $oElasticsearchController = getController('elasticsearch');
        $output = $oElasticsearchController->deleteErrorLogsAll();

        return $output;
    }

    function setIndicesRefreshInterval($refreshInterval = 1) {
        $oElasticsearchModel = getModel('elasticsearch');
        $oElasticsearchController = getController('elasticsearch');
        $prefix = $oElasticsearchModel::getElasticEnginePrefix();
        if($prefix) {
            $prefix .= "_";
        }
        $targetIndices = array($prefix."documents", $prefix."comments", $prefix."document_extra_vars", $prefix."files");
        $hasNotIndices = array_search(false, $oElasticsearchModel->hasIndices($targetIndices));
        if($hasNotIndices !== false) {
            return new BaseObject(-1, "인덱스가 올바르게 설정되어있지 않습니다.");
        }
        if($refreshInterval < -1 || $refreshInterval > 3600*24) {
            return new BaseObject(-1, "새로고침 주기 값이 올바르지 않습니다.");
        }

        foreach($targetIndices as $each) {
            $output = $oElasticsearchController->setIndexRefreshInterval($each, $refreshInterval);
            if(!$output) {
                return new BaseObject(-1, "인덱스 설정 도중 오류가 발생했습니다.");
            }
        }

        return new BaseObject();
    }

}