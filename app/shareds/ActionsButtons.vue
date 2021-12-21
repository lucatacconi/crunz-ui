<template>
    <v-speed-dial
        absolute
        fixed
        bottom
        right
        direction="left"
        transition="slide-y-reverse-transition"
        style="margin-bottom:30px;"
    >
        <template v-slot:activator>
            <v-btn
                color="blue darken-2"
                dark
                fab
            >
                <v-icon large>mdi-cog</v-icon>
            </v-btn>
        </template>
        <v-tooltip bottom>
            <template v-slot:activator="{ on }">
                <v-btn
                    fab
                    dark
                    small
                    color="green accent-4"
                    @click="openNewTaskModal()"
                    v-on="on"
                >
                    <v-icon>mdi-plus</v-icon>
                </v-btn>
            </template>
            <span>New task</span>
        </v-tooltip>
        <v-tooltip bottom>
            <template v-slot:activator="{ on }">
                <v-btn
                    fab
                    dark
                    small
                    color="green"
                    @click="openUploadModal()"
                    v-on="on"
                >
                    <v-icon>mdi-upload</v-icon>
                </v-btn>
            </template>
            <span>Upload tasks</span>
        </v-tooltip>
        <v-tooltip bottom>
            <template v-slot:activator="{ on }">
                <v-btn
                    fab
                    dark
                    small
                    color="indigo"
                    @click="readData()"
                    v-on="on"
                >
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </template>
            <span>Refresh</span>
        </v-tooltip>
    </v-speed-dial>
</template>

<script>
module.exports = {
    data:function(){
        return{}
    },
    methods: {
        readData:function(){
            this.$emit('read-data',true)
        },
        openUploadModal:function(){
            this.$emit('upload-modal',true)
        },
        openNewTaskModal:function(){
            this.$emit('new-task-modal',true)
        }
    },
}
</script>
