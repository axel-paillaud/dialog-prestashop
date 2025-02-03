<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use LouisAuthie\Askdialog\Service\DataGenerator;
use LouisAuthie\Askdialog\Service\AskDialogClient;
use PSpell\Config;

class AskDialogFeedModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        //Check if token is valid
        $token = Tools::getValue('token');
        if ($token != Configuration::get('ASKDIALOG_API_KEY')) {
            $response = array('status' => 'error', 'message' => 'Invalid token');
            die(json_encode($response));
        }
        $this->ajax = true;
    }

    public function displayAjax()
    {
        //Get action from the post request in Json
        $action = Tools::getValue('action');
        $dataGenerator = new DataGenerator();
        $batchSize = Configuration::get('ASKDIALOG_BATCH_SIZE');

        switch ($action) {
            case 'sendCatalogData':
                //Check if there are product remaining to add to JSON as a part of the batch process
                $dataCatalog = $dataGenerator->getCatalogDataForBatch($batchSize, Configuration::get('PS_SHOP_DEFAULT'));
                if (count($dataCatalog) == 0) {
                    //Check if there are files in the temp folder
                    $files = glob(_PS_MODULE_DIR_ . 'askdialog/temp/catalog_partial_*.json');
                    //if there are files in this temp folder, load their content in a loop and check the JSON validity of each, then merge them all in one file
                    if (!empty($files)) {
                        $dataCatalog = [];
                        foreach ($files as $file) {
                            $dataCatalog = array_merge($dataCatalog, json_decode(file_get_contents($file), true));
                        }
                        //Delete all files in the temp folder
                        array_map('unlink', glob(_PS_MODULE_DIR_ . 'askdialog/temp/*'));
                    } else {
                        //Go for a new batch
                        //Add all the products to generate to the database
                        $sql = 'TRUNCATE TABLE ' . _DB_PREFIX_ . 'askdialog_product';
                        Db::getInstance()->execute($sql);

                        $products = Db::getInstance()->executeS('SELECT * FROM ' . _DB_PREFIX_ . 'product');
                        foreach ($products as $product) {
                            $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'askdialog_product (id_product, id_shop) VALUES (' . $product['id_product'] . ', ' . Configuration::get('PS_SHOP_DEFAULT') . ')';
                            Db::getInstance()->execute($sql);
                        }
                        //Retrieve the first batch of products
                        $dataCatalog = $dataGenerator->getCatalogDataForBatch($batchSize, Configuration::get('PS_SHOP_DEFAULT'));
                        //Prepare a partial file
                        $this->generatePartialDataFile($dataCatalog);
                        break;
                    }
                    $filename = 'catalog_' . date('Ymd_His') . '.json';
                    // Generate a temporary file to store the JSON data
                    $tempFile = _PS_MODULE_DIR_ . 'askdialog/temp/'.$filename;
                    file_put_contents($tempFile, json_encode($dataCatalog));
                }else{
                    //Prepare a partial file
                    $this->generatePartialDataFile($dataCatalog);
                    break;
                }

                //Prepare the server transfer
                $askDialogClient = new AskDialogClient(Configuration::get('ASKDIALOG_API_KEY'));
                $return = $askDialogClient->prepareServerTransfer();
                $bodyPrepared = json_decode($return['body'], true);


                $url = $bodyPrepared['url'];
                $fields = $bodyPrepared['fields'];

                //send a PUT request to presigned  aws URL in a  request with guzzle client adding the fields $fields to url in GET the request
                $client = new Client(['verify' => false]);
                $data = ['multipart' => array_merge(
                        array_map(function($name, $contents) {                
                            return [
                                'name'     => $name,
                                'contents' => $contents,
                            ];
                        }, array_keys($fields), $fields),
                        [
                            [
                                'name'     => 'file',
                                'contents' => fopen($tempFile, 'r'),
                                'filename' => $filename,
                            ]
                        ]
                )];
                $data['multipart'][] = [
                    'name'=> 'Content-Type',
                    'contents' => 'application/json'
                ];

                try {
                    $response = $client->post($url, $data);
                } catch (RequestException $e) {
                    echo "<pre>";
                    
                    if ($e->hasResponse()) {
                        echo "Response Body:\n";
                        echo $e->getResponse()->getBody()->getContents();
                    }
                    echo "</pre>";
                }

                //If success move the file to the sent folder
                if ($response->getStatusCode() == 204) {
                    rename($tempFile, _PS_MODULE_DIR_ . 'askdialog/sent/' . $filename);
                    $response = array('status' => 'success', 'message' => 'Catalog data sent');
                    die(json_encode($response));
                } else {
                    $response = array('status' => 'error', 'message' => 'Error sending catalog data');
                    die(json_encode($response));
                }

                break;
                
            default:
                $response = array('status' => 'error', 'message' => 'Invalid action');
                die(json_encode($response));
        }
    }

    private function generatePartialDataFile($dataCatalog)
    {
        $filename = 'catalog_partial_' . date('Ymd_His') . '.json';
        // Generate a temporary file to store the JSON data
        $tempFile = _PS_MODULE_DIR_ . 'askdialog/temp/' . $filename;
        file_put_contents($tempFile, json_encode($dataCatalog));

        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'askdialog_product WHERE id_shop = ' . Configuration::get('PS_SHOP_DEFAULT') . ' AND id_product IN (' . implode(',', array_column($dataCatalog, 'id')) . ')';
        Db::getInstance()->execute($sql);

        die(json_encode(array('status' => 'success', 'message' => 'Partial data generated')));
    }
}