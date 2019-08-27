<template id="loading" lang="html">
    <div class="text-center">
        <v-overlay z-index="15">
            <v-progress-circular
                :size="100"
                :width="10"
                color="grey lighten-3"
                indeterminate>
            </v-progress-circular>
        </v-overlay>
    </div>
</template>

<script type="text/javascript">
    module.exports = {
        data: function() {
            return {}
        }
    }
</script>

<style>
    .v-progress-circular {
        margin: 1rem
    }
</style>
