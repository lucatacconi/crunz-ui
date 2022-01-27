<template>
    <div>
        <!-- date-picker -->
        <v-dialog
            persistent
            v-model="datePickerModal"
            max-width="290px"
        >
            <v-card>
                <v-toolbar dense flat>
                    Select date
                    <v-spacer></v-spacer>
                    <v-btn
                        icon
                        @click="closeDatePickerModal()"
                    >
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-toolbar>
                <v-date-picker
                    v-model="date"
                    first-day-of-week="1"
                ></v-date-picker>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        small
                        outlined
                        @click="saveDate(true)"
                    >
                        Set date
                    </v-btn>
                    <v-btn
                        small
                        outlined
                        @click="saveDate()"
                        v-if="addTime"
                    >
                        Set time
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        <!-- time picker -->
        <v-dialog
            persistent
            v-model="timePickerModal"
            max-width="290px"
        >
            <v-card>
                <v-toolbar dense flat>
                    Select time
                    <v-spacer></v-spacer>
                    <v-btn
                        icon
                        @click="closeTimePickerModal()"
                    >
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-toolbar>
                <v-time-picker
                    v-model="time"
                    format="24hr"
                    :use-seconds="addSeconds"
                ></v-time-picker>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn
                        small
                        outlined
                        @click="saveTime()"
                    >
                        Set time
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            datePickerModal:false,
            timePickerModal:false,
            date:null,
            time:null,
            addTime:false,
            addSeconds:false,
            originValue:null,
        }
    },
    methods: {
        showDatePickerModal:function(value=null,addTime=false,addSeconds=false){
            this.originValue=value;
            this.addTime=addTime;
            this.addSeconds=addSeconds;
            var regex_date_or_datetime = new RegExp(/^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10} (?:[01]\d|2[0123]):(?:[012345]\d):(?:[012345]\d)$|^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10}$/gm);
            var regex_onlydatetime = new RegExp(/^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10} (?:[01]\d|2[0123]):(?:[012345]\d):(?:[012345]\d)$/gm);
            var regex_onlydate = new RegExp(/^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10}$/gm);
            var regex_onlytime = new RegExp(/(?:[01]\d|2[0123]):(?:[012345]\d):(?:[012345]\d)/gm);
            // console.log("origin value: "+this.originValue);
            if(!regex_date_or_datetime.test(this.originValue||this.originValue=='')) {
                this.date=dayjs().format("YYYY-MM-DD");
            }else{
                this.date=dayjs(this.originValue).format("YYYY-MM-DD");
            }
            this.datePickerModal=true;
        },
        closeDatePickerModal:function(){
            this.originValue=null;
            this.datePickerModal=false;
        },
        showTimePickerModal:function(value=null,addSeconds=false){
            this.originValue=value;
            this.addSeconds=addSeconds;
            var regex_date_or_datetime = new RegExp(/^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10} (?:[01]\d|2[0123]):(?:[012345]\d):(?:[012345]\d)$|^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10}$/gm);
            var regex_onlydatetime = new RegExp(/^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10} (?:[01]\d|2[0123]):(?:[012345]\d):(?:[012345]\d)$/gm);
            var regex_onlydate = new RegExp(/^(((\d{4}-((0[13578]-|1[02]-)(0[1-9]|[12]\d|3[01])|(0[13456789]-|1[012]-)(0[1-9]|[12]\d|30)|02-(0[1-9]|1\d|2[0-8])))|((([02468][048]|[13579][26])00|\d{2}([13579][26]|0[48]|[2468][048])))-02-29)){0,10}$/gm);
            var regex_onlytime = new RegExp(/(?:[01]\d|2[0123]):(?:[012345]\d):(?:[012345]\d)/gm);
            if(regex_onlydatetime.test(this.originValue&&this.originValue!='')){
                this.time=dayjs(this.originValue).format(this.addSeconds ? "HH:mm:ss" : "HH:mm");
            }else{
                this.time=dayjs().format(this.addSeconds ? "HH:mm:ss" : "HH:mm");
            }
            this.timePickerModal=true;
        },
        closeTimePickerModal:function(){
            this.originValue=null;
            this.timePickerModal=false;
        },
        saveDate:function(setOnlyDate=false){
            if(this.addTime&&!setOnlyDate){
                this.showTimePickerModal(this.originValue,this.addSeconds);
            }else{
                // console.log("result: "+this.date);
                this.$emit('result',this.date);
                this.closeDatePickerModal();
            }
        },
        saveTime:function(){
            if(this.addTime){
                if(this.addSeconds){
                    // console.log("result: "+this.date+" "+this.time);
                    this.$emit('result',this.date+" "+this.time);
                }else{
                    // console.log("result: "+this.date+" "+this.time+":00");
                    this.$emit('result',this.date+" "+this.time+":00");
                }
                this.closeDatePickerModal();
            }else{
                if(this.addSeconds){
                    // console.log("result: "+this.time);
                    this.$emit('result',this.time);
                }else{
                    // console.log("result: "+this.time+":00");
                    this.$emit('result',this.time+":00");
                }
            }
            this.closeTimePickerModal();
        }
    },
}
</script>
