<template>
    <div class="ps-lg-7 pt-4">
        <h1 class="text-h6 fw-700 mb-2">{{ $t('create_new_refund_request') }}</h1>
        <v-form lazy-validation v-on:submit.prevent="sendRefundRequest()" autocomplete="chrome-off" enctype="multipart/form-data" v-if="!is_empty_obj(order)">
            <v-card  class="mb-6" outlined>
                <v-card-title>{{ $t('order_code') }}: {{ orderCode }}</v-card-title>
                <v-card-text>
                    <v-data-table :headers="headers" :items="order.products.data" class="" hide-default-footer mobile-breakpoint="750" item-key="order_detail_id">
                        <template v-slot:[`item.serial`]="{ item, index }">
                            <v-checkbox
                                true-icon="las la-check"
                                v-model="form.refund_items[index].status"
                                class="mt-1"
                                hide-details
                                name="order_detail_ids"
                                :disabled="!item.refund_eligibility || !item.refund_eligibility.is_eligible"
                            ></v-checkbox>
                        </template>
                        <template v-slot:[`item.product`]="{ item }">
                            <div class="d-flex align-center">
                                <img :src="item.thumbnail" :alt="item.name" @error="imageFallback($event)" class="size-70px flex-shrink-0">
                                <div class="flex-grow-1 ms-4">
                                    <div class="text-truncate-2">{{ item.name }}</div>
                                    <div class="" v-if="item.combinations.length > 0">
                                        <span v-for="(combination, j) in  item.combinations" :key="j" class="me-4 py-1 fs-12">
                                            <span class="opacity-70">{{combination.attribute}}</span> : <span class="fw-500">{{combination.value}}</span>
                                        </span>
                                    </div>
                                    <div class="mt-2 fs-12" v-if="item.refund_eligibility">
                                        <span class="text-success" v-if="item.refund_eligibility.is_eligible">
                                            {{ item.refund_eligibility.message }}
                                            ({{ item.refund_eligibility.max_requestable_quantity }})
                                        </span>
                                        <span class="text-red" v-else>
                                            {{ item.refund_eligibility.message }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template v-slot:[`item.quantity`]="{ item, index }">
                            <vue-number-input
                                v-model="form.refund_items[index].quantity"
                                :min="1"
                                :max="item.refund_eligibility && item.refund_eligibility.max_requestable_quantity > 0 ? item.refund_eligibility.max_requestable_quantity : item.quantity"
                                :step="1"
                                align="center"
                                size="110px"
                                :disabled="!item.refund_eligibility || !item.refund_eligibility.is_eligible"
                            ></vue-number-input>
                        </template>
                        <template v-slot:[`item.unit_price`]="{ item }">
                            <span class="d-block fw-600">{{ format_price(item.price + item.tax) }}</span>
                        </template>
                        <template v-slot:[`item.total`]="{ item }">
                            <span class="d-block fw-600">{{ format_price(item.total) }}</span>
                        </template>
                    </v-data-table>
                </v-card-text>
            </v-card>

            <v-card  class="mb-6" outlined>
                <v-card-title class="">{{ $t("refund_information") }}</v-card-title>
                <v-card-text>

                    <div class="mb-3">
                        <div class="mb-1 fs-13 fw-500">
                            {{ $t('refund_reasons') }}
                            <span v-if="requiresReason" class="text-red">*</span>
                        </div>
                        <v-combobox
                            v-model="form.refund_reasons"
                            :items="reasonSuggestions"
                            :label="$t('choose_one')"
                            :menu-props="{ offsetY: true }"
                            @blur="v$.form.refund_reasons.$touch()"
                            hide-details="auto"
                            flat
                            variant="outlined"
                            solo
                            multiple
                            chips
                            clearable
                        >
                            <template :item="{ item}" >
                                <v-list-item :title="item.title || item" />
                            </template>
                        </v-combobox>
                        <p v-for="error of refundReasonsErrors" :key="error" class="text-red mt-1">
                            {{ error }}
                        </p>
                    </div>

                    <div class="mb-3">
                        <div class="mb-1 fs-13 fw-500">
                            {{ $t('refund_note') }}
                            <span v-if="requiresReason" class="text-red">*</span>
                        </div>
                        <v-textarea
                            :placeholder="requiresReason ? 'Describe the issue for the selected items' : $t('refund_note')"
                            @blur="v$.form.refund_note.$touch()"
                            hide-details="auto"
                            rows="3"
                            v-model="form.refund_note"
                            variant="outlined"
                            no-resize
                        ></v-textarea>
                        <p v-for="error of refundNoteErrors" :key="error" class="text-red mt-1">
                            {{ error }}
                        </p>
                    </div>

                    <div class="mb-3">
                        <div class="mb-1 fs-13 fw-500">
                            {{ $t('attachments') }}
                            <span v-if="requiresEvidence" class="text-red">*</span>
                        </div>
                        <v-file-input
                            :label="$t('select_images')"
                            prepend-icon=""
                            accept="image/png, image/jpg, image/jpeg"
                            hide-details="auto"
                            variant="outlined"
                            multiple
                            dense
                            solo
                            flat
                            clearable
                            clear-icon="las la-times"
                            v-model="form.attachments"
                        >
                            <template v-slot:selection="{ text }">
                                <v-chip
                                    small
                                    label
                                    color="primary"
                                >
                                    {{ text }}
                                </v-chip>
                            </template>
                        </v-file-input>
                        <p v-if="requiresEvidence" class="fs-12 text-red mb-0">
                            Evidence is required for the selected items.
                        </p>
                    </div>
                    <v-btn 
                        type="submit" 
                        color="primary" 
                        elevation="0" 
                        @click="sendRefundRequest" 
                        :loading="loading"
                        :disabled="loading"
                        class="px-10 mt-2"
                    >{{ $t('request_refund') }}</v-btn>
                </v-card-text>
            </v-card>
        </v-form>
    </div>
</template>

<script>
import { useVuelidate } from '@vuelidate/core';
import { requiredIf } from "@vuelidate/validators";
import { mapGetters } from "vuex";
export default {
    data: () => ({
        v$: useVuelidate(),
        form: {
            refund_items: [],
            refund_reasons: [],
            refund_note: "",
            attachments: null,
        },
        orderCode: null,
        order: {},
        loading: false
    }),
    validations() {
        return {
            form: {
                refund_reasons: {
                    required: requiredIf(() => this.requiresReason),
                },
                refund_note: {
                    required: requiredIf(() => this.requiresReason),
                },
            },
        };
    },
    computed:{
        ...mapGetters('app',['refundSettings']),
        selectedRefundItems() {
            return this.form.refund_items.filter(item => item.status);
        },
        selectedProductMap() {
            return new Map((this.order.products?.data || []).map(product => [product.order_detail_id, product]));
        },
        selectedEligibility() {
            return this.selectedRefundItems
                .map(item => this.selectedProductMap.get(item.order_detail_id)?.refund_eligibility)
                .filter(Boolean);
        },
        requiresReason() {
            return this.selectedEligibility.some(item => item.requires_reason);
        },
        requiresEvidence() {
            return this.selectedEligibility.some(item => item.requires_evidence);
        },
        reasonSuggestions() {
            return this.refundSettings?.refund_reason_types || [];
        },
        headers(){
            let headers = [
                {
                    title: '#',
                    align: 'start',
                    sortable: false,
                    value: 'serial',
                },
                {
                    title: this.$i18n.t('product'),
                    sortable: false,
                    value: 'product'
                },
                {
                    title: this.$i18n.t('quantity'),
                    sortable: false,
                    value: 'quantity'
                },
                {
                    title: this.$i18n.t('unit_price'),
                    sortable: false,
                    value: 'unit_price'
                },
                {
                    title: this.$i18n.t('total'),
                    sortable: false,
                    align: 'end',
                    value: 'total'
                }
            ]
            return headers
        },
        refundReasonsErrors() {
            const errors = [];
            if (!this.v$.form.refund_reasons.$dirty) return errors;
            !this.v$.form.refund_reasons.required &&
                errors.push(this.$i18n.t("this_field_is_required"));
            return errors;
        },
        refundNoteErrors() {
            const errors = [];
            if (!this.v$.form.refund_note.$dirty) return errors;
            !this.v$.form.refund_note.required &&
                errors.push(this.$i18n.t("this_field_is_required"));
            return errors;
        },
    },
    methods:{
        async getDetails(orderId){
            const res = await this.call_api("get", `user/refund-request/create/${orderId}`);
            if(res.data.success){
                this.orderCode = res.data.order_code
                this.order = res.data.order
                const preselectedOrderDetailId = Number(this.$route.query.orderDetailId || 0);
                this.order.products.data.forEach( product => {
                    let item = {
                        status: preselectedOrderDetailId === product.order_detail_id && product.refund_eligibility?.is_eligible,
                        order_detail_id: product.order_detail_id,
                        quantity: Math.max(1, product.refund_eligibility?.max_requestable_quantity || product.quantity),
                        unit_price: product.price,
                        unit_tax: product.tax,
                    }
                    this.form.refund_items.push(item)
                });
            }
            else{
                this.snack({
                    message: res.data.message,
                    color: "red"
                });
                this.$router.push({ name: "404" });
            }
        },

        async sendRefundRequest(){
            // Prevents form submitting if it has error
            const isFormCorrect = await this.v$.$validate();
            if (!isFormCorrect) return;

            let refund_items = this.selectedRefundItems
            if(refund_items.length == 0){
                this.snack({
                    message: this.$i18n.t("please_select_a_product."),
                    color: "red"
                });
                return
            }

            if (this.requiresEvidence && (!this.form.attachments || this.form.attachments.length === 0)) {
                this.snack({
                    message: "Evidence is required for the selected items.",
                    color: "red"
                });
                return;
            }

            this.loading = true;

            refund_items = JSON.stringify(this.form.refund_items.map(item => {
                let a = {
                    order_detail_id: item.order_detail_id,
                    quantity: item.quantity,
                    status: item.status,
                }
                return a
            }).filter(item => item.status))

            let formData = new FormData();
            formData.append('refund_items',refund_items)
            formData.append('order_id',this.order.id)
            formData.append('refund_reasons',this.form.refund_reasons.join(','))
            formData.append('refund_note',this.form.refund_note)
            if(this.form.attachments){
                this.form.attachments.forEach((file,index) =>{
                    formData.append(`attachments[${index}]`, file);
                })
            }

            const res = await this.call_api("post", "user/refund-request/store", formData, true);
            if (res.data.success) {
                this.snack({
                    message: res.data.message,
                });
                this.$router.push({ name: "RefundRequests" });
            }
            else{
                this.snack({
                    message: res.data.message,
                    color: "red"
                });
            }
            this.loading = false;
        },
        
        calculateRefund(){
            let amount = 0
            this.form.refund_items.forEach(item => {
                amount += item.status ? (item.unit_price + item.unit_tax) * item.quantity : 0
            })
            this.form.refund_amount = amount
        }
    },
    created(){
        this.getDetails(this.$route.params.orderId)
    }
};

</script>
