<?php

if (php_sapi_name() != 'cli') {
    exit('ERROR: Not in CLI mode' . PHP_EOL);
}

define('__XE__',   TRUE);

require '../../../config/config.inc.php';
$oContext = Context::getInstance();
$oContext->init();


$oElasticsearchAdminModel = getAdminModel('elasticsearch');
$install = $oElasticsearchAdminModel->getElasticSearchInstall(true);
$arrImporter = array(
    $oElasticsearchAdminModel->getElasticSearchDocumentImporter(true),
    $oElasticsearchAdminModel->getElasticSearchCommentImporter(true),
    $oElasticsearchAdminModel->getElasticSearchDocumentExtraVarsImporter(true)
);


$install->removeIndexes();
$install->installIndexes();
foreach($arrImporter as $importer) {
    $importer->import();
}

echo "DONE!!";

$oContext->close();