<?php

declare(strict_types=1);

namespace CrunzUI\Task;

class CrunzUITaskGenerator
{
    private $frequencyDictionary = [];
    private $timeSetDictionary = [];
    private $lifeTimeDictionary = [];
    private $individualFieldsSettingsDictionary = [];

    public function __construct()
    {
        $this->frequencyDictionary[] = "hourly";
        $this->frequencyDictionary[] = "daily";
        $this->frequencyDictionary[] = "weekly";
        $this->frequencyDictionary[] = "weeklyOn";
        $this->frequencyDictionary[] = "monthly";
        $this->frequencyDictionary[] = "quarterly";
        $this->frequencyDictionary[] = "yearly";
        $this->frequencyDictionary[] = "dailyAt";
        $this->frequencyDictionary[] = "twiceDaily";
        $this->frequencyDictionary[] = "weekdays";
        $this->frequencyDictionary[] = "mondays";
        $this->frequencyDictionary[] = "tuesdays";
        $this->frequencyDictionary[] = "wednesdays";
        $this->frequencyDictionary[] = "thursdays";
        $this->frequencyDictionary[] = "fridays";
        $this->frequencyDictionary[] = "saturdays";
        $this->frequencyDictionary[] = "sundays";
        $this->frequencyDictionary[] = "/^every([A-Z][a-zA-Z]+)?(Minute|Hour|Day|Month)s?$/";

        $this->timeSetDictionary[] = "on";
        $this->timeSetDictionary[] = "at";

        $this->lifeTimeDictionary[] = "between";
        $this->lifeTimeDictionary[] = "from";
        $this->lifeTimeDictionary[] = "to";

        $this->individualFieldsSettingsDictionary[] = "days";
        $this->individualFieldsSettingsDictionary[] = "hour";
        $this->individualFieldsSettingsDictionary[] = "minute";
        $this->individualFieldsSettingsDictionary[] = "dayOfMonth";
        $this->individualFieldsSettingsDictionary[] = "month";
        $this->individualFieldsSettingsDictionary[] = "dayOfWeek";
    }

    public function getFrequencyDictionary()
    {
        return $this->frequencyDictionary;
    }
    public function getTimeSetDictionary()
    {
        return $this->timeSetDictionary;
    }
    public function getLifeTimeDictionary()
    {
        return $this->lifeTimeDictionary;
    }
    public function getIindividualFieldsSettingsDictionary()
    {
        return $this->individualFieldsSettingsDictionary;
    }


    protected function getTaskTemplate()
    {

        $task_content = '';
        $task_content .= '<?php\n\n';
        $task_content .= 'use Crunz\Schedule;\n\n';
        $task_content .= '$schedule = new Schedule();\n\n';



        $task_content .= 'return $schedule;';





        return $task_content;
    }






    public function test(): void
    {
        // $this->getMailer()
        //     ->send(
        //         $this->getMessage($subject, $message)
        //     )
        // ;
    }


    /**
     * Get the task type.
     *
     * @param string $pippo
     *
     * @return string
     */
    // protected function getTaskTemplate()
    // {

    //     $task_content = '';
    //     $task_content .= '<?php\n\n';
    //     $task_content .= 'use Crunz\Schedule;\n\n';
    //     $task_content .= '$schedule = new Schedule();\n\n';



    //     $task_content .= 'return $schedule;';





    //     return $task_content;
    // }
}
