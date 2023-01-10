<template>
    <div>
        <v-container>
            <v-row class="pa-0">

                <v-col lg="4" md="6" xs="12">
                    <v-card class="fill-height">
                        <v-card-title >
                            Number of tasks:
                        </v-card-title>
                        <v-card-text>
                            <div>Number of tasks (task's files):</div>
                            <p>
                                <span class="text-h1 text--primary">
                                    <strong>10</strong>
                                </span>
                                <span class="text-h4 text--primary">
                                    <strong>(10)</strong>
                                </span>
                            </p>
                            <div>
                                relating to or dependent on charity; charitable.<br>
                                "an eleemosynary educational institution."
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col lg="4" md="6" xs="12">
                    <v-card class="fill-height">
                        <v-card-title >
                            Uptime:
                        </v-card-title>
                        <v-card-text>
                            <div>Uptime:</div>
                            <p>
                                <span class="text-h1 text--primary">
                                    <strong>10 days</strong>
                                </span>
                            </p>
                            <div>
                                relating to or dependent on charity; charitable.<br>
                                "an eleemosynary educational institution."
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>

                <v-col lg="4" md="6" xs="12">
                    <v-card class="fill-height">
                        <v-card-title >
                            Executed tasks:
                        </v-card-title>
                        <v-card-text>
                            <div>daily (month):</div>
                            <p>
                                <span class="text-h1 text--primary">
                                    <strong>200</strong>
                                </span>
                                <span class="text-h4 text--primary">
                                    <strong>(123123)</strong>
                                </span>
                            </p>
                            <div>
                                relating to or dependent on charity; charitable.<br>
                                "an eleemosynary educational institution."
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>



                <v-col lg="4" md="6" xs="12">
                    <v-card class="fill-height">
                        <v-card-title >
                            Log size:
                        </v-card-title>
                        <v-card-text>
                            <div>Log size:</div>
                            <p>
                                <span class="text-h1 text--primary">
                                    <strong>10 Gb</strong>
                                </span>
                            </p>
                            <div>
                                relating to or dependent on charity; charitable.<br>
                                "an eleemosynary educational institution."
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>
    </div>
</template>

<script>
module.exports = {
    data:function(){
        return{
            task_path_lovs:[]
        }
    },
    methods: {
        readData:function(options = {}){
            var self = this;
        },
    },

    computed: {

    },

    created:function() {
        // this.readData();
    },

    mounted:function(){
        // this.readData();
    },

    components:{ }
}
</script>
