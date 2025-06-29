<?php
require_once('vendor/autoload.php');

$client = new \GuzzleHttp\Client();

$response = $client->request('POST', 'https://rest.api.transifex.com/resource_strings_async_downloads', [
  'body' => '{"data":{"attributes":{"callback_url":null,"content_encoding":"text","file_type":"default","pseudo":false},"relationships":{"resource":{"data":{"type":"resources","id":"o:infotelGLPI:p:GLPI_accounts:r:locales-glpi-pot--master"}}},"type":"resource_strings_async_downloads"}}',
  'headers' => [
    'accept' => 'application/vnd.api+json',
    'authorization' => 'Bearer 1/f91ef8f89a2cdb246e68b02d0024cd28c1462b96',
    'content-type' => 'application/vnd.api+json',
  ],
]);

echo $response->getBody();