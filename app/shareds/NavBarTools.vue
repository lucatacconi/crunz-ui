<template id="navbartools" lang="html">
    <div>
        <v-tooltip bottom>
            <template v-slot:activator="{ on, attrs }">
            <v-btn icon v-bind="attrs" v-on="on" @click="changeTheme"><v-icon>mdi-theme-light-dark</v-icon></v-btn>
            </template>
            <span>Change theme dark/light</span>
        </v-tooltip>
    </div>
</template>

<script type="text/javascript">
    module.exports = {
        data: function() {
            return {}
        },
        methods: {
            changeTheme:function () {
                this.$vuetify.theme.dark=!this.$vuetify.theme.dark;
                localStorage.setItem("theme",this.$vuetify.theme.dark);
            },
        },
    }
</script>

<style>
</style>
