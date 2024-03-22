<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

use Ramsey\Uuid\Uuid;

use Crunz\Configuration\Configuration;
use Crunz\Schedule;
use Crunz\Filesystem;
use Crunz\Task\Collection;
use Crunz\Task\WrongTaskInstanceException;

foreach (glob(__DIR__ . '/../classes/*.php') as $filename){
    require_once $filename;
}

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

$app->group('/tools', function (RouteCollectorProxy $group) {

    $group->post('/notification', function (Request $request, Response $response, array $args) {

        $data = [];

        $params = [];
        if(!empty($request->getParsedBody())){
            $params = array_change_key_case($request->getParsedBody(), CASE_UPPER);
        }

        if( empty($params["MAIL_SUBJECT"]) ) throw new Exception("ERROR - No mail subject submitted");
        if( empty($params["MAIL_CONTENT"]) ) throw new Exception("ERROR - No mail content submitted");
        if( empty($params["RECIPIENT_LST"]) || !array($params["RECIPIENT_LST"]) ) throw new Exception("ERROR - No recipient list submitted");


        $app_configs = $this->get('configs')["app_configs"];
        $base_path =$app_configs["paths"]["base_path"];

        if(empty($_ENV["CRUNZ_BASE_DIR"])){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = $_ENV["CRUNZ_BASE_DIR"];
        }

        if(!file_exists ( $crunz_base_dir."/crunz.yml" )) throw new Exception("ERROR - Crunz.yml configuration file not found");
        $crunz_config_yml = file_get_contents($crunz_base_dir."/crunz.yml");

        if(empty($crunz_config_yml)) throw new Exception("ERROR - Crunz configuration file empty");

        try {
            $crunz_config = Yaml::parse($crunz_config_yml);
        } catch (ParseException $exception) {
            throw new Exception("ERROR - Crunz configuration file error");
        }

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

            $subject = $params["MAIL_SUBJECT"];
            $message = $params["MAIL_CONTENT"];

            $from = new Address($crunz_config["mailer"]["sender_email"], $crunz_config["mailer"]["sender_name"]);

            $aSENT = [];
            foreach ($params["RECIPIENT_LST"] ?? [] as $recipient_name => $recipient_mail) {

                unset($messageObject);

                $messageObject = (new Email())
                    ->from($from)
                    ->subject($subject)
                    ->text($message)
                ;

                $messageObject->addTo(new Address($recipient_mail, $recipient_name));

		$check_mail = $mailer->send($messageObject);

                if( $check_mail == null ){
                    $aSENT[$recipient_name] = 'SENT';
                }else{
                    $aSENT[$recipient_name] = 'ERR';
		}
            }

        }else{
            throw new Exception("ERROR - No mailer configuration found");
        }

        $data = $aSENT;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

    $group->get('/disk/free-space', function (Request $request, Response $response, array $args) {

        $data = 0;

        $app_configs = $this->get('configs')["app_configs"];
        $base_path =$app_configs["paths"]["base_path"];

        if(empty($_ENV["CRUNZ_BASE_DIR"])){
            $crunz_base_dir = $base_path;
        }else{
            $crunz_base_dir = $_ENV["CRUNZ_BASE_DIR"];
        }

        if(empty($_ENV["LOGS_DIR"])) throw new Exception("ERROR - Logs directory configuration empty");

        if(substr($_ENV["LOGS_DIR"], 0, 2) == "./"){
            $LOGS_DIR = $base_path . "/" . $_ENV["LOGS_DIR"];
        }else{
            $LOGS_DIR = $_ENV["LOGS_DIR"];
        }

        $total_space = $total_space_disp = disk_total_space($LOGS_DIR);
        $free_space = $free_space_disp = disk_free_space($LOGS_DIR);
        $used_space = $used_space_disp = $total_space - $free_space;

        $data = $free_space;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

});
