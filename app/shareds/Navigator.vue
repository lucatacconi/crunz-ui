<template id="navigator">
    <div>
        <navbar @drawer="drawer=!drawer" :activesection="activeSection"></navbar>
        <navdrawer :drawer="drawer" @select="selection=$event" :navmap="navmap" :selection="selection" @selectedsection="onSelectedSection"></navdrawer>

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
                        self.load(response.data.routes[i].path,response.data.routes[i].component);
                    }
                }

                if (typeof response.data.navMap !== 'undefined' && response.data.navMap.length > 0) {
                    self.navmap = response.data.navMap;
                }

                if (typeof response.data.bootstrapPage !== 'undefined' && response.data.bootstrapPage.route != '') {
                    self.activeSection = response.data.bootstrapPage.title;
                    sessionStorage.setItem("activeSection", response.data.bootstrapPage.title);
                    if(self.$route.path!=response.data.bootstrapPage.route) router.push(response.data.bootstrapPage.route);
                }
            });
        },

        components: {
            'navbar': () => Utils.loadFileVue('../app/shareds/NavBar.vue'),
            'navdrawer': () => Utils.loadFileVue('../app/shareds/NavDrawer.vue'),
            'appfooter': () => Utils.loadFileVue('../app/shareds/Footer.vue')
        },

        methods: {
            onSelectedSection (value) {
                this.activeSection = value;
            },
            load:function(path,url){
                var self=this;

                Utils.loadFileVue(url).then(function(comp){
                    self.$router.addRoutes([
                        { path: path, component: comp },
                    ])
                })
            }
        }
    }
</script>

<style>
</style>
