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
            $list = $oElasticSearchModel->getDocumentList($obj, $obj->isExtraVars, $obj->columnList);
        }


    }


}

/* End of file elasticsearch.controller.php */
/* Location: ./modules/elasticsearch/elasticsearch.controller.php */
