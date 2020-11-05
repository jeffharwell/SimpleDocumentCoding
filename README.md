# SimpleDocumentCoding
Very simple PHP site for allowing others to code documents. They put in their coder id, and the site allows them to code a directory of documents using the coding form that you set up.

# Database Setup

First create the database user that the program will use to access the database, create the database itself, and assign permissions.

    MariaDB [(none)]> create user 'db_user'@'%' identified by 'XXXXXXXXXXXXXXX';
    MariaDB [(none)]> create database coding_database;
    MariaDB [(none)]> grant all privileges on coding_database.* to 'db_user'@'%';

Now run the PHP code to set-up the database. Usage is where XXXXXXXXX is the password you assigned to the db_user that you created previously.

    php ./setup_database.php db_user coding_database XXXXXXXXXX



