<style scoped>
</style>

<template>
    <div>
        <!-- https://xaksis.github.io/vue-good-table/guide/configuration/pagination-options.html#mode -->
        <vue-good-table
            :columns="columns"
            :rows="conversations"
            styleClass="vgt-table"
            :sort-options="{
                enabled: true,
                initialSortBy: {
                    field: 'happened_at',
                    type: 'desc'
                    }
            }"
            :pagination-options="{
                enabled: true
            }">
            <template slot="table-row" slot-scope="props">
                <span v-if="props.column.field == 'message_count'">
                    <span>
                        {{props.row.message_count}}
                        <svg width="18" height="15" viewBox="0 0 18 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2.29412 14.6014V12.8182H1.69853C0.772058 12.8182 0 12.0839 0 11.2028V6.67133C0 5.79021 0.772058 5.05594 1.69853 5.05594H5.60294C5.82353 5.05594 6.02206 5.22378 6.02206 5.45455C6.02206 5.68531 5.84559 5.85315 5.60294 5.85315H1.69853C1.21323 5.85315 0.816176 6.23077 0.816176 6.69231V11.2238C0.816176 11.6853 1.21323 12.0629 1.69853 12.0629H2.71324C2.93382 12.0629 3.13235 12.2308 3.13235 12.4615V13.6993L4.80882 12.1678C4.875 12.1049 4.9853 12.0629 5.09559 12.0629H9.52941C10.0147 12.0629 10.4118 11.6853 10.4118 11.2238V8.91608C10.4118 8.70629 10.5882 8.51748 10.8309 8.51748C11.0515 8.51748 11.25 8.68532 11.25 8.91608V11.2238C11.25 12.1049 10.4779 12.8392 9.55147 12.8392H5.25L3 14.8951C2.91177 14.958 2.82353 15 2.71324 15C2.66912 15 2.60294 15 2.55882 14.958C2.40441 14.8951 2.29412 14.7482 2.29412 14.6014ZM12.7059 7.84615H8.40441C7.45588 7.84615 6.68382 7.11188 6.68382 6.20979V1.63636C6.68382 0.734263 7.45588 0 8.40441 0H16.2794C17.2279 0 18 0.734263 18 1.63636V6.20979C18 7.11188 17.2279 7.84615 16.2794 7.84615H15.6838V9.62937C15.6838 9.7972 15.5956 9.92307 15.4412 9.98601C15.3971 10.007 15.3309 10.028 15.2868 10.028C15.1765 10.028 15.0882 9.98601 15 9.92307L12.7059 7.84615ZM8.40441 7.06993H12.8603C12.9706 7.06993 13.0588 7.11188 13.1471 7.17482L14.8456 8.72727V7.46853C14.8456 7.25874 15.0221 7.06993 15.2647 7.06993H16.2794C16.7647 7.06993 17.1618 6.6923 17.1618 6.23077V1.65734C17.1618 1.1958 16.7647 0.818181 16.2794 0.818181H8.40441C7.91912 0.818181 7.52206 1.1958 7.52206 1.65734V6.23077C7.52206 6.6923 7.91912 7.06993 8.40441 7.06993ZM12.3529 3.54545C12.0441 3.54545 11.7794 3.7972 11.7794 4.09091C11.7794 4.38462 12.0441 4.63636 12.3529 4.63636C12.6618 4.63636 12.9265 4.38462 12.9265 4.09091C12.9265 3.7972 12.6618 3.54545 12.3529 3.54545ZM14.4926 4.63636C14.8015 4.63636 15.0662 4.38462 15.0662 4.09091C15.0662 3.7972 14.8015 3.54545 14.4926 3.54545C14.1838 3.54545 13.9191 3.7972 13.9191 4.09091C13.9191 4.38462 14.1838 4.63636 14.4926 4.63636ZM10.1912 3.54545C9.88235 3.54545 9.61765 3.7972 9.61765 4.09091C9.61765 4.38462 9.88235 4.63636 10.1912 4.63636C10.5 4.63636 10.7647 4.38462 10.7647 4.09091C10.7647 3.7972 10.5 3.54545 10.1912 3.54545Z" fill="#67718A"/>
                        </svg>
                    </span>
                </span>
                <span v-else-if="props.column.field == 'after'">
                    <div class="action-btn pointer absolute" style="right: 20px;">
                        <v-selectmenu :data="menu" :regular="true" position="right">
                            <svg width="24" height="6" viewBox="0 0 24 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="24" height="6" fill="black" fill-opacity="0"/>
                                <circle cx="4.5" cy="2.5" r="2.5" fill="#505473" fill-opacity="0.86"/>
                                <circle cx="11.5" cy="2.5" r="2.5" fill="#505473" fill-opacity="0.86"/>
                                <circle cx="18.5" cy="2.5" r="2.5" fill="#505473" fill-opacity="0.86"/>
                            </svg>
                        </v-selectmenu>
                    </div>
                </span>
                <span v-else>
                    {{props.formattedRow[props.column.field]}}
                </span>
            </template>

            <!-- Actions column -->
            <template slot="table-column" slot-scope="props">
                <div v-if="props.column.field =='after'" class="tr">
                    {{props.column.label}}
                </div>
                <span v-else>
                    {{props.column.label}}
                </span>
            </template>
        </vue-good-table>

    </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                conversations: [],

                menu: [
                    {
                        content: '<svg class="mr1 relative" style="top: -1px;" width="13" height="8" viewBox="0 0 13 8" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="13" height="7.96449" fill="black" fill-opacity="0"/><path d="M0 3.98225C0.610995 2.78374 1.54146 1.77749 2.68856 1.07472C3.83566 0.371941 5.15474 0 6.5 0C7.84527 0 9.16434 0.371941 10.3114 1.07472C11.4585 1.77749 12.389 2.78374 13 3.98225C12.389 5.18076 11.4585 6.187 10.3114 6.88978C9.16434 7.59255 7.84527 7.96449 6.5 7.96449C5.15474 7.96449 3.83566 7.59255 2.68856 6.88978C1.54146 6.187 0.610995 5.18076 0 3.98225V3.98225ZM6.5 6.63531C7.20364 6.63531 7.87845 6.35579 8.376 5.85824C8.87354 5.3607 9.15306 4.68588 9.15306 3.98225C9.15306 3.27861 8.87354 2.60379 8.376 2.10625C7.87845 1.6087 7.20364 1.32918 6.5 1.32918C5.79636 1.32918 5.12155 1.6087 4.624 2.10625C4.12646 2.60379 3.84694 3.27861 3.84694 3.98225C3.84694 4.68588 4.12646 5.3607 4.624 5.85824C5.12155 6.35579 5.79636 6.63531 6.5 6.63531V6.63531ZM6.5 5.30878C6.14818 5.30878 5.81077 5.16902 5.562 4.92025C5.31323 4.67147 5.17347 4.33406 5.17347 3.98225C5.17347 3.63043 5.31323 3.29302 5.562 3.04425C5.81077 2.79548 6.14818 2.65572 6.5 2.65572C6.85182 2.65572 7.18923 2.79548 7.438 3.04425C7.68677 3.29302 7.82653 3.63043 7.82653 3.98225C7.82653 4.33406 7.68677 4.67147 7.438 4.92025C7.18923 5.16902 6.85182 5.30878 6.5 5.30878Z" fill="#748494"/></svg> View',
                        url : 'http://www.163.com'
                    },
                    {
                        content: '<svg class="mr1" width="13" height="13" viewBox="0 0 13 13" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.995 2.405L10.595 5.005L2.6 13H0V10.4L7.995 2.405ZM8.905 1.495L10.4 0L13 2.6L11.505 4.095L8.905 1.495V1.495Z" fill="#748494"/></svg> Edit',
                        url : 'http://www.163.com'
                    },
                    {
                        content: '<svg class="mr1 relative" style="top: 1px;" width="11" height="13" viewBox="0 0 11 13" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="11" height="13" fill="black" fill-opacity="0"/><path d="M2.75 1.3L4.125 0H6.875L8.25 1.3H11V2.6H0V1.3H2.75ZM0.6875 3.9H10.3125L9.625 13H1.375L0.6875 3.9ZM4.125 5.2V11.7H4.8125V5.2H4.125ZM6.1875 5.2V11.7H6.875V5.2H6.1875Z" fill="#748494"/></svg> Delete',
                        url : 'https://github.com'
                    },
                ],

                columns: [
                    {
                        label: 'Date',
                        field: 'happened_at',
                        tdClass: 'vgt-table-date',
                    },
                    {
                        label: 'Type',
                        field: 'contact_field_type',
                        width: '110px',
                    },
                    {
                        label: 'Messages',
                        field: 'message_count',
                        width: '110px',
                    },
                    {
                        label: 'Partial content (last message)',
                        field: 'content',
                    },
                    {
                        label: 'Actions',
                        field: 'after',
                        sortable: false,
                        tdClass: 'vgt-table-action',
                    }
                ],
            };
        },

        props: {
            hash: {
                type: Number,
            },
        },

        mounted() {
            this.prepareComponent(this.hash)
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent(hash) {
                axios.get('/people/' + hash + '/conversations')
                    .then(response => {
                        this.conversations = response.data;
                    });
            },
        }
    }
</script>
