<?php

use Crunz\Schedule;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

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

            $userPart = '';
            if(!empty($crunz_config["smtp"]["username"])){
                $userPart = $crunz_config["smtp"]["username"].":".$crunz_config["smtp"]["password"]."@";
            }

            $dsn = "smtp://".$userPart.$crunz_config["smtp"]["host"].":".$crunz_config["smtp"]["port"]."?verifyPeer=".$crunz_config["smtp"]["encryption"];

            $transport = Transport::fromDsn($dsn);
            $mailer = new Mailer($transport);

            $subject = 'Crunz-ui report';
            $message = $aRESULT["DATA"];

            $from = new Address($crunz_config["mailer"]["sender_email"], $crunz_config["mailer"]["sender_name"]);

            $messageObject = (new Email())
                ->from($from)
                ->subject($subject)
                ->text($message)
            ;

            foreach ($crunz_config["mailer"]["recipients"] ?? [] as $recipient_name => $recipient_mail) {
                $messageObject->addTo(new Address($recipient_mail, $recipient_name));
            }

            $mailer->send($messageObject);
        }

    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }

});

$task
->description('Warns that Crunz-ui is working for you, check public IP of the server and sends it to specific addresses.')
->cron('0 */12 * * *'); //everyTwelveHours equivalent

return $schedule;
