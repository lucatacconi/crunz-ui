<?php

use Crunz\Schedule;
use Symfony\Component\Yaml\Yaml;

$schedule = new Schedule();

$task = $schedule->run(function() {

    $aRESULT = [];

    try {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://checkip.amazonaws.com');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $ip_ext = curl_exec($ch);
        $ip_ext = str_replace([chr(10), chr(13)], '', $ip_ext);
        curl_close($ch);

        $ip = $_SERVER['SERVER_ADDR'];
        $aRESULT["DATA"] = "Hi, I'm Crunz-ui, I'm alive. My external IP is " .$ip_ext. " - ".date("Y-m-d H:i:s");

        $jRESULT = json_encode($aRESULT);

        echo "\n".$jRESULT."\n";


        //If mail setting is present, i'll use it to send the same message by mail.

        $crunz_base_dir = __DIR__."/..";


        if(!file_exists ( $crunz_base_dir."/crunz.yml" )) throw new Exception("ERROR - Crunz.yml configuration file not found");
        $crunz_config_yml = file_get_contents($crunz_base_dir."/crunz.yml");

        if(empty($crunz_config_yml)) throw new Exception("ERROR - Crunz configuration file empty");

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            throw new Exception("ERROR - Crunz configuration file error");
        }

        if(    !empty($crunz_config)
            && !empty($crunz_config["mailer"])
            && !empty($crunz_config["mailer"]["transport"]) && $crunz_config["mailer"]["transport"] == 'smtp'
            && !empty($crunz_config["mailer"]["recipients"])
            && !empty($crunz_config["mailer"]["sender_name"])
            && !empty($crunz_config["mailer"]["sender_email"])
            && !empty($crunz_config["smtp"])
            && !empty($crunz_config["smtp"]["host"])
            && !empty($crunz_config["smtp"]["port"])
        ){

            // Create the Transport
            $transport = (new Swift_SmtpTransport($crunz_config["smtp"]["host"], $crunz_config["smtp"]["port"], $crunz_config["smtp"]["encryption"]));

            if(!empty($crunz_config["smtp"]["username"])){
                $transport->setUsername($crunz_config["smtp"]["username"]);
                $transport->setPassword($crunz_config["smtp"]["password"]);
            }

            // Create the Mailer using your created Transport
            $mailer = new Swift_Mailer($transport);

            // Create a message
            $message = (new Swift_Message('Crunz-ui report'))
            ->setFrom([$crunz_config["mailer"]["sender_email"] => $crunz_config["mailer"]["sender_name"]])
            ->setTo($crunz_config["mailer"]["recipients"])
            ->setBody($aRESULT["DATA"]);

            $recipients = [];

            // Send the message
            $result = $mailer->send($message);
            if(!$result){
                throw new Exception("ERROR - Error in sending the email");
            }
        }

    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }

});

$task
->description('Warns that Crunz-ui is working for you, check public IP of the server and sends it to specific addresses.')
->everyTwelveHours();

return $schedule;
