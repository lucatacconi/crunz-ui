<?php

use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run(function() {

    try {
        echo "Running a test...";
        sleep(5);

        //Uncomment this if you want to simulate error
        // throw new Exception('Division by zero.');

    } catch (Exception $e) {

        // To communicate the error via email, modify the configuration file crunz.yml as follows:

        //# This option determines whether the error messages should be emailed or not.
        //email_errors: false -> true

        //# Global Swift Mailer settings
        // mailer:
        //     # Possible values: smtp, mail, and sendmail
        //     transport: smtp
        //     recipients:
        //          dest1@example.com: "Dest1"
        //          dest2@example.com: "Dest2"
        //          dest3@example.com: "Dest3"
        //     sender_name: Crunz-ui
        //     sender_email: mail_from

        //# SMTP settings
        // smtp:
        //     host: mail_server
        //     port: mail_server_port
        //     username: mail_server_username
        //     password: mail_server_password
        //     encryption: mail_server_encrypt_mode


        throw new Exception($e->getMessage());
    }
});

$task
->description('Task file '.__FILE__)
->from('2023-01-01 00:00:00')
->to('2023-12-31 23:59:59')
->daily();

return $schedule;
