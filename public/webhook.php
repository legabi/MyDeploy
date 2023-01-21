<?php

// on webhook request by github, do git pull in the project folder
if (isset($_GET['token']) && isset($_GET['project'])) {
    $db = new SQLite3('database.sqlite');

    // verify tables config and projects exist, else quit
    if (!$db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='config'") || !$db->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='projects'")) {
        echo "Tables config and projects do not exist. Please run install.php first.";
        exit;
    }

    // verify the token is valid
    $token = $db->querySingle("SELECT value FROM config WHERE name='admin_token'");
    if ($_GET['token'] == $token) {
        // verify the project exists
        $project = $db->prepare("SELECT * FROM projects WHERE name=:id");
        $project->bindValue(':id', $_GET['project'], SQLITE3_TEXT);
        $project = $project->execute()->fetchArray();

        if ($project) {
            // do git pull in the project folder
            $output = shell_exec("cd {$project['location']} && git pull");
            echo $output;

            // send discord webhook
            $discord_webhook = $db->querySingle("SELECT value FROM config WHERE name='discord_webhook'");

            // if discord webhook is set
            if ($discord_webhook) {
                // send discord webhook
                $discord_webhook_data = array(
                    'content' => "Project updated: {$project['name']}",
                    'embeds' => array(
                        array(
                            'title' => $project['name'],
                            'url' => $project['github'],
                            'color' => hexdec('00ff00'),
                            'timestamp' => date('c'),
                            'footer' => array(
                                'text' => 'Project updated'
                            ),
                            'fields' => array(
                                array(
                                    'name' => 'Location',
                                    'value' => $project['location']
                                )
                            )
                        )
                    )
                );
                $discord_webhook_data_string = json_encode($discord_webhook_data);
                $ch = curl_init($discord_webhook);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $discord_webhook_data_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($discord_webhook_data_string)
                ));
                $result = curl_exec($ch);
                curl_close($ch);
            }
        } else {
            echo "Project not found.";
        }
    } else {
        echo "Invalid token.";
    }
} else {
    echo "Invalid request.";
}