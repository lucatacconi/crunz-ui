<template>
    <v-dialog :value="true" persistent max-width="800px" @on-close="closeModal()">
        <v-card>
            <v-toolbar
                dense
                dark
                :color="rowdata.color"
            >
                <v-toolbar-title v-html="rowdata.name"></v-toolbar-title>
                <v-spacer></v-spacer>
                <v-toolbar-items>
                    <v-btn
                        icon
                        @click="closeModal()"
                    >
                        <v-icon>
                            close
                        </v-icon>
                    </v-btn>
                </v-toolbar-items>
            </v-toolbar>

            <v-card-text class="pt-0">
                <v-container>
                    <v-row>
                        <v-col cols="6" class="py-0">
                            <v-text-field
                                label="Last duration (minutes):"
                                :value="( exec_duration < 0 ? '--' : ( exec_duration == 0 ? '&lt;1' : exec_duration ))"
                                readonly
                                dense
                                hide-details
                            ></v-text-field>
                        </v-col>
                        <v-col cols="6" class="py-0">
                            <v-text-field
                                label="Status at the date and time:"
                                :value="status != 'EXEC' ? 'Scheduled/Waiting' : 'Executed' "
                                readonly
                                dense
                                hide-details
                            ></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12" class="py-0">
                            <v-text-field
                                label="Execution schedule:"
                                :value="rowdata.data.expression_readable"
                                readonly
                                dense
                                hide-details
                            ></v-text-field>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col cols="12" class="py-0">
                            <v-textarea
                                label="Description:"
                                :value="rowdata.data.task_description"
                                readonly
                                dense
                                hide-details
                            ></v-textarea>
                        </v-col>
                    </v-row>


                    <div v-if="status == 'EXEC'">
                        <v-row>
                            <v-col cols="6" class="py-0">
                                <v-text-field
                                    label="Execution date and time:"
                                    :value="rowdata.start"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6" class="py-0">
                                <v-text-field
                                    label="Duration (minutes):"
                                    :value="( exec_duration < 0 ? '--' : ( exec_duration == 0 ? '&lt;1' : exec_duration ))"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6" class="py-0">
                                <v-text-field v-if="exec_outcome != ''"
                                    label="Execution outcome:"
                                    :value="( exec_outcome == 'OK' ? 'Success' : 'Failed')"
                                    readonly
                                    dense
                                    hide-details
                                    :error="( exec_outcome == 'OK' ? false : true)"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6" class="py-0 pb-0">
                                <template>
                                    <div class="text-center">
                                        <v-btn  @click="openLogModal()"
                                                class="ma-2 mt-3 mb-0 pb-0"
                                                outlined
                                                bottom
                                                :color="rowdata.color">
                                            <v-icon left>fas fa-file-alt</v-icon>
                                            Execution log
                                        </v-btn>
                                    </div>
                                </template>
                            </v-col>
                        </v-row>
                    </div>
                    <div v-else>
                        <v-row>
                            <v-col cols="6" class="py-0">
                                <v-text-field
                                    label="Scheduled execution date/time:"
                                    :value="rowdata.data.next_run"
                                    readonly
                                    dense
                                    hide-details
                                ></v-text-field>
                            </v-col>
                        </v-row>
                    </div>

                </v-container>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
module.exports = {
    data:function(){
        return{
            status: null,
            exec_duration: 0,
            exec_outcome: null
        }
    },

    props: ['rowdata'],

    mounted:function() {

        var self = this;

        this.status = "PROGR";
        if(this.rowdata){

            if(this.rowdata.data.executed_task_lst.length > 0){
                this.exec_1_atleast = false
            }


            for (var task_start in this.rowdata.data.executed_task_lst) {
                if(this.rowdata.start == task_start){
                    this.status = "EXEC";
                }
            }
            for (var task_exec in this.rowdata.data.outcome_executed_task_lst) {
                if(this.rowdata.start == task_exec){
                    this.exec_outcome = this.rowdata.data.outcome_executed_task_lst[task_exec];
                }
            }

            if(this.rowdata.data.last_outcome != ''){
                this.exec_duration = moment(this.rowdata.end).diff(moment(this.rowdata.start), 'minutes')
            }else{
                this.exec_duration = -1;
            }
        }
    },

    methods: {
        closeModal: function () {
            var self = this;
            self.$emit('on-close-modal');
        },

        openLogModal: function () {
            var self = this;
            self.$emit('on-open-log-modal');
        }
    }
}
</script>

<style>
</style>
