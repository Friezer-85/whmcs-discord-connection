<?php
$client_id = "";
$secret_id = "";
$scopes = "identify email";
$domainurl = "https://billing.example.com";
$guild_id = "000000000000000000";
$role_id = "000000000000000000";
$bot_token = "";

use WHMCS\Authentication\CurrentUser;
use WHMCS\ClientArea;

define('CLIENTAREA', true);
require __DIR__ . '/init.php';

$ca = new ClientArea();
$ca->setPageTitle('Discord Connection');
$ca->initPage();

$currentUser = new CurrentUser();
$client = $currentUser->client();

/* Translation function */
function translate($key, $language) {
    $translations = [
        'en' => [
            'page_title' => 'Discord Connection',
            'linked_success' => 'Discord Account Linked Successfully',
            'link_expired' => 'This Link has Expired',
            'no_products' => 'No Active Products Found',
            'must_login' => 'You must be logged in',
        ],
        'french' => [
            'page_title' => 'Connexion Discord',
            'linked_success' => 'Compte Discord lié avec succès',
            'link_expired' => 'Ce lien a expiré',
            'no_products' => 'Aucun produit actif trouvé',
            'must_login' => 'Vous devez être connecté',
        ]
    ];
    
    return $translations[$language][$key] ?? $translations['en'][$key];
}

/* Determinate client language */
$clientLanguage = 'en';
if ($client) {
    $command = 'GetClientsDetails';
    $postData = array(
        'clientid' => $client->id,
    );
    $clientDetails = localAPI($command, $postData);
    $clientLanguage = (isset($clientDetails['language']) && $clientDetails['language'] == 'french') ? 'french' : 'en';
}

$ca->setPageTitle(translate('page_title', $clientLanguage));

if ($client) {    
    if(isset($_GET['code'])) {  
        /* Get user access token */
        $ch = curl_init('https://discord.com/api/oauth2/token');
        curl_setopt_array($ch, array(
            CURLOPT_HTTPHEADER     => array('Content-Type: application/x-www-form-urlencoded'),
            CURLOPT_VERBOSE        => 1,
            CURLOPT_POST           => 1,    
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_RETURNTRANSFER => true
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=authorization_code&code=".$_GET['code']."&redirect_uri={$domainurl}/discord.php&client_id={$client_id}&client_secret={$secret_id}");
        
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch); 
        
        $response = json_decode($data);
        
        if (isset($response->access_token)) {
            /* Get user ids */
            $ch = curl_init('https://discord.com/api/users/@me');
            curl_setopt_array($ch, array(
                CURLOPT_HTTPHEADER     => array('Authorization: Bearer '.$response->access_token),
                CURLOPT_VERBOSE        => 1,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_RETURNTRANSFER => true
            ));
            
            $data = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
            
            $user = json_decode($data);
            
            /* Add Discord ID to client profile */
            $command = 'UpdateClient';
            $customfields = array('discord' => $user->id);
            $postData = array(
                'clientid' => $client->id,
                'customfields' => base64_encode(serialize($customfields))
            );
            $results = localAPI($command, $postData);
            
            /* Add role to user */
            $command = 'GetClientsDetails';
            $postData = array(
                'clientid' => $client->id,
                'stats' => true,
            );
            $results = localAPI($command, $postData);
            
            if (isset($results['stats']['productsnumactive']) && $results['stats']['productsnumactive'] > 0) {
                $url = "https://discord.com/api/v9/guilds/{$guild_id}/members/{$user->id}/roles/{$role_id}"; 
                $curl = curl_init();
                $headers = [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'Authorization: Bot ' . $bot_token
                ];
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(new stdClass()));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                
                $response_role = curl_exec($curl);
                curl_close($curl);
                
                $ca->assign('message', translate('linked_success', $clientLanguage));
            } else {
                $ca->assign('message', translate('no_products', $clientLanguage));
            }
        } else {    
            $ca->assign('message', translate('link_expired', $clientLanguage));
        }
    } else {
        header('Location: https://discordapp.com/oauth2/authorize?response_type=code&client_id=' . $client_id . '&redirect_uri=' . urlencode($domainurl . '/discord.php') . '&scope=' . urlencode($scopes));
        exit;
    }
} else {
    $ca->assign('message', translate('must_login', $clientLanguage));
}

$ca->setTemplate('discord');
$ca->output();
