<?php

// Execute this file to install the database 

// sudo apt install phpXX-sqlite3

// generate a token for the admin user
$token = bin2hex(random_bytes(32));

// create the database .sqlite file
$db = new SQLite3('database.sqlite');

// create projets table
$db->exec('CREATE TABLE IF NOT EXISTS projects (id INTEGER PRIMARY KEY, name TEXT, github TEXT, location TEXT, created_at TEXT, updated_at TEXT)');

// create config table
$db->exec('CREATE TABLE IF NOT EXISTS config (id INTEGER PRIMARY KEY, name TEXT, value TEXT)');

// insert the admin token
$request = $db->prepare("INSERT INTO config (name, value) VALUES ('admin_token', :token)");
$request->bindValue(':token', $token, SQLITE3_TEXT);
$request->execute();

// ask for discord webhook url
echo "Enter the discord webhook url: ";
$discord_webhook = trim(fgets(STDIN));

// verify the discord webhook url is valid
if (filter_var($discord_webhook, FILTER_VALIDATE_URL)) {
    // insert the discord webhook url
    $request = $db->prepare("INSERT INTO config (name, value) VALUES ('discord_webhook', :discord_webhook)");
    $request->bindValue(':discord_webhook', $discord_webhook, SQLITE3_TEXT);
    $request->execute();
} else {
    echo "Passed";
}

echo "Installation complete. Your admin token is: $token";