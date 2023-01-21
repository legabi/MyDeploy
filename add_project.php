<?php

$db = new SQLite3('database.sqlite');

// verify tables config and projects exist, else quit
if (!$db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='config'") || !$db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='projects'")) {
    echo "Tables config and projects do not exist. Please run install.php first. \n";
    exit;
}

// ask for project name
echo "Enter the project name: ";
$project_name = trim(fgets(STDIN));

// ask for project github url
echo "Enter the project github url: ";
$project_github = trim(fgets(STDIN));

// ask for location of the project on the server
echo "Enter the location of the project on the server: ";
$project_location = trim(fgets(STDIN));

// verify the project github url is valid
if (filter_var($project_github, FILTER_VALIDATE_URL)) {
    // verify the location of the project on the server is valid and exists
    if (is_dir($project_location)) {
        // insert the project
        $result = $db->prepare("INSERT INTO projects (name, github, location) VALUES (:name, :github, :location)");
        $result->bindValue(':name', $project_name, SQLITE3_TEXT);
        $result->bindValue(':github', $project_github, SQLITE3_TEXT);
        $result->bindValue(':location', $project_location, SQLITE3_TEXT);
        $result->execute();
    } else {
        echo "Error: Invalid location of the project on the server.\n";
        exit;
    }
} else {
    echo "Error: Invalid github url.";
}

// send discord webhook
$discord_webhook = $db->querySingle("SELECT value FROM config WHERE name='discord_webhook'");

// if discord webhook is set
if ($discord_webhook) {
    // send discord webhook
    $discord_webhook_data = array(
        'content' => "New project added: $project_name",
        'embeds' => array(
            array(
                'title' => $project_name,
                'url' => $project_github,
                'color' => hexdec('00ff00'),
                'timestamp' => date('c'),
                'footer' => array(
                    'text' => 'Project added'
                ),
                'fields' => array(
                    array(
                        'name' => 'Location',
                        'value' => $project_location
                    )
                )
            )
        )
    );
    $discord_webhook_data_json = json_encode($discord_webhook_data);

    $discord_webhook_ch = curl_init();
    curl_setopt($discord_webhook_ch, CURLOPT_URL, $discord_webhook);
    curl_setopt($discord_webhook_ch, CURLOPT_POST, 1);
    curl_setopt($discord_webhook_ch, CURLOPT_POSTFIELDS, $discord_webhook_data_json);
    curl_setopt($discord_webhook_ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_exec($discord_webhook_ch);
    curl_close($discord_webhook_ch);

    echo "Project added. The link to add in github is : \n";
    echo "https://exemple.com/webhook.php?token=YOUR_TOKEN&project=$project_name";
}

echo "\n";