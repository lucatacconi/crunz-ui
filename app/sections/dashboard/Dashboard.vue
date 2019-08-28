<template>
    <div>
        <v-card>
            <!-- <v-card-title>Silly-vue-scaffolding</v-card-title> -->
            <v-card-text class="pt-5">
            </v-card-text>

            <!-- <v-card-text style="height: 100px; position: relative">
                <v-btn
                absolute
                dark
                fab
                top
                right
                color="pink"
                >
                <v-icon>add</v-icon>
                </v-btn>
            </v-card-text> -->
        </v-card>

        <v-btn
            absolute
            bottom
            right
            fab
            dark
            color="pink"
            >
            <v-icon>add</v-icon>
        </v-btn>
    </div>
</template>

<script>
    module.exports = {
        data: function() {
            return {
            }
        },
        components: {
            'taskedit': httpVueLoader('../../app/shareds/TaskEdit.vue')
        },
    }
</script>

<style>
</style>
