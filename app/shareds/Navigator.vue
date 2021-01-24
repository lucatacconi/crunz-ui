<template id="navigator" lang="html">
    <div>
        <navbar v-on:drawer="drawer=!drawer" :activesection="activeSection"></navbar>
        <navdrawer :drawer="drawer" v-on:select="selection=$event" :navmap="navmap" :selection="selection" v-on:selectedsection="onSelectedSection"></navdrawer>

        <v-main>
            <v-container fluid fill-height>
                <v-layout>
                    <v-flex>
                        <router-view></router-view>
                    </v-flex>
                </v-layout>
            </v-container>
        </v-main>

        <appfooter></appfooter>
   </div>
</template>

<script type="text/javascript">
    module.exports = {
        data: function() {
            return {
                drawer: "true",
                routes: [],
                navmap: [],
                selection: 0,
                activeSection: ''
            }
        },

        mounted: function(){

            self = this;

            Utils.apiCall("get", "/navigation/")
            .then(function (response) {

                if (typeof response.data.routes !== 'undefined' && response.data.routes.length > 0) {
                    for(var i=0; i<response.data.routes.length; i++){
                        self.$router.addRoutes([
                            { path: response.data.routes[i].path, component: httpVueLoader(response.data.routes[i].component + '?v=' + new Date().getTime()) },
                        ])
                    }
                }

                if (typeof response.data.navMap !== 'undefined' && response.data.navMap.length > 0) {
                    self.navmap = response.data.navMap;
                }

                if (typeof response.data.bootstrapPage !== 'undefined' && response.data.bootstrapPage.route != '') {
                    self.activeSection = response.data.bootstrapPage.title;
                    sessionStorage.setItem("activeSection", response.data.bootstrapPage.title);
                    router.push(response.data.bootstrapPage.route);
                }
            });
        },

        components: {
            'navbar': httpVueLoader('../../app/shareds/NavBar.vue' + '?v=' + new Date().getTime()),
            'navdrawer': httpVueLoader('../../app/shareds/NavDrawer.vue' + '?v=' + new Date().getTime()),
            'appfooter': httpVueLoader('../../app/shareds/Footer.vue' + '?v=' + new Date().getTime())
        },

        methods: {
            onSelectedSection (value) {
                this.activeSection = value;
            }
        }
    }
</script>

<style>
</style>
