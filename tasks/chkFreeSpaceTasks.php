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

        $base_path = __DIR__."/..";

        if(!file_exists ( $base_path."/crunz.yml" )) throw new Exception("ERROR - Crunz.yml configuration file not found");
        $crunz_config_yml = file_get_contents($base_path."/crunz.yml");

        if(empty($crunz_config_yml)) throw new Exception("ERROR - Crunz configuration file empty");

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            throw new Exception("ERROR - Crunz configuration file error");
        }


        $dotenv = Dotenv\Dotenv::createImmutable($base_path);
        $dotenv->load();

        if(empty($_ENV["LOGS_DIR"])) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr($_ENV["LOGS_DIR"], 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . $_ENV["LOGS_DIR"];
        }else{
            $LOGS_DIR = $_ENV["LOGS_DIR"];
        }

        $free_space = $free_space_disp = disk_free_space($LOGS_DIR);

        $megabyte_alert = 300;

        $subject = 'Low space warning';
        $message = "The log partition is running out of space. ".number_format($free_space_disp / 1024 / 1024, 2)."Mb left.";

        if($free_space < ($megabyte_alert * 1024 * 1024)){

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
            }else{
                throw new Exception("ERROR - Email configuration not present");
            }
        }

    } catch (Exception $e) {
        throw new Exception($e->getMessage());
    }

});

$task
->description('Check available disk space. Possibly warns in case of running out of space.')
->daily();

return $schedule;
