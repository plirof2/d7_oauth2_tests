# d7_oauth2_gsis module
Module for Drupal 7 that allows connecting to an Oauth2 server. This is made for the Greek GSIS server but it could be used for other servers with modifications.

## Usage
After enabling oauth2_gsis module ,goto Structure/Block and put the Oauth2_Gsis login block to the Header (or anywhere you prefer).
You will see a button on your WebSite that will redirect you to the Oauth2 server.

## Modifications/configuration
Modifications/configuration is done by modifying these oauth2_gsis.module file functions:

function oauth2_getUrl($url_name){

function oauth2_getConData($code){

## Demo server
Before connectiong to the official/pilot GSIS servers you can try connecting to my NodeJS server modification here : https://github.com/plirof2/fake-oauth2-server
It emulates GSIS server and you can have it return whatever data you prefer


## Security proposals
- use limit_visit for /gsis and /gsis-callback_logout
- add to .htaccess this 
```html
<files "gsis" >
    Order Deny,Allow
    Deny from all
 #   add IPs seperated by space    
    Allow from 127.0.1.1 127.0.0.1
</files>
```