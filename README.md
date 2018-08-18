# Developer access in PHP application

Private/public key authentication in PHP, granting you as developer access to the application.

CAUTION! Make sure you replace master.key/master.pub with your own private and public key.

    openssl genrsa -out master.key 1024
    openssl rsa -in master.key -pubout -out master.pub

MIT licensed - http://www.jasny.net/MIT.txt

# A secure backdoor for PHP

A backdoor provides access to an application bypassing the normal authentication process. There are many ways to do this. Some are more secure than others.

## Why do you need a backdoor?

In a perfect world you could just deliver an application and all would be good. However in the real world there are unforeseen issues which need to be solved. This means that you as a developer will need access to the application. To reproduce the problem, you usually want to run the application logged in as the user that spotted the issue.

Another use of the backdoor is in a situation where you want to allow a user, that has already been authenticated, to bypassing further authentication. For example if you have a (web hosting) control panel where the user is already logged in, you can allow him to directly access the dashboard of the application without have to enter his password again. This requires a backdoor, since you don’t know his (unencrypted) password.

## A very simple solution

The most simple solution is to use a backdoor password. This password will work for every user. A variation on this, is to have a superuser account, that is allowed to switch to any user on the system.

This solution is fine if you’re the only developer working on these applications. However in a professional environment this solution won’t do. With this method it is easy to give somebody super privileges, but hard to take them away. This requires changing the backdoor password. Which is a tedious job if you’re managing any serious number of applications.

## The secure way

It is easier if there is a project management system where you and other developers can log into. From within that system, the developer can directly login the customer application as any user. Within that application you can configure on which team each developer is. That limits to which applications the developer has access. More important, simply blocking the user account on the project management system will lock the developer out completely.

## Private and public keys

The best known method for logging into a system, is the use of private/public (DSA) keys with SSH. The SSH client signs the request with the private key. The SSH server has the public key in the authorized_key file. It verifies the credentials using the public keys and grands access on success.

We can use the same method with PHP using the OpenSSL extension. We’ll let the client (project management system) sign the username and system name (URL) using openssl\_sign. This signature is verified on the server (customer application) using openssl\_verify. To unsure the login URL can’t be reused later, we’ll throw in a 5 second timeout.

## Generating the keys

The keys can be generated on the (*nix) command line, using the ‘openssl’ binary. I’m using RSA keys, but DSA should also work if preferred.

    # Generate private key
    openssl genrsa -out master.key 1024
    # Generate public key
    openssl rsa -in master.key -pubout -out master.pub
    
The public key should be copied to the ‘pubkeys’ directory of the server application. Make sure the private key is absolutely private. Anybody who has a copy of that, can use the backdoor.
