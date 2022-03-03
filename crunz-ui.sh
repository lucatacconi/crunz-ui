#!/bin/bash
# sysinfo_page - Execute Crunz tasks, calculate execution duration, outcome e write specific log. Compatibile with Crunz version v2.0.2

usage="
./$(basename "$0") [-h] [-d tasks_path ] [ -l logs_path] [-t n] [-f]
execute Crunz tasks, calculate execution duration, outcome e write specific log. Use Crunz configuration file to set task path and task.

Usage:
    -h: show this help text
    -d <tasks_path>: set tasks directory to tasks_path (default: ./tasks; Read Crunz configuration file crunz.yml)
    -l <logs path>: set logs directory to logs_path (default: ./var/logs; Check environment configuration in Crunz-ui directory)
    -p <executable>: set executable php location (Needed when using crunz-ui installed in a docker. default is '')
    -t <n>: Configure Crunz to run only specified task (Wait for task's execution end)
    -f: Forces Crunz to run task even if not programmed

"

usage_def="
Wrong parameters used.
$usage
"

absolute_tasks_contaniner_path="$( cd "$(dirname "$0")" ; pwd -P )"
absolute_tasks_path="$( cd "$(dirname "$0")" ; pwd -P )/${tasks_path#"./"}"

logs_path="./var/logs"
forced_execution=""
id_task=-1
php_executable=$(which php)


declare -a a_no_due=("No event is due!")
declare -a a_task_outcome=("success" "fail")
end_exec_string="Invoke Schedule's ping after."


function parse_yaml {
   local prefix=$2
   local s='[[:space:]]*' w='[a-zA-Z0-9_]*' fs=$(echo @|tr @ '\034')
   sed -ne "s|^\($s\):|\1|" \
        -e "s|^\($s\)\($w\)$s:$s[\"']\(.*\)[\"']$s\$|\1$fs\2$fs\3|p" \
        -e "s|^\($s\)\($w\)$s:$s\(.*\)$s\$|\1$fs\2$fs\3|p"  $1 |
   awk -F$fs '{
      indent = length($1)/2;
      vname[indent] = $2;
      for (i in vname) {if (i > indent) {delete vname[i]}}
      if (length($3) > 0) {
         vn=""; for (i=0; i<indent; i++) {vn=(vn)(vname[i])("_")}
         printf("%s%s%s=\"%s\"\n", "'$prefix'",vn, $2, $3);
      }
   }'
}


function runTask() {

    p_task_counter=$1
    p_forced_execution=$2
    p_tasks_path=$3
    p_logs_path=$4
    p_php_executable=$4

    file_uuid="."$(echo {A..Z} {a..z} {0..9} {0..9} | tr ' ' "\n" | shuf | xargs | tr -d ' ' | cut -b 1-32)
    file_seed=$(echo {A..Z} {a..z} {0..9} {0..9} | tr ' ' "\n" | shuf | xargs | tr -d ' ' | cut -b 1-4)

    task_start_datetime=$(date +"%Y%m%d%H%M")

    ./vendor/bin/crunz schedule:run -t$p_task_counter $p_forced_execution -vvv $p_tasks_path > $p_logs_path/$file_uuid.log

    task_stop_datetime=$(date +"%Y%m%d%H%M")

    task_launched="Y"
    execution_content=()

    row_cnt=0
    while IFS= read -r line
    do
        #Has the task been launched?
        if [[ ${a_no_due[*]} == $line ]]; then
            task_launched="N"
        fi

        execution_content[$row_cnt]=$line
        ((row_cnt++))

    done < "$p_logs_path/$file_uuid.log"


    # executed_task_row=""
    row_cnt=0
    row_cnt_p1=0
    row_end_founded="N"
    if [[ $task_launched == "Y" ]]; then
        for line in "${execution_content[@]}"
        do
            let row_cnt_p1=$row_cnt+1

            if [[ $line == *"status: "* ]]; then
                for outcome_status in "${a_task_outcome[@]}";
                do
                    if [[ $line =~ $outcome_status ]]; then
                        row_end_founded="Y"
                        executed_task_row=$line
                    fi
                done
            fi
            ((row_cnt++))
        done
    fi

    executed_task_outcome="KO"
    if [[ $task_launched == "Y" && $row_end_founded == "Y" && $executed_task_row != "" ]]; then

        #I'm reading the outcome of task execution
        start_task_outcome=" status: "
        end_task_outcome="\."

        executed_task_outcome=$(echo $executed_task_row | grep -oP "(?<=$start_task_outcome).*?(?=$end_task_outcome)")

        if [ "$executed_task_outcome" == "success" ]; then
            executed_task_outcome="OK"
        else
            executed_task_outcome="KO"
        fi

        event_unique_id=$(${p_php_executable} -r 'include  "./TasksTreeReader.php"; $res = TasksTreeReader::getEventUniqueKey('$p_task_counter'); echo $res;')

        log_task_name=$event_unique_id"_"$executed_task_outcome"_"$task_start_datetime"_"$task_stop_datetime"_"$file_seed".log"
        mv $p_logs_path/$file_uuid.log $p_logs_path/$log_task_name
    else
        rm $p_logs_path/$file_uuid.log
    fi
}

# ========================================================================================

tasks_path="./tasks"
tasks_suffix="Tasks.php"

if [ ! -f "./crunz.yml" ]; then
    echo "Crunz configuration file (./crunz.yml) doesn't exist."
    exit 1
fi

eval $(parse_yaml ./crunz.yml "CONF_")

if [ -n "$CONF_source" ]
then
    tasks_path="./"${CONF_source/"./"/''}
fi

if [ -n "$CONF_suffix" ]
then
    tasks_suffix=$CONF_suffix
fi

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
        -p)
            php_executable="$2"
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
        ;;
        *)
            echo "$usage_def" >&2
            exit 1
    esac
done

logs_path=${logs_path/"./"/''}

if [ ! -w $logs_path ]; then
    echo "Logdir $logs_path doesn't exist or is not writable. Check directory and permitions."
    exit 1
fi

if [ $id_task -gt 0 ]; then
    runTask $id_task "$forced_execution" "$tasks_path" "$logs_path" "$php_executable"
else

    tasks_count=$(${php_executable} -r 'include  "./TasksTreeReader.php"; $res = TasksTreeReader::getMaxNumTasks(); echo $res;')

    task_counter=1
    while [ $task_counter -le $tasks_count ]
    do
        runTask $task_counter "$forced_execution" "$tasks_path" "$logs_path" "$php_executable" &
        ((task_counter++))
    done
fi

# echo "Executed"
exit 0
