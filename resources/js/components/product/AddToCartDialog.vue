<template>
    <v-dialog
    class="add-to-cart-dialog z-1020"
        v-model="cartDialog"
        width="1000"
        @click:outside="hideDialog"
    >
        <v-card>
            <v-toolbar color="grey-lighten-4 " class="d-block" dense flat>
                <v-toolbar-title class="fw-600">{{ dialogTitle }}</v-toolbar-title>
                <v-btn icon fab small class="ms-auto" @click="hideDialog">
                    <i class="las la-times fs-24"></i>
                </v-btn>
            </v-toolbar>
            <v-card-text class="pa-4 text-dark"> 
                <add-to-cart :is-loading="loading" :product-details="productDetails" :intent="dialogMode" />
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
import {mapGetters,mapMutations} from "vuex";
import AddToCart from './AddToCart.vue'
export default {
    components: { AddToCart },
    data: () => ({
        loading: true,
        productDetails: {}
    }),
    computed:{
        ...mapGetters('auth', {
            cartDialog:'showAddToCartDialog',
            productSlug:'cartDialogProductSlug',
            dialogMode:'cartDialogMode'
        }),
        dialogTitle() {
            return this.dialogMode === 'buy_now' ? this.$t('buy_now') : this.$t('add_to_cart');
        }
    },
    watch: {
        productSlug: {
            immediate: true,
            handler (newVal, oldVal) {
                this.getDetails()
            }
        }
    },
    methods:{
        ...mapMutations('auth',['showAddToCartDialog']),
        hideDialog(){
            this.showAddToCartDialog({status:false,slug:null,mode:'cart'})
            this.loading = true
        },
        async getDetails() {
            if(this.productSlug){
                const res = await this.call_api( "get", `product/details/${this.productSlug}`);
                if (res.data.success) {
                    this.productDetails = res.data.data;
                } else {
                    this.snack({
                        message: res.data.message,
                        color: "red",
                    });
                }
                this.loading = false;
            }
        },
    }
}
</script>
