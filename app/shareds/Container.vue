<template id="container" lang="html">
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
            'loading': httpVueLoader('../../app/shareds/Loading.vue' + '?v=' + new Date().getTime()),
            'login': httpVueLoader('../../app/sections/login/Login.vue' + '?v=' + new Date().getTime()),
            'navigator': httpVueLoader('../../app/shareds/Navigator.vue' + '?v=' + new Date().getTime())
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
