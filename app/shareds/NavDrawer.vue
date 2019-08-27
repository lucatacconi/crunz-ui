<template>
    <div>
        <v-navigation-drawer
            v-model="drawer"
            app
            mobile-break-point="0"
        >
            <v-list dense>
                <template v-for="navItem in navMap">
                    <v-list-group
                        v-if="navItem.type == 'SUBM'"
                        :value="navItem.layout.expanded ? navItem.layout.expanded : null"
                        :key="navItem.id"
                        active-class="groupActivated-NavDrawer"
                    >
                        <template v-slot:activator>
                            <v-list-item-icon>
                                <v-icon>{{ navItem.layout.icon }}</v-icon>
                            </v-list-item-icon>

                            <v-list-item-content>
                                <v-list-item-title>
                                    {{ navItem.title }}
                                </v-list-item-title>
                                <v-list-item-subtitle v-if="navItem.subtitle">
                                    {{ navItem.subtitle }}
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </template>

                        <v-list-item
                            dense
                            v-for="subItem in navItem.subMenuItems"
                            :key="subItem.title"
                            @click="launchEvent(subItem)"
                            :disabled="subItem.layout.disabled || navItem.layout.disabled "
                            :class="subItem.layout.class ? subItem.layout.class : null"
                            active-class="itemActivated"
                        >
                            <v-list-item-icon>
                                <v-icon>{{ subItem.layout.icon }}</v-icon>
                            </v-list-item-icon>

                            <v-list-item-content>
                                <v-list-item-title
                                    :class="subItem.layout.color ? subItem.layout.color : null"
                                >
                                    {{ subItem.title }}
                                </v-list-item-title>
                                <v-list-item-subtitle v-if="subItem.subtitle">
                                    {{ subItem.subtitle }}
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </v-list-item>
                    </v-list-group>

                    <v-divider v-if="navItem.type == 'DIV'" :key="navItem.id"></v-divider>

                    <v-list-item
                        dense
                        v-else-if="navItem.type == 'ELM'"
                        :key="navItem.id"
                        @click="launchEvent(navItem)"
                        :disabled="navItem.layout.disabled"
                        :class="navItem.layout.class ? navItem.layout.class : null"
                    >
                        <v-list-item-icon>
                            <v-icon>{{ navItem.layout.icon }}</v-icon>
                        </v-list-item-icon>

                        <v-list-item-content>
                            <v-list-item-title
                                :class="navItem.layout.color ? navItem.layout.color : null"
                            >
                                {{ navItem.title }}
                            </v-list-item-title>
                            <v-list-item-subtitle v-if="navItem.subtitle">
                                {{ navItem.subtitle }}
                            </v-list-item-subtitle>
                        </v-list-item-content>
                    </v-list-item>
                </template>
            </v-list>
        </v-navigation-drawer>
    <div>
</template>

<script>
module.exports = {
    data:function(){
        return{
        }
    },
    computed: {
        navMap: function () {
            return this.navmap;
        }
    },


    props:['drawer','selection', 'navmap'],
    watch: {
        // selection:function(){
        //     for(var i=0;i<this.items.length;i++){
        //         if(i==this.selection){
        //             this.items[i].color="blue"
        //         }else{
        //             this.items[i].color="black"
        //         }
        //     }
        // }
    },
    methods: {
        launchEvent:function(navItem){
            sessionStorage.setItem("activeSection", navItem.title);
            this.$emit('selectedsection', navItem.title);

            if(navItem.actionType=="LINK"){
                window.open(navItem.action.url, navItem.action.target)
            }else if(navItem.actionType=="SECT"){
                //Rimuovo colore da tutte le voci
                for(var i=0;i<this.navmap.length;i++){
                    if(this.navmap[i].layout!=undefined){
                        this.navmap[i].layout.color=false
                    }
                    if(this.navmap[i].subMenuItems!=undefined){
                        for(var k=0;k<this.navmap[i].subMenuItems.length;k++){
                            if(this.navmap[i].subMenuItems[k].layout!=undefined){
                                this.navmap[i].subMenuItems[k].layout.color=false
                            }
                        }
                    }
                }
                //Coloro la voce corrente
                if(navItem.layout!=undefined){
                    navItem.layout.color="red--text"
                }
                router.push(navItem.action.path);
            }else if(navItem.actionType=="FUNC"){
                var F = new Function (navItem.action);
                return(F())
            }else{
                return;
            }
        }
    },
    mounted:function() {

    }
}
</script>

<style>
    .groupActivated-NavDrawer{
        background-color: #d5e5ed;
    }
</style>
