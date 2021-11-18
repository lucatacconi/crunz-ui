<template id="container">
    <div>
        <loading v-if="showloading"></loading>
        <login v-if="!granted"></login>
        <navigator v-else></navigator>
    </div>
</template>

<script type="text/javascript">
    module.exports = {
        data: function() {
            return {
            }
        },
        computed: {
            granted: function () {
                if(!localStorage.getItem("token")){
                    return false;
                }else{
                    return true;
                }
            }
        },
        props: ['showloading'],
        components: {
            'loading': () => Utils.loadFileVue('../app/shareds/Loading.vue'),
            'login': () => Utils.loadFileVue('../app/sections/login/Login.vue'),
            'navigator': () => Utils.loadFileVue('../app/shareds/Navigator.vue')
        },
        methods: {
            checkSession: function(){
                var config = {
                    showLoading: false,
                    hideLoading: false
                }

                Utils.apiCall("get", "/auth/session/check", null, config);
            }
        },

        created: function() {
            var accountData = false;
            if(localStorage.getItem("accountData") != '' && localStorage.getItem("accountData") != null && localStorage.getItem("accountData") != 'undefined'){
                accountData = JSON.parse(localStorage.getItem("accountData"));
            }

            if(!accountData || !(accountData.sessionExpireDate != '' && accountData.sessionExpireDate != null && accountData.sessionExpireDate != 'undefined')){
                localStorage.removeItem("token");
                localStorage.removeItem("accountData");
            }else{
                if(moment().isAfter(accountData.sessionExpireDate, 'second')){
                    localStorage.removeItem("token");
                    localStorage.removeItem("accountData");
                }
            }
        },
        mounted: function() {
            var self = this;

            if(this.granted){
                self.checkSession();
                setInterval(function(){ self.checkSession(); }, 60000 * 15);
            }
        }
    }
</script>

<style>
</style>
