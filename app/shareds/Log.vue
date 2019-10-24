<template>
    <v-dialog :value="true" persistent max-width="800px" @on-close="closeModal()">
        <v-card>
            <v-toolbar
                dense
                dark
                color="#607d8b"
            >
                <v-toolbar-title>
                    {{modalTitle}}
                </v-toolbar-title>
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

            <v-card-text class="pt-5">
                <v-form>
                    <v-container>
                        <v-row>
                            <v-col cols="12">
                                <strong>Crunz log</strong>
                                <div id="crunz-log"> {{ logdata.crunzLog_content }} </div>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="12">
                                <strong>Custom log</strong>
                                <div id="custom-log"> {{ logdata.customLog_content }} </div>
                            </v-col>
                        </v-row>
                    </v-container>
                <v-form>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
module.exports = {
    data:function(){
        return{
            modalTitle:"Log",
            logdata: {
                "crunzLog_content" : "Test crunz log",
                "customLog_content" : "Test custom log"
            },
            crunzLog : null,
            customLog : null
        }
    },

    props: ['rowdata'],

    created:function() {
        // this.readData()
        // console.log(JSON.stringify(this.rowdata));
    },

    mounted: function () {
        var self = this;


        self.crunzLog = ace.edit("crunz-log");
        // self.crunzLog.setTheme("ace/theme/eclipse");
        self.crunzLog.getSession().setMode("ace/mode/text");

        self.crunzLog.setOptions({
            showPrintMargin: false,
            fontSize: 14
        });

        // self.crunzLog.session.setValue("self.crunzLog");


        self.customLog = ace.edit("custom-log");
        // self.customLog.setTheme("ace/theme/eclipse");
        self.customLog.getSession().setMode("ace/mode/text");

        self.customLog.setOptions({
            showPrintMargin: false,
            fontSize: 14
        });

        // self.customLog.session.setValue("self.customLog");

    },


    methods: {
        closeModal: function () {
            var self = this;
            self.$emit('on-close-edit-modal');
        },

        readData:function(){
            // var self=this
            // Utils.apiCall("get", "/task/")
            // .then(function (response) {
            //     if(response.data.length!=0){
            //         self.logdata=JSON.parse(JSON.stringify(response.data))
            //     }
            // });
        },
    },
}
</script>

<style>
    #crunz-log {
        height: 300px;
    }
    #custom-log {
        height: 300px;
    }

</style>
