<template id="navigator" lang="html">
    <v-app-bar app clipped-right color="blue-grey" dense dark>
        <v-app-bar-nav-icon @click="drawer()" ></v-app-bar-nav-icon>
        <v-toolbar-title>
            {{appName}}
            <span class="subtitle-1 yellow--text lighten-5 font-weight-black" v-if="activesection != ''"> - {{ activesection }}</span>
        </v-toolbar-title>
        <v-spacer></v-spacer>
        <navbartools></navbartools>
        <userinfo></userinfo>
    </v-app-bar>
</template>

<script type="text/javascript">
    module.exports = {
        data: function() {
            return {
            }
        },
        props:['activesection'],
        computed:{
            appName: function () {
                return document.querySelector("meta[name='application-name']").getAttribute("content");
            }
        },
        methods: {
            drawer:function(){
                this.$emit("drawer")
            }
        },
        components: {
            'userinfo': httpVueLoader('../../app/shareds/UserInfo.vue' + '?v=' + new Date().getTime()),
            'navbartools': httpVueLoader('../../app/shareds/NavBarTools.vue' + '?v=' + new Date().getTime())
        },
        mounted: function(){
        }
    }
</script>

<style>
</style>
