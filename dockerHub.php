<?php

/**
 * Docker Hub Bot for Slack.
 * Inspired by j0k3r
 *
 * You will need a webhook from Slack.
 *
 * @author MentheFraiche <contact@menthefraiche.com>
 * @license MIT
 */

/**
 * Let's configure
 */

// Slack stuff
const SLACK_WEBHOOK    = 'XXXXXXXXXXXXXXXXXXXXX';
const SLACK_CHANNEL    = '#general';
const SLACK_BOT_NAME   = 'Docker';
const SLACK_BOT_AVATAR = 'http://i.imgur.com/uo2vlQC.png';

/**
 * Let's run
 */

function getUrl($url, $json)
{
    $ch = curl_init($url);
    $options = array(
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json),
        ),
        CURLOPT_TIMEOUT => 5,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_SSL_VERIFYPEER => false,
    );

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (200 !== $httpcode)
    {
        curl_close($ch);
        return false;
    }

    if ($response !== false)
    {
        curl_close($ch);
        return $response;
    }

    curl_close($ch);
    die();
}

function postMessage($text, $attachments)
{
    return postToSlack($text, $attachments);
}

function postToSlack($text, $attachments)
{
    foreach ($attachments as $title => $value)
    {
        $fields[] = array(
            'title' => $title,
            'value' => '<' . $value['url'] . '|' . $value['name'] . '>',
            'short' => false,
        );
    }

    $response = array(
        'channel' => SLACK_CHANNEL,
        'username' => SLACK_BOT_NAME,
        'icon_url' => SLACK_BOT_AVATAR,
        'unfurl_links' => false,
        'attachments' => array(
            array(
                'fallback' => $text,
                'pretext' => $text,
                'color' => 'good',
                'fields' => $fields,
            ),
        ),
    );

    $json = json_encode($response);

    return getUrl(SLACK_WEBHOOK, $json);
}

function postToDocker($callbackUrl, $code)
{
    $post = array(
        'state' => ($code == 200 ? 'success' : 'error'),
    );
    $json = json_encode($post);

    return getUrl($callbackUrl, $json);
}

$json = file_get_contents('php://input');
$docker = json_decode($json, true);

$fields = array(
    'Tag' => array(
        'name' => $docker['push_data']['tag'],
        'url' => $docker['repository']['repo_url'] . '/tags/' . $docker['push_data']['tag'],
    ),
);

$response = postMessage('[' . $docker['repository']['repo_name'] . '] Image pushed by <https://hub.docker.com/u/' . $docker['push_data']['pusher'] . '|' . $docker['push_data']['pusher'] . '>', $fields);

postToDocker($docker['callback_url'], (true == $response ? 200 : 500));
