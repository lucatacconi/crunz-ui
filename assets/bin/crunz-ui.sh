#!/bin/bash
# sysinfo_page - A script to produce a system information HTML file

usage="
./$(basename "$0") [-h] [-d tasks_path ] [ -l logs_path] [-tn] [-f] -- execute Crunz tasks, calculate execution duration, outcome e write specific log.

Usage:
    -h: show this help text
    -d <tasks_path>: set tasks directory to tasks_path (default: ./tasks)
    -l <logs path>: set logs directory to logs_path (default: ./logs)
    -t<n>: Configure Crunz to run only specified task (Wait for task's execution end)
    -f: Forces Crunz to run task even if not programmed

"
tasks_path="./tasks"
tasks_suffix="Tasks.php"
absolute_tasks_contaniner_path="$( cd "$(dirname "$0")" ; pwd -P )"
absolute_tasks_path="$( cd "$(dirname "$0")" ; pwd -P )/${tasks_path#"./"}"

logs_path="./logs"
forced_execution=""
id_task=-1

a_no_due=["No event is due!"]
a_task_outcome=["success","fail"]


function runTask() {

    p_task_counter=$1
    p_forced_execution=$2
    p_tasks_path=$3
    p_logs_path=$4

    file_uuid="."$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 32 | head -n 1)
    file_seed="."$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 4 | head -n 1)

    task_start_datetime=$(date +"%Y%m%d%H%M")

    ./vendor/bin/crunz schedule:run -t$p_task_counter $p_forced_execution -vvv $p_tasks_path > $p_logs_path/$file_uuid.log

    task_stop_datetime=$(date +"%Y%m%d%H%M")

    task_launched="Y"
    executed_task_row=""
    executed_task=""
    executed_task_outcome="KO"

    while IFS= read -r line
    do
        #Has the task been launched?
        if [[ ${a_no_due[*]} =~ $line ]]; then
            task_launched="N"
        fi

        if [[ $line == *"Task Task file"* ]]; then
            row_end_founded="N"
            for outcome_status in "${a_task_outcome[@]}";
            do
                if [[ "$line" =~ $outcome_status ]]; then
                    row_end_founded="Y"
                fi
            done

            if [ $row_end_founded == "Y" ]; then
                executed_task_row=$line
            fi
        fi

    done < "$p_logs_path/$file_uuid.log"

    if [[ $task_launched == "Y" && $executed_task_row != "" ]]; then

        start_task_path="Task Task file "
        end_task_path=" status:"

        #I'm reading task's relative path in tasks directory. I need it for log file name
        executed_task=$(echo $executed_task_row | grep -oP "(?<=$start_task_path).*?(?=$end_task_path)")
        executed_task=${executed_task/".php"/""}

        log_task_name=${executed_task/"$absolute_tasks_path/"/''}
        log_task_name=${log_task_name/"/"/'_'}


        #I'm reading the outcome of task execution
        start_task_outcome="php status: "
        end_task_outcome="\."

        executed_task_outcome=$(echo $executed_task_row | grep -oP "(?<=$start_task_outcome).*?(?=$end_task_outcome)")

        if [ "$executed_task_outcome" == "success" ]; then
            executed_task_outcome="OK"
        else
            executed_task_outcome="KO"
        fi

        log_task_name=$log_task_name"_"$executed_task_outcome"_"$task_start_datetime"_"$task_stop_datetime"_"$file_seed".log"
        mv $p_logs_path/$file_uuid.log $p_logs_path/$log_task_name
    else
        rm $p_logs_path/$file_uuid.log
    fi
}

while [[ $# -gt 0 ]]
do
    key="$1"
    case $key in
        -d)
            tasks_path="$2"
            shift # past argument
            shift # past value
        ;;
        -l)
            logs_path="$2"
            shift # past argument
            shift # past value
        ;;
        -t)
            id_task="$2"
            shift # past argument
            shift # past value
        ;;
        -f)
            forced_execution="-f"
            shift # past argument
        ;;
        -h)
            echo "$usage" >&2
            exit 1
    esac
done

if [ $id_task -gt 0 ]; then
    runTask $id_task "$forced_execution" "$tasks_path" "$logs_path"
else

    tasks_count=$(find $tasks_path -name *$tasks_suffix -type f | wc -l)

    task_counter=1
    while [ $task_counter -le $tasks_count ]
    do
        runTask $task_counter "$forced_execution" "$tasks_path" "$logs_path" &
        ((task_counter++))
    done
fi

exit 0
