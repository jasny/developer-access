# php-pubkey

Private/public key authentication in PHP

CAUTION! Make sure you replace master.key/master.pub with your own
private and public key.

    openssl genrsa -out master.key 1024
    openssl rsa -in master.key -pubout -out master.pub

MIT licensed - http://www.jasny.net/MIT.txt
