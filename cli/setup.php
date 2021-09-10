<?php

if (php_sapi_name() != 'cli' || !defined('STDIN')) {
    exit('ERROR: Not in CLI mode' . PHP_EOL);
}

define('__XE__',   TRUE);

require '../../../config/config.inc.php';
$oContext = Context::getInstance();
$oContext->init();

$oElasticsearchnModel = getModel('elasticsearch');
$oElasticsearchAdminModel = getAdminModel('elasticsearch');
$oElasticsearchAdminController = getAdminController('elasticsearch');
if($oElasticsearchnModel->isServerAvailable()) {
    $install = $oElasticsearchAdminModel->getElasticSearchInstall(true);
    $arrImporter = array(
        $oElasticsearchAdminModel->getElasticSearchDocumentImporter(true),
        $oElasticsearchAdminModel->getElasticSearchCommentImporter(true),
        $oElasticsearchAdminModel->getElasticSearchDocumentExtraVarsImporter(true),
        $oElasticsearchAdminModel->getElasticSearchFileImporter(true)
    );
    
    $install->removeIndexes();
    $install->installIndexes();
    $output = $oElasticsearchAdminController->setIndicesRefreshInterval(-1);
    if(!$output->toBool()) {
        print_r($output);
        die("error");
    }
    foreach($arrImporter as $importer) {
        $importer->import();
    }
    $output = $oElasticsearchAdminController->setIndicesRefreshInterval(1);
    if(!$output->toBool()) {
        print_r($output);
        die("error");
    }
    echo "DONE!!";
} else {
    die("ElasticSearch 서버가 작동중이지 않거나 연결에 실패하였습니다.\n\n");
}



$oContext->close();
