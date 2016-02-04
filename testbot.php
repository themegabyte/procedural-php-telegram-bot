<?php

define('API_KEY', '127242530:JFIOEFEF-IOINIUDNIAUFBIUESBF');// replace this with your own API_KEY

$debug = false;

if ($argc > 1){
	if (($argv[1] == '-d' || $argv[1] == '--debug')) {
    $debug = true;
	}
}


function APIRequest($Method, $Data, $Debug = null, $APIKey = null)
{
    if (defined('API_KEY') && $APIKey === null) {
        $APIKey = API_KEY;
    }

    $ch = curl_init(); //initializes cURL
    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$APIKey}/{$Method}");//set the URL that needs to be fetched
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); //seconds to take while trying to connect
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); //max time cURL fucntions can execute
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //returns a string when curl_exec() is called
    curl_setopt($ch, CURLOPT_POST, true); //to use HTTP POST
    curl_setopt($ch, CURLOPT_POSTFIELDS, $Data); //the POST data will be sent from this array
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']);

    $answer = curl_exec($ch); //making connection and getting data

    if ($Debug) {
        echo "\n ****querry**** ".date('g:i:s a')."\n";
    }

    if (curl_error($ch)) {
        error_log(curl_error($ch));
    }
    $answer_json = json_decode($answer);
    if ($answer_json) {
        return $answer_json;
    } else {
        return $answer;
    }
}


$lastoffset = 0;

while (true) {
    $post_r = array('offset' => $lastoffset + 1, 'timeout' => 20);
    $data = APIRequest('getUpdates', $post_r, $debug);

    $reply = null;
    if ($data) {
        foreach ($data->result as $update_object) {
            if ($debug) {
				echo 'UpdateOffset: ' . $lastoffset . "\n";
            }

            if (isset($update_object->message->text)) {
                $command = str_replace('@throwawaybot', '', $update_object->message->text);
            } else {
                continue;
            }

            $args = explode(' ', $command);
            $command = $args[0];

            $chatid = $update_object->message->chat->id;
            $messageid = $update_object->message->message_id;

    
			if ($command == '/help') {
			$helptext = 'this is a throwaway bot';
			$reply['method'] = 'sendMessage';
			$reply['message'] = array(
								'chat_id' => $chatid, 
								'text' => $helptext, 
								'reply_to_message_id' => $messageid
							);
			}

            if ($command == '/start') {
                //$reply['method'] = 'sendVoice';
                $reply['method'] = 'sendMessage';
				$reply['message'] = array(
                                'chat_id' => $chatid,
                                'reply_to_message_id' => $messageid,
                                'text' => 'How do I start?'
								//'voice' => '@'.realpath('dial-up.ogg'),
                            );
         
            }

            if (!is_null($reply['method']) && !is_null($reply['message'])) {
                $datas = APIRequest($reply['method'], $reply['message']);
                if ($debug) {
                    echo $command."\n";
                    echo $reply['method']." sent \n";
                }
            }
            $lastoffset = $update_object->update_id;
        }
    }
}
