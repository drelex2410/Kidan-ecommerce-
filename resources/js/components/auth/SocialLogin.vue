<template>
    <div>
        <form :action="'/social-login/redirect/'+selectedsocialOption" ref="socialLoginForm" method="POST">
            <input type="hidden" name="redirect_to" :value="$route.path">
        </form>
        <v-btn
            v-if="generalSettings.social_login.google == 1"
            block
            size="large"
            variant="outlined"
            class="google-btn text-none"
            @click="socialAuth('google')"
        >
            <v-icon start>mdi-google</v-icon>
            Continue with Gmail
        </v-btn>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
export default {
    data: () => ({
        selectedsocialOption: null,
    }),
    computed:{
        ...mapGetters('app',[
            'appUrl',
            'generalSettings'
        ])
    },
    methods:{
        socialAuth(provider){
            this.selectedsocialOption = provider
            let self = this
            setTimeout(function(){
                self.$refs.socialLoginForm.submit()
            }, 300)
        }
    }
}
</script>

<style scoped>
.google-btn {
    border-radius: 8px;
    font-weight: 500;
}
</style>