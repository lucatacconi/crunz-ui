<template>
    <v-speed-dial
        absolute
        bottom
        right
        direction="left"
        open-on-hover
        transition="slide-y-reverse-transition"
    >
        <template v-slot:activator>
            <v-btn
                color="blue darken-2"
                dark
                fab
            >
                <v-icon>fa fa-cog</v-icon>
            </v-btn>
        </template>
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
            <span>Upload file</span>
        </v-tooltip>
        <v-tooltip bottom>
            <template v-slot:activator="{ on }">
                <v-btn
                    fab
                    dark
                    small
                    color="indigo"
                    @click="opendEditModal()"
                    v-on="on"
                >
                    <v-icon>mdi-plus</v-icon>
                </v-btn>
            </template>
            <span>Add new task</span>
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
        opendEditModal:function(){
            this.$emit('edit-modal',true)
        },
        openUploadModal:function(){
            this.$emit('upload-modal',true)
        }
    },
}
</script>
